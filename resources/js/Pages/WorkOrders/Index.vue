<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import axios from 'axios';
import WorkOrderQuote from '@/Components/WorkOrderQuote.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useDebounce } from '@/composables/useDebounce';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    kanban: Object,
    orders: Object,
    filters: Object,
    tenantId: Number
});

const { page, tenantRouteParams } = useTenantRouting();
const { debounce } = useDebounce();
const { formatCurrency } = useFormatting();
const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const commercialReportsEnabled = computed(() => planAccess.value?.commercial_reports_enabled ?? false);

const roles = computed(() => page.props.auth?.user?.roles ?? []);
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const isSuperAdmin = computed(() => Boolean(page.props.auth?.user?.is_super_admin));
const canDeleteWorkOrder = computed(() => (
    isSuperAdmin.value || permissions.value.includes('work-orders.delete')
));

// Column identifiers and headers (moved to top for early reference)
const columns = [
    { id: 'recepcion', title: 'Recepción', color: 'bg-orange-100/50' },
    { id: 'diagnostico', title: 'En Diagnóstico', color: 'bg-blue-100/50' },
    { id: 'esperando_repuestos', title: 'Esp. Repuestos', color: 'bg-yellow-100/50' },
    { id: 'control_calidad', title: 'Control de Calidad', color: 'bg-cyan-100/50' },
    { id: 'listo', title: 'Listo para Entrega', color: 'bg-green-100/50' },
];

// Filter & View State
const search = ref(props.filters?.search || '');
const month = ref(props.filters?.month || '');
const viewMode = ref(props.filters?.view || 'kanban');
const perPage = ref(props.filters?.per_page || 15);

const updateFilters = () => {
    router.get(
        route('work-orders.index', tenantRouteParams.value),
        {
            view: viewMode.value,
            month: month.value,
            search: search.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const triggerSearch = debounce(() => {
    updateFilters();
}, 300);

watch(search, () => {
    triggerSearch();
});

watch(month, () => {
    updateFilters();
});

watch(perPage, () => {
    updateFilters();
});

const setViewMode = (mode) => {
    viewMode.value = mode;
    updateFilters();
};

const getStatusLabel = (statusId) => {
    const col = columns.find(c => c.id === statusId);
    return col ? col.title : statusId;
};

// Modal state
const isModalOpen = ref(false);
const selectedWorkOrder = ref(null);
const isLoadingModal = ref(false);
const activeTab = ref('budget'); // 'budget' or 'evidence'

const quoteStatusConfig = {
    draft: { label: 'Borrador', classes: 'border-slate-200 bg-slate-50 text-slate-500' },
    pending_customer: { label: 'Pendiente cliente', classes: 'border-amber-200 bg-amber-50 text-amber-600' },
    accepted: { label: 'Aceptada', classes: 'border-emerald-200 bg-emerald-50 text-emerald-600' },
    rejected: { label: 'Rechazada', classes: 'border-rose-200 bg-rose-50 text-rose-600' },
};

const quoteItemTypeLabels = {
    product: 'Repuesto',
    service: 'Servicio',
    manual: 'Manual',
};

const resolveQuoteStatus = (status) => quoteStatusConfig[status] ?? {
    label: status || 'Sin cotización',
    classes: 'border-slate-200 bg-slate-50 text-slate-500',
};

const formatQuoteStatusLabel = (status) => resolveQuoteStatus(status).label;
const formatQuoteStatusClasses = (status) => resolveQuoteStatus(status).classes;

// Form for adding items
const itemMode = ref('product');
const itemErrors = ref({});
const itemForm = ref({
    product_id: '',
    service_id: '',
    quantity: 1
});

const availableProducts = ref([]);
const availableServices = ref([]);

const resetItemForm = () => {
    itemMode.value = 'product';
    itemErrors.value = {};
    itemForm.value = {
        product_id: '',
        service_id: '',
        quantity: 1,
    };
};

const setItemMode = (mode) => {
    itemMode.value = mode;
    itemErrors.value = {};
    itemForm.value.product_id = '';
    itemForm.value.service_id = '';
};

const hydrateCatalogs = (workOrder) => {
    availableProducts.value = workOrder?.catalogs?.products ?? [];
    availableServices.value = workOrder?.catalogs?.services ?? [];
};

const requiresQuoteConfirmation = (order, nextStatus) => (
    order?.originalStatus === 'recepcion'
    && nextStatus !== 'recepcion'
    && order?.quote?.status !== 'accepted'
);

const openModal = async (orderId) => {
    isModalOpen.value = true;
    isLoadingModal.value = true;
    selectedWorkOrder.value = null;
    resetItemForm();

    try {
        const orderResponse = await axios.get(route('api.work-orders.show', { ...tenantRouteParams.value, id: orderId }));
        selectedWorkOrder.value = orderResponse.data;
        hydrateCatalogs(orderResponse.data);
    } catch (error) {
        console.error('Error fetching work order details:', error);
    } finally {
        isLoadingModal.value = false;
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedWorkOrder.value = null;
    availableProducts.value = [];
    availableServices.value = [];
    resetItemForm();
};

const addItem = async () => {
    if (!selectedWorkOrder.value) return;

    itemErrors.value = {};

    const payload = {
        quantity: itemForm.value.quantity,
    };

    if (itemMode.value === 'product') {
        payload.product_id = itemForm.value.product_id;
    }

    if (itemMode.value === 'service') {
        payload.service_id = itemForm.value.service_id;
    }

    try {
        await axios.post(route('api.work-orders.items.store', { ...tenantRouteParams.value, workOrder: selectedWorkOrder.value.id }), payload);
        await openModal(selectedWorkOrder.value.id);
        resetItemForm();
    } catch (error) {
        if (error.response?.status === 422) {
            itemErrors.value = error.response.data.errors ?? {};
        } else {
            console.error('Error adding item:', error);
        }
    }
};

const removeItem = async (itemId) => {
    if (!confirm('¿Estás seguro de quitar este ítem de la cotización?')) return;

    try {
        await axios.delete(route('api.work-orders.items.destroy', {
            ...tenantRouteParams.value,
            workOrder: selectedWorkOrder.value.id,
            item: itemId
        }));
        await openModal(selectedWorkOrder.value.id);
    } catch (error) {
        console.error('Error removing item:', error);
    }
};

const uploadPhoto = async (event) => {
    const file = event.target.files[0];
    if (!file || !selectedWorkOrder.value) return;

    const formData = new FormData();
    formData.append('image', file);

    try {
        await axios.post(route('api.work-orders.images.upload', { ...tenantRouteParams.value, id: selectedWorkOrder.value.id }), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        // Refresh modal data
        openModal(selectedWorkOrder.value.id);
    } catch (error) {
        console.error('Error uploading image:', error);
    }
};

const deletePhoto = async (imageId) => {
    if (!confirm('¿Estás seguro de eliminar esta imagen?')) return;

    try {
        await axios.delete(route('api.work-orders.images.destroy', { ...tenantRouteParams.value, imageId }));
        // Refresh modal data
        openModal(selectedWorkOrder.value.id);
    } catch (error) {
        console.error('Error deleting image:', error);
    }
};

const previewQuote = () => {
    if (!selectedWorkOrder.value?.uuid) return;
    window.open(route('tracking.show', { uuid: selectedWorkOrder.value.uuid }), '_blank');
};


// Columns already defined at the top

const draggedItem = ref(null);
const currentHoverColumn = ref(null);

// Kanban horizontal scroll
const kanbanRef = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(true);
const scrollProgress = ref(0);
const activeColumnIndex = ref(0);
const showSwipeTutorial = ref(false);
let scrollInterval = null;

const updateScrollState = () => {
    if (!kanbanRef.value) return;
    canScrollLeft.value = kanbanRef.value.scrollLeft > 0;
    const maxScroll = kanbanRef.value.scrollWidth - kanbanRef.value.clientWidth;
    canScrollRight.value = kanbanRef.value.scrollLeft < maxScroll - 1;
    scrollProgress.value = maxScroll > 0 ? (kanbanRef.value.scrollLeft / maxScroll) * 100 : 0;

    // Calcular la columna activa basada en la posición de scroll
    const cols = kanbanRef.value.querySelectorAll('.kanban-column');
    if (cols.length > 0) {
        let nearestIndex = 0;
        let minDiff = Infinity;

        cols.forEach((col, idx) => {
            const colLeft = col.offsetLeft - kanbanRef.value.offsetLeft;
            const diff = Math.abs(colLeft - kanbanRef.value.scrollLeft);
            if (diff < minDiff) {
                minDiff = diff;
                nearestIndex = idx;
            }
        });

        activeColumnIndex.value = nearestIndex;
    }
};

const scrollToColumn = (index) => {
    if (!kanbanRef.value) return;
    const cols = kanbanRef.value.querySelectorAll('.kanban-column');
    if (cols[index]) {
        const colLeft = cols[index].offsetLeft - kanbanRef.value.offsetLeft;
        kanbanRef.value.scrollTo({
            left: colLeft,
            behavior: 'smooth'
        });
    }
};

const startScroll = (direction) => {
    if (scrollInterval) return;
    scrollInterval = setInterval(() => {
        if (!kanbanRef.value) return;
        kanbanRef.value.scrollLeft += direction === 'right' ? 12 : -12;
        updateScrollState();
    }, 16);
};

const stopScroll = () => {
    clearInterval(scrollInterval);
    scrollInterval = null;
};

// Drag-to-scroll (manito / grab cursor)
const isGrabbing = ref(false);
let grabStartX = 0;
let grabScrollLeft = 0;

const isInteractiveTarget = (target) =>
    target.closest('[draggable="true"]') ||
    target.closest('button') ||
    target.closest('a') ||
    target.closest('input') ||
    target.closest('select');

const onKanbanMouseDown = (e) => {
    if (isInteractiveTarget(e.target)) return;
    isGrabbing.value = true;
    grabStartX = e.pageX - kanbanRef.value.offsetLeft;
    grabScrollLeft = kanbanRef.value.scrollLeft;
};

const onKanbanMouseMove = (e) => {
    if (!isGrabbing.value) return;
    e.preventDefault();
    const x = e.pageX - kanbanRef.value.offsetLeft;
    const delta = (x - grabStartX) * 1.5;
    kanbanRef.value.scrollLeft = grabScrollLeft - delta;
    updateScrollState();
};

const onKanbanMouseUp = () => {
    isGrabbing.value = false;
};

const onDragStart = (order, fromColumnId) => {
    draggedItem.value = { ...order, originalStatus: fromColumnId };
};

const onDragOver = (e, columnId) => {
    e.preventDefault();
    currentHoverColumn.value = columnId;
};

const onDrop = (columnId) => {
    if (!draggedItem.value) return;

    const newStatus = columnId;
    const oldStatus = draggedItem.value.originalStatus;

    if (newStatus !== oldStatus) {
        const confirmedWithoutAcceptedQuote = requiresQuoteConfirmation(draggedItem.value, newStatus);

        if (confirmedWithoutAcceptedQuote) {
            const shouldContinue = window.confirm('La cotización aún no está aceptada por el cliente. ¿Quieres mover igualmente esta OT al siguiente paso?');

            if (!shouldContinue) {
                draggedItem.value = null;
                currentHoverColumn.value = null;
                return;
            }
        }

        router.put(route('work-orders.status.update', { workOrder: draggedItem.value.id }), {
            status: newStatus,
            confirmed_without_accepted_quote: confirmedWithoutAcceptedQuote,
        }, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    draggedItem.value = null;
    currentHoverColumn.value = null;
};

// Implementación WebSockets Reverb (Actualización en tiempo real)
onMounted(() => {
    const hasSeenTutorial = sessionStorage.getItem('feeto_kanban_tutorial_shown');
    if (!hasSeenTutorial && window.innerWidth < 1024) {
        showSwipeTutorial.value = true;
        // Auto-dismiss the swipe tutorial overlay after 8 seconds
        setTimeout(() => {
            if (showSwipeTutorial.value) {
                dismissTutorial();
            }
        }, 8000);
    }

    if (window.Echo) {
        window.Echo.private(`tenant.${props.tenantId}.work-orders`)
            .listen('WorkOrderDraftCreated', (e) => {
                const newOrder = e.workOrder;
                // Agregamos una propiedad temporal para la animación visual
                newOrder.isNew = true;

                const status = newOrder.status || 'recepcion';
                if (!props.kanban[status]) {
                    props.kanban[status] = [];
                }

                // Agregar dinámicamente haciendo push
                props.kanban[status].push(newOrder);

                // Quitar la animación después de 5 segundos
                setTimeout(() => {
                    newOrder.isNew = false;
                }, 5000);
            });

        // También escuchamos cambios de estado para que el Kanban se actualice entre usuarios
        window.Echo.private(`taller.${props.tenantId}`)
            .listen('.kanban.updated', (e) => {
                // Aquí podrías implementar la lógica para mover el card en el Kanban si es necesario
            });
    }

    if (kanbanRef.value) {
        kanbanRef.value.addEventListener('scroll', updateScrollState);
        updateScrollState();
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`tenant.${props.tenantId}.work-orders`);
        window.Echo.leave(`taller.${props.tenantId}`);
    }
    stopScroll();
    kanbanRef.value?.removeEventListener('scroll', updateScrollState);
});

const dismissTutorial = () => {
    showSwipeTutorial.value = false;
    sessionStorage.setItem('feeto_kanban_tutorial_shown', 'true');
};

const showDeleteModal = ref(false);
const workOrderToDelete = ref(null);

const confirmDeleteWorkOrder = (order) => {
    workOrderToDelete.value = order;
    showDeleteModal.value = true;
};

const submitDeleteWorkOrder = () => {
    if (!workOrderToDelete.value) return;
    router.delete(route('work-orders.destroy', { ...tenantRouteParams.value, workOrder: workOrderToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            workOrderToDelete.value = null;
        }
    });
};
</script>

<template>
    <TallerLayout>

        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h2 class="text-3xl font-black uppercase tracking-tight text-slate-900">
                    {{ viewMode === 'list' ? 'Listado de Órdenes' : 'Tablero de Órdenes' }}
                </h2>
                <p class="text-sm font-medium text-slate-500">
                    {{ viewMode === 'list' ? 'Búsqueda e historial de órdenes de trabajo' : 'Gestión del flujo de trabajo en tiempo real' }}
                </p>
            </div>

            <!-- View Switcher (Tabs / Icons) -->
            <div
                class="inline-flex rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-md p-1 shadow-sm shrink-0 self-start md:self-auto">
                <button type="button" @click="setViewMode('kanban')"
                    :class="viewMode === 'kanban' ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-xl px-4 py-2 text-xs font-black uppercase tracking-widest transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Tablero
                </button>
                <button type="button" @click="setViewMode('list')"
                    :class="viewMode === 'list' ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-xl px-4 py-2 text-xs font-black uppercase tracking-widest transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Listado
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white/80 backdrop-blur-md p-4 rounded-[2rem] border border-white shadow-sm mb-6">
            <!-- Search Filter -->
            <div class="relative flex-1 max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input v-model="search" type="text" placeholder="Buscar por Patente, OT, Cliente, Marca..."
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-slate-900 shadow-sm transition-all placeholder:text-slate-400 focus:border-[#FF7A00] focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/50" />
            </div>

            <!-- Month & Pagination Limit Filters -->
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Month selector -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mes:</label>
                    <input v-model="month" type="month"
                        class="rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm font-bold text-slate-700 outline-none focus:border-[#FF7A00] focus:ring-2 focus:ring-[#FF7A00]/20" />
                </div>

                <!-- Per page selector (only show in list view) -->
                <div v-if="viewMode === 'list'" class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Ver:</label>
                    <select v-model="perPage"
                        class="rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm font-bold text-slate-700 outline-none focus:border-[#FF7A00] focus:ring-2 focus:ring-[#FF7A00]/20">
                        <option :value="10">10 por pág</option>
                        <option :value="15">15 por pág</option>
                        <option :value="20">20 por pág</option>
                        <option :value="50">50 por pág</option>
                        <option :value="100">100 por pág</option>
                    </select>
                </div>

                <!-- Clear filters button -->
                <button v-if="search || month" @click="search = ''; month = ''"
                    class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 py-2 px-3 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <div v-if="viewMode === 'kanban'" class="relative">

            <!-- Scroll arrows -->
            <div class="flex items-center gap-2 mb-3">
                <button @mousedown="startScroll('left')" @mouseup="stopScroll" @mouseleave="stopScroll"
                    @touchstart.prevent="startScroll('left')" @touchend="stopScroll" :disabled="!canScrollLeft"
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-white shadow-sm border border-gray-100 transition-all duration-200"
                    :class="canScrollLeft ? 'text-slate-700 hover:bg-[#FF7A00] hover:text-white hover:border-[#FF7A00] hover:shadow-md' : 'text-slate-300 cursor-not-allowed'"
                    aria-label="Scroll izquierda">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button @mousedown="startScroll('right')" @mouseup="stopScroll" @mouseleave="stopScroll"
                    @touchstart.prevent="startScroll('right')" @touchend="stopScroll" :disabled="!canScrollRight"
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-white shadow-sm border border-gray-100 transition-all duration-200"
                    :class="canScrollRight ? 'text-slate-700 hover:bg-[#FF7A00] hover:text-white hover:border-[#FF7A00] hover:shadow-md' : 'text-slate-300 cursor-not-allowed'"
                    aria-label="Scroll derecha">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Mobile scroll progress indicator -->
            <div class="lg:hidden flex flex-col gap-2 mb-4 px-2 select-none">
                <div
                    class="flex justify-between items-center text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 animate-pulse text-[#FF7A00]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                        Recepción
                    </span>
                    <span class="text-[9px] text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full font-bold">
                        ← Desliza para navegar →
                    </span>
                    <span class="flex items-center gap-1">
                        Listo para Entrega
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 animate-pulse text-[#FF7A00]" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden relative shadow-inner">
                    <div class="bg-gradient-to-r from-orange-400 to-[#FF7A00] h-full rounded-full transition-all duration-75 shadow-sm"
                        :style="{ width: `${scrollProgress}%` }"></div>
                </div>
            </div>

            <!-- Tutorial de navegación Kanban en Mobile (Una vez por sesión) -->
            <div v-if="showSwipeTutorial" class="absolute inset-0 z-40 bg-slate-900/10 backdrop-blur-[2px] pointer-events-auto flex items-center justify-start pl-8 md:pl-12 rounded-[2rem] overflow-hidden" @click="dismissTutorial">
                <div class="relative flex flex-col items-center">
                    <!-- Floating simulated work order card -->
                    <div class="animate-swipe-tutorial-card bg-white/95 p-5 rounded-[2rem] shadow-[0_15px_40px_rgba(0,0,0,0.15)] border-2 border-orange-100 w-[240px] flex flex-col gap-2 relative pointer-events-none select-none">
                        <div class="bg-[#E2EAF4] text-slate-600 text-[10px] font-bold uppercase px-3 py-1.5 rounded-full self-start">
                            #OT-1234
                        </div>
                        <p class="text-3xl font-black font-mono text-slate-800 tracking-wider">
                            HH-GG-12
                        </p>
                        <div class="space-y-1 mt-1">
                            <p class="text-xs font-semibold text-slate-500">
                                Marca Modelo
                            </p>
                            <p class="text-[10px] text-slate-400 font-medium">
                                Arrastra para mover estado
                            </p>
                        </div>
                        
                        <!-- Animated hand cursor hovering/grabbing the card -->
                        <div class="absolute -bottom-6 right-6 text-[#FF7A00] animate-swipe-tutorial-hand">
                            <svg class="w-14 h-14 drop-shadow-[0_4px_8px_rgba(0,0,0,0.2)]" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 11a3 3 0 0 0-3-3h-1.5V7a3 3 0 0 0-6 0v1H10a3 3 0 0 0-3 3v4.3c-.3-.2-.7-.3-1.1-.3a2.9 2.9 0 0 0-2.9 3c0 2 1.6 3.7 3.5 3.7H15c3.3 0 6-2.7 6-6V11zM11.5 7a1 1 0 0 1 2 0v5h-2V7zm7.5 10c0 2.2-1.8 4-4 4H9.5c-1 0-1.8-.8-1.8-1.7v-7.6a1 1 0 0 1 1-1h1.8v2.3c0 .6.4 1 1 1s1-.4 1-1V8.5h1.5a1 1 0 0 1 1 1V11c0 .6.4 1 1 1s1-.4 1-1v-1a1 1 0 0 1 1-1h1.5a1 1 0 0 1 1 1v7z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Floating instructions box with quick action button -->
                    <div class="mt-6 bg-slate-900/90 backdrop-blur-md text-white px-5 py-3 rounded-2xl text-xs font-bold shadow-2xl flex items-center gap-3 border border-slate-700 max-w-[280px]">
                        <span class="leading-relaxed">Desliza para mover entre columnas</span>
                        <button @click.stop="dismissTutorial" class="text-xs font-black uppercase text-[#FF7A00] border border-[#FF7A00] px-2.5 py-1 rounded-xl hover:bg-[#FF7A00] hover:text-white transition-all duration-300">
                            Ok
                        </button>
                    </div>
                </div>
            </div>

            <div ref="kanbanRef"
                class="h-[calc(100vh-260px)] lg:h-[calc(100vh-180px)] overflow-x-auto no-scrollbar pb-10 select-none"
                :class="isGrabbing ? 'cursor-grabbing' : 'cursor-grab'" @mousedown="onKanbanMouseDown"
                @mousemove="onKanbanMouseMove" @mouseup="onKanbanMouseUp" @mouseleave="onKanbanMouseUp">
                <!-- Kanban Board -->
                <div class="flex gap-4 sm:gap-6 min-w-max h-full items-start">

                    <!-- Column -->
                    <div v-for="col in columns" :key="col.id"
                        class="kanban-column w-[82vw] max-w-[290px] sm:max-w-none sm:w-[300px] md:w-[320px] shrink-0 h-full flex flex-col rounded-[2.2rem] transition-colors duration-300 relative border border-slate-200/60 bg-white/50 backdrop-blur-sm shadow-[inset_0_2px_4px_rgba(0,0,0,0.01),0_4px_20px_rgba(0,0,0,0.02)] p-3 pb-5"
                        :class="[
                            currentHoverColumn === col.id ? 'border-[#FF7A00] bg-[#FF7A00]/5 ring-2 ring-[#FF7A00]/10' : ''
                        ]" @dragover="(e) => onDragOver(e, col.id)" @drop="() => onDrop(col.id)">
                        <!-- Header -->
                        <div class="px-2 py-2.5 mb-2 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 text-lg tracking-tight">
                                {{ col.title }}
                            </h3>
                            <span
                                class="text-[10px] font-bold text-white bg-slate-800 px-3 py-1 rounded-full shadow-sm">
                                {{ kanban[col.id]?.length || 0 }}
                            </span>
                        </div>

                        <!-- Cards Container -->
                        <div class="flex-1 overflow-y-auto space-y-4 no-scrollbar px-1">
                            <div v-for="order in kanban[col.id]" :key="order.id" draggable="true"
                                @dragstart="onDragStart(order, col.id)" @click="openModal(order.id)"
                                class="bg-white/90 backdrop-blur-md p-5 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white cursor-pointer hover:shadow-lg transition-all duration-300 touch-none select-none relative"
                                :class="{
                                    'opacity-50 scale-95': draggedItem?.id === order.id,
                                    'ring-2 ring-[#FF7A00] bg-orange-50/50': order.isNew
                                }">
                                <!-- Etiqueta Flotante Estado / OT -->
                                <div
                                    class="absolute top-4 left-4 bg-[#E2EAF4] text-slate-600 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full z-10">
                                    #OT-{{ order.id }}
                                </div>

                                <!-- Drag Handle Sutil -->
                                <div class="absolute top-4 right-4 text-slate-300" @click.stop>
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button type="button"
                                                class="rounded-full p-1 transition-colors hover:bg-slate-100 hover:text-slate-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 8h16M4 16h16" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <div class="flex flex-col py-2">
                                                <Link
                                                    :href="route('work-orders.show', { ...tenantRouteParams, workOrder: order.id })"
                                                    class="px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                                    Ver detalle OT
                                                </Link>
                                                <Link v-if="commercialReportsEnabled && order.vehicle?.client?.id"
                                                    :href="route('clients.show', { ...tenantRouteParams, client: order.vehicle.client.id })"
                                                    class="px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                                    Ver reporte cliente
                                                </Link>
                                                <button v-if="canDeleteWorkOrder" type="button"
                                                    class="w-full text-left px-4 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 border-t border-slate-50 mt-1 pt-2"
                                                    @click="confirmDeleteWorkOrder(order)">
                                                    Eliminar OT
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>

                                <div class="mt-8 mb-4">
                                    <div class="flex items-center gap-2">
                                        <p class="text-3xl font-black font-mono text-slate-800 tracking-wider">
                                            {{ order.vehicle?.plate || 'S/P' }}
                                        </p>
                                        <span v-if="commercialQuotesEnabled && order.quote?.status"
                                            :class="['rounded-full border px-2 py-1 text-[9px] font-black uppercase tracking-widest', formatQuoteStatusClasses(order.quote.status)]">
                                            {{ formatQuoteStatusLabel(order.quote.status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-1 mb-2">
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ order.vehicle?.brand || 'Marca' }} {{ order.vehicle?.model || 'Modelo' }}
                                    </p>
                                    <p class="text-xs font-medium text-slate-500 flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ order.vehicle?.client?.name || 'Cliente' }}
                                    </p>
                                </div>

                                <!-- O.T. Number and Date Base -->
                                <div
                                    class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-400">
                                    <span class="bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">{{ new
                                        Date(order.created_at).toLocaleDateString() }}</span>
                                    <div
                                        class="w-8 h-8 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-md hover:bg-orange-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>


                            <!-- Empty Placeholder for easy dropping -->
                            <div v-if="!kanban[col.id]?.length"
                                class="h-full min-h-[150px] w-full border-2 border-dashed border-slate-300 rounded-[2rem] flex items-center justify-center text-slate-400">
                                Arrastra aquí
                            </div>
                        </div>
                    </div>
            </div>
        </div>

            <!-- Indicador de Navegación por Pasos (Dots) para Móvil -->
            <div class="lg:hidden flex flex-col items-center gap-3 mt-4 px-4 py-3 bg-white/90 backdrop-blur-md rounded-[2rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] select-none">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-700 bg-slate-100 px-3 py-1.5 rounded-full flex items-center gap-1.5 transition-all duration-300">
                    <span class="text-[#FF7A00]">Paso {{ activeColumnIndex + 1 }} de {{ columns.length }}:</span>
                    <span>{{ columns[activeColumnIndex]?.title }}</span>
                    <span class="ml-1 text-[9px] bg-slate-800 text-white px-2 py-0.5 rounded-full font-bold">
                        {{ kanban[columns[activeColumnIndex]?.id]?.length || 0 }} {{ (kanban[columns[activeColumnIndex]?.id]?.length || 0) === 1 ? 'OT' : 'OTs' }}
                    </span>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        v-for="(col, index) in columns" 
                        :key="col.id"
                        @click="scrollToColumn(index)"
                        class="h-2.5 transition-all duration-300 rounded-full focus:outline-none relative flex items-center justify-center"
                        :class="[
                            activeColumnIndex === index 
                                ? 'w-8 bg-gradient-to-r from-orange-400 to-[#FF7A00] shadow-sm shadow-orange-500/20' 
                                : 'w-2.5 bg-slate-200 hover:bg-slate-300'
                        ]"
                        :aria-label="`Ir a columna ${col.title}`"
                    >
                        <!-- Pequeño contador sobre el dot inactivo si hay ítems -->
                        <span 
                            v-if="activeColumnIndex !== index && (kanban[col.id]?.length || 0) > 0"
                            class="absolute -top-3 text-[8px] font-bold text-slate-400"
                        >
                            {{ kanban[col.id]?.length }}
                        </span>
                    </button>
                </div>
            </div>
        </div> <!-- closes v-if="viewMode === 'kanban'" -->

        <!-- List View -->
        <div v-else class="space-y-6">
            <div
                class="overflow-hidden rounded-[2rem] border border-white bg-white/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                <div v-if="!orders?.data || orders.data.length === 0" class="p-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50">
                        <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">No se encontraron órdenes</h3>
                    <p class="mt-1 text-sm text-slate-500 font-medium">Prueba limpiando o cambiando los filtros de
                        búsqueda.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-6 py-4">OT</th>
                                <th class="px-6 py-4">Patente</th>
                                <th class="px-6 py-4">Vehículo</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Fecha de Ingreso</th>
                                <th class="px-6 py-4">Estado Taller</th>
                                <th class="px-6 py-4">Cotización</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50">
                            <tr v-for="order in orders.data" :key="order.id"
                                class="group transition-colors hover:bg-slate-50/50 cursor-pointer"
                                @click="openModal(order.id)">
                                <!-- OT Number -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-[#E2EAF4] text-slate-600">
                                        #OT-{{ order.id }}
                                    </span>
                                </td>

                                <!-- Plate -->
                                <td class="px-6 py-4">
                                    <span class="text-lg font-black font-mono text-slate-800 tracking-wider">
                                        {{ order.vehicle?.plate || 'S/P' }}
                                    </span>
                                </td>

                                <!-- Vehicle details -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-700">
                                        {{ order.vehicle?.brand || 'Marca' }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-medium">
                                        {{ order.vehicle?.model || 'Modelo' }}
                                    </div>
                                </td>

                                <!-- Client -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ order.vehicle?.client?.name || 'Cliente' }}
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 text-sm font-semibold text-slate-500">
                                    {{ new Date(order.created_at).toLocaleDateString('es-CL', {
                                        day: '2-digit', month:
                                    'short',
                                    year: 'numeric' }) }}
                                </td>

                                <!-- Workshop Status -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600">
                                        {{ getStatusLabel(order.status) }}
                                    </span>
                                </td>

                                <!-- Quote Status -->
                                <td class="px-6 py-4">
                                    <span v-if="commercialQuotesEnabled && order.quote?.status"
                                        :class="['rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-widest', formatQuoteStatusClasses(order.quote.status)]">
                                        {{ formatQuoteStatusLabel(order.quote.status) }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400 font-medium">Sin cotización</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(order.id)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors"
                                            title="Ver resumen">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <Link
                                            :href="route('work-orders.show', { ...tenantRouteParams, workOrder: order.id })"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 hover:bg-[#FF7A00] text-white transition-colors"
                                            title="Ir al detalle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                        <button v-if="canDeleteWorkOrder" type="button"
                                            @click="confirmDeleteWorkOrder(order)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-50 hover:bg-red-100 text-red-600 transition-colors"
                                            title="Eliminar OT">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination links -->
                <div v-if="orders?.links && orders.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-slate-100 px-6 py-4">
                    <template v-for="(link, i) in orders.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="link.active ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-slate-400"></span>
                    </template>
                </div>
            </div>
        </div>
        <!-- Modal OT -->
        <div v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300"
            @click.self="closeModal">
            <div
                class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-300">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div v-if="selectedWorkOrder" class="flex items-center gap-4">
                        <div>
                            <Link
                                :href="route('work-orders.show', { ...tenantRouteParams, workOrder: selectedWorkOrder.id })"
                                class="group inline-block">
                                <h2
                                    class="text-2xl font-black text-slate-800 group-hover:text-[#FF7A00] group-hover:underline transition-colors">
                                    OT #{{ selectedWorkOrder.id }} — {{ selectedWorkOrder.vehicle?.plate }}
                                </h2>
                            </Link>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{
                                selectedWorkOrder.vehicle?.brand }} {{ selectedWorkOrder.vehicle?.model
                                }}</p>
                        </div>
                        <span v-if="commercialQuotesEnabled && selectedWorkOrder.quote?.status"
                            :class="['rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', formatQuoteStatusClasses(selectedWorkOrder.quote.status)]">
                            {{ formatQuoteStatusLabel(selectedWorkOrder.quote.status) }}
                        </span>
                        <button @click="previewQuote"
                            class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 transition-colors shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00]" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Vista Previa
                        </button>
                    </div>
                    <div v-else class="animate-pulse flex space-x-4">
                        <div class="h-8 bg-slate-200 rounded w-48"></div>
                    </div>
                    <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Tabs -->
                <div class="flex border-b border-slate-100">
                    <button @click="activeTab = 'budget'"
                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                        :class="activeTab === 'budget' ? 'text-[#FF7A00] border-b-2 border-[#FF7A00] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                        Presupuesto
                    </button>
                    <button @click="activeTab = 'evidence'"
                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                        :class="activeTab === 'evidence' ? 'text-[#FF7A00] border-b-2 border-[#FF7A00] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                        Evidencia Fotográfica
                    </button>
                    <button @click="activeTab = 'preview'"
                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                        :class="activeTab === 'preview' ? 'text-[#FF7A00] border-b-2 border-[#FF7A00] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                        Vista Previa (Cliente)
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-8 no-scrollbar">
                    <div v-if="isLoadingModal" class="flex items-center justify-center h-64">
                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#FF7A00] border-t-transparent">
                        </div>
                    </div>

                    <div v-else-if="selectedWorkOrder">
                        <!-- Budget Section -->
                        <div v-if="activeTab === 'budget' && commercialQuotesEnabled"
                            class="space-y-8 animate-in slide-in-from-left-4 duration-500">
                            <div
                                class="flex flex-col gap-4 rounded-[2rem] border border-slate-200 bg-white p-5 md:flex-row md:items-start md:justify-between">
                                <div class="space-y-2">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Estado comercial
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span
                                            :class="['rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', formatQuoteStatusClasses(selectedWorkOrder.quote?.status ?? 'draft')]">
                                            {{ formatQuoteStatusLabel(selectedWorkOrder.quote?.status ??
                                                'draft') }}
                                        </span>
                                        <span v-if="selectedWorkOrder.quote?.sent_at"
                                            class="text-xs font-semibold text-slate-500">
                                            Enviada: {{ new
                                                Date(selectedWorkOrder.quote.sent_at).toLocaleString('es-CL')
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="selectedWorkOrder.quote?.status !== 'accepted'"
                                    class="max-w-sm rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold leading-relaxed text-amber-700">
                                    Si esta OT sale de Recepción sin cotización aceptada, el sistema
                                    mostrará una advertencia
                                    antes de continuar.
                                </p>
                            </div>

                            <div class="bg-slate-50 rounded-[2rem] p-6">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="text-left text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                                            <th class="pb-3">Descripción</th>
                                            <th class="pb-3">Tipo</th>
                                            <th class="pb-3 text-center">Cant</th>
                                            <th class="pb-3 text-right">Precio</th>
                                            <th class="pb-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="item in selectedWorkOrder.items" :key="item.id">
                                            <td class="py-4 font-semibold text-slate-700">{{
                                                item.description }}</td>
                                            <td class="py-4">
                                                <span
                                                    class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                                    {{ quoteItemTypeLabels[item.item_type] ??
                                                        item.item_type }}
                                                </span>
                                            </td>
                                            <td class="py-4 text-center text-slate-500 font-mono">{{
                                                item.quantity }}</td>
                                            <td class="py-4 text-right text-slate-500 font-mono">{{
                                                formatCurrency(item.unit_price) }}</td>
                                            <td class="py-4 text-right font-bold text-slate-800 font-mono">
                                                <div class="flex items-center justify-end gap-3">
                                                    {{ formatCurrency(item.total_price) }}
                                                    <button @click="removeItem(item.id)"
                                                        class="p-1.5 text-red-400 hover:text-red-600 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-slate-200">
                                            <td colspan="4"
                                                class="pt-4 text-right text-xs font-black uppercase text-slate-400">
                                                Total OT</td>
                                            <td class="pt-4 text-right text-xl font-black text-slate-900 font-mono">
                                                {{
                                                    formatCurrency(selectedWorkOrder.total_amount) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Add Item Form -->
                            <div class="bg-white border-2 border-dashed border-slate-200 rounded-[2rem] p-6">
                                <div class="flex flex-col gap-5">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <h4
                                                class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">
                                                Agregar a la cotización</h4>
                                            <p class="mt-2 text-sm font-semibold text-slate-700">Elige
                                                un repuesto del
                                                inventario o un servicio activo.</p>
                                        </div>
                                        <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                                            <button type="button" @click="setItemMode('product')"
                                                :class="itemMode === 'product' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                class="rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest transition-all">
                                                Repuesto
                                            </button>
                                            <button type="button" @click="setItemMode('service')"
                                                :class="itemMode === 'service' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                class="rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest transition-all">
                                                Servicio
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end gap-4 md:flex-row">
                                        <div class="w-full flex-1">
                                            <p class="mb-1 ml-2 text-[8px] font-bold uppercase text-slate-400">
                                                {{ itemMode === 'product' ? 'Buscar repuesto' : 'Buscar servicio' }}
                                            </p>
                                            <select v-if="itemMode === 'product'" v-model="itemForm.product_id"
                                                class="w-full cursor-pointer rounded-xl border-none bg-slate-50 py-3.5 text-xs font-bold focus:ring-2 focus:ring-[#FF7A00]">
                                                <option value="" disabled>Seleccionar repuesto...
                                                </option>
                                                <option v-for="product in availableProducts" :key="product.id"
                                                    :value="product.id">
                                                    {{ product.name }} (Stock: {{ product.physical_stock
                                                    }}) — {{
                                                        formatCurrency(product.selling_price) }}
                                                </option>
                                            </select>
                                            <select v-else v-model="itemForm.service_id"
                                                class="w-full cursor-pointer rounded-xl border-none bg-slate-50 py-3.5 text-xs font-bold focus:ring-2 focus:ring-[#FF7A00]">
                                                <option value="" disabled>Seleccionar servicio...
                                                </option>
                                                <option v-for="service in availableServices" :key="service.id"
                                                    :value="service.id">
                                                    {{ service.name }} — {{
                                                        formatCurrency(service.selling_price) }}
                                                </option>
                                            </select>
                                            <p v-if="itemErrors.product_id"
                                                class="ml-1 mt-2 text-[10px] font-semibold text-rose-500">
                                                {{
                                                    itemErrors.product_id[0] }}</p>
                                            <p v-if="itemErrors.service_id"
                                                class="ml-1 mt-2 text-[10px] font-semibold text-rose-500">
                                                {{
                                                    itemErrors.service_id[0] }}</p>
                                            <p v-if="itemMode === 'product' && availableProducts.length === 0"
                                                class="ml-1 mt-2 text-[10px] font-semibold text-slate-400">
                                                No hay repuestos con stock disponible.
                                            </p>
                                            <p v-if="itemMode === 'service' && availableServices.length === 0"
                                                class="ml-1 mt-2 text-[10px] font-semibold text-slate-400">
                                                No hay servicios activos disponibles.
                                            </p>
                                        </div>

                                        <div class="w-full md:w-32">
                                            <p class="mb-1 ml-2 text-[8px] font-bold uppercase text-slate-400">
                                                Cant.</p>
                                            <input v-model.number="itemForm.quantity" type="number" min="1"
                                                class="w-full rounded-xl border-none bg-slate-50 py-3.5 text-xs font-mono font-bold focus:ring-2 focus:ring-[#FF7A00]" />
                                            <p v-if="itemErrors.quantity"
                                                class="ml-1 mt-2 text-[10px] font-semibold text-rose-500">
                                                {{
                                                    itemErrors.quantity[0] }}</p>
                                        </div>

                                        <button @click="addItem"
                                            class="w-full whitespace-nowrap rounded-xl bg-slate-900 px-8 py-4 text-[10px] font-black uppercase tracking-widest text-white shadow-lg transition-all hover:bg-[#FF7A00] active:scale-95 md:w-auto">
                                            Agregar ítem
                                        </button>
                                    </div>

                                    <p v-if="itemErrors.description" class="text-[10px] font-semibold text-rose-500">{{
                                        itemErrors.description[0] }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'budget' && !commercialQuotesEnabled"
                            class="rounded-[2rem] border border-dashed border-orange-200 bg-orange-50/70 p-8 animate-in slide-in-from-left-4 duration-500">
                            <span
                                class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                            <h3 class="mt-4 text-xl font-black text-slate-900">Cotizaciones comerciales
                                no disponibles</h3>
                            <p class="mt-2 text-sm font-medium text-slate-600">
                                Este tablero puede mover órdenes, pero el presupuesto formal con
                                aprobación del cliente está
                                reservado para planes superiores.
                            </p>
                            <p class="mt-3 text-sm font-semibold text-orange-700">
                                {{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}
                            </p>
                        </div>

                        <!-- Evidence Section -->
                        <div v-if="activeTab === 'evidence'"
                            class="space-y-8 animate-in slide-in-from-right-4 duration-500">
                            <!-- Image Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="image in selectedWorkOrder.images" :key="image.id"
                                    class="aspect-square rounded-3xl overflow-hidden bg-slate-100 relative group shadow-sm">
                                    <img :src="'/media/' + image.image_path"
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />

                                    <!-- Delete Button -->
                                    <button @click.stop="deletePhoto(image.id)"
                                        class="absolute top-2 right-2 p-2 bg-red-500/80 backdrop-blur-md text-white rounded-xl opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 shadow-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>

                                    <div v-if="image.notes"
                                        class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4 pointer-events-none">
                                        <p class="text-[10px] text-white font-medium">{{ image.notes }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Upload Button -->
                                <label
                                    class="aspect-square rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:bg-orange-50/50 transition-colors group">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-[#FF7A00] transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Añadir
                                        Foto</span>
                                    <input type="file" accept="image/*" capture="camera" class="hidden"
                                        @change="uploadPhoto" />
                                </label>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div v-if="activeTab === 'preview' && commercialQuotesEnabled"
                            class="animate-in zoom-in-95 duration-500">
                            <div
                                class="max-w-xl mx-auto bg-white rounded-[2.5rem] p-8 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100">
                                <div class="mb-6 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Acciones de
                                            envío</p>
                                        <p class="mt-1 text-sm font-medium text-slate-500">
                                            Abre el detalle completo para enviar la cotización o avisar
                                            al administrador.
                                        </p>
                                    </div>

                                    <Link
                                        :href="route('work-orders.show', { ...tenantRouteParams, workOrder: selectedWorkOrder.id })"
                                        class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-700 transition-colors hover:bg-slate-50">
                                        Ir al detalle
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </div>

                                <WorkOrderQuote :workOrder="selectedWorkOrder" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Work Order Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>

            <div
                class="relative w-full max-w-md overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white p-6 shadow-2xl animate-in zoom-in-95 duration-300">
                <div class="text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">¿Eliminar Orden de Trabajo?
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 font-medium">Esta acción no se puede deshacer de forma
                        sencilla. Por
                        favor confirma los datos de la OT a eliminar:</p>
                </div>

                <div
                    class="mt-5 rounded-2xl bg-gray-50 p-4 space-y-2.5 text-xs text-gray-700 font-medium border border-gray-100">
                    <div class="flex justify-between">
                        <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Número de OT:</span>
                        <span class="font-bold text-gray-900">#OT-{{ workOrderToDelete?.id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Fecha de
                            Ingreso:</span>
                        <span class="font-bold text-gray-900">{{ workOrderToDelete ? new
                            Date(workOrderToDelete.created_at).toLocaleDateString('es-CL') : '' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Cliente:</span>
                        <span class="font-bold text-gray-900">{{ workOrderToDelete?.vehicle?.client?.name || 'No registrado' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Patente:</span>
                        <span class="font-mono font-bold text-gray-900 tracking-wider">{{
                            workOrderToDelete?.vehicle?.plate ||
                            'S/P' }}</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="button"
                        class="flex-1 rounded-2xl bg-gray-100 py-3.5 text-xs font-black uppercase tracking-widest text-gray-600 transition-colors hover:bg-gray-200"
                        @click="showDeleteModal = false">Cancelar</button>
                    <button type="button"
                        class="flex-1 rounded-2xl bg-red-600 py-3.5 text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-red-700"
                        @click="submitDeleteWorkOrder">Eliminar OT</button>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>

<style scoped>
/* Oculta scrollbars nativas pero mantiene la funcionalidad para UI más limpia */
.custom-scrollbar::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 20px;
}

@keyframes swipe-tutorial-card-animation {
    0% {
        transform: translateX(0);
        opacity: 0;
    }
    8% {
        transform: translateX(0);
        opacity: 1;
    }
    18% {
        transform: translateX(0);
        opacity: 1;
    }
    68% {
        transform: translateX(180px);
        opacity: 1;
    }
    80% {
        transform: translateX(180px);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 0;
    }
}

.animate-swipe-tutorial-card {
    animation: swipe-tutorial-card-animation 3.5s ease-in-out infinite;
}

@keyframes swipe-tutorial-hand-animation {
    0% {
        transform: scale(1.1);
        opacity: 0;
    }
    8% {
        transform: scale(1.1);
        opacity: 1;
    }
    15% {
        transform: scale(0.85); /* grab card */
    }
    68% {
        transform: scale(0.85);
    }
    76% {
        transform: scale(1.1); /* release card */
    }
    80% {
        transform: scale(1.1);
        opacity: 0;
    }
    100% {
        transform: scale(1.1);
        opacity: 0;
    }
}

.animate-swipe-tutorial-hand {
    animation: swipe-tutorial-hand-animation 3.5s ease-in-out infinite;
}
</style>
