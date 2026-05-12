<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import axios from 'axios';
import WorkOrderQuote from '@/Components/WorkOrderQuote.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    kanban: Object,
    tenantId: Number
});

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const commercialReportsEnabled = computed(() => planAccess.value?.commercial_reports_enabled ?? false);

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
        const orderResponse = await axios.get(route('api.work-orders.show', { id: orderId }));
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
        await axios.post(route('api.work-orders.items.store', { workOrder: selectedWorkOrder.value.id }), payload);
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
        await axios.post(route('api.work-orders.images.upload', { id: selectedWorkOrder.value.id }), formData, {
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
        await axios.delete(route('api.work-orders.images.destroy', { imageId }));
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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(value);
};

// Column identifiers and headers
const columns = [
    { id: 'recepcion', title: 'Recepción', color: 'bg-orange-100/50' },
    { id: 'diagnostico', title: 'En Diagnóstico', color: 'bg-blue-100/50' },
    { id: 'esperando_repuestos', title: 'Esp. Repuestos', color: 'bg-yellow-100/50' },
    { id: 'control_calidad', title: 'Control de Calidad', color: 'bg-cyan-100/50' },
    { id: 'listo', title: 'Listo para Entrega', color: 'bg-green-100/50' },
];

const draggedItem = ref(null);
const currentHoverColumn = ref(null);

// Kanban horizontal scroll
const kanbanRef = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(true);
let scrollInterval = null;

const updateScrollState = () => {
    if (!kanbanRef.value) return;
    canScrollLeft.value = kanbanRef.value.scrollLeft > 0;
    canScrollRight.value = kanbanRef.value.scrollLeft < kanbanRef.value.scrollWidth - kanbanRef.value.clientWidth - 1;
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
</script>

<template>
    <TallerLayout>

        <div class="mb-4">
            <h2 class="text-3xl font-bold leading-tight text-slate-800 tracking-tight">
                Tablero de Órdenes
            </h2>
            <p class="text-sm text-slate-500 font-medium">Gestión del flujo de trabajo</p>
        </div>

        <!-- Scroll arrows -->
        <div class="flex items-center gap-2 mb-3">
            <button @mousedown="startScroll('left')" @mouseup="stopScroll" @mouseleave="stopScroll"
                @touchstart.prevent="startScroll('left')" @touchend="stopScroll" :disabled="!canScrollLeft"
                class="flex items-center justify-center w-9 h-9 rounded-full bg-white shadow-sm border border-gray-100 transition-all duration-200"
                :class="canScrollLeft ? 'text-slate-700 hover:bg-[#F9A826] hover:text-white hover:border-[#F9A826] hover:shadow-md' : 'text-slate-300 cursor-not-allowed'"
                aria-label="Scroll izquierda">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @mousedown="startScroll('right')" @mouseup="stopScroll" @mouseleave="stopScroll"
                @touchstart.prevent="startScroll('right')" @touchend="stopScroll" :disabled="!canScrollRight"
                class="flex items-center justify-center w-9 h-9 rounded-full bg-white shadow-sm border border-gray-100 transition-all duration-200"
                :class="canScrollRight ? 'text-slate-700 hover:bg-[#F9A826] hover:text-white hover:border-[#F9A826] hover:shadow-md' : 'text-slate-300 cursor-not-allowed'"
                aria-label="Scroll derecha">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <div ref="kanbanRef"
            class="h-[calc(100vh-260px)] lg:h-[calc(100vh-180px)] overflow-x-auto no-scrollbar pb-10 select-none"
            :class="isGrabbing ? 'cursor-grabbing' : 'cursor-grab'" @mousedown="onKanbanMouseDown"
            @mousemove="onKanbanMouseMove" @mouseup="onKanbanMouseUp" @mouseleave="onKanbanMouseUp">
            <!-- Kanban Board -->
            <div class="flex gap-6 min-w-max h-full items-start">

                <!-- Column -->
                <div v-for="col in columns" :key="col.id"
                    class="w-[300px] md:w-[320px] shrink-0 h-full flex flex-col rounded-[2rem] transition-colors duration-300 relative border border-transparent"
                    :class="[
                        currentHoverColumn === col.id ? 'border-[#F9A826] bg-[#F9A826]/5' : ''
                    ]" @dragover="(e) => onDragOver(e, col.id)" @drop="() => onDrop(col.id)">
                    <!-- Header -->
                    <div class="px-5 py-4 mb-2 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700 text-lg tracking-tight">
                            {{ col.title }}
                        </h3>
                        <span class="text-[10px] font-bold text-white bg-slate-800 px-3 py-1 rounded-full shadow-sm">
                            {{ kanban[col.id]?.length || 0 }}
                        </span>
                    </div>

                    <!-- Cards Container -->
                    <div class="flex-1 overflow-y-auto space-y-5 no-scrollbar px-1">
                        <div v-for="order in kanban[col.id]" :key="order.id" draggable="true"
                            @dragstart="onDragStart(order, col.id)" @click="openModal(order.id)"
                            class="bg-white/90 backdrop-blur-md p-5 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white cursor-pointer hover:shadow-lg transition-all duration-300 touch-none select-none relative"
                            :class="{
                                'opacity-50 scale-95': draggedItem?.id === order.id,
                                'ring-2 ring-[#F9A826] bg-orange-50/50': order.isNew
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#F9A826]" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ order.vehicle?.client?.name || 'Cliente' }}
                                </p>
                            </div>

                            <!-- O.T. Number and Date Base -->
                            <div class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-400">
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

                        <!-- Modal -->
                        <div v-if="isModalOpen"
                            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300"
                            @click.self="closeModal">
                            <div
                                class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-300">
                                <!-- Modal Header -->
                                <div
                                    class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div v-if="selectedWorkOrder" class="flex items-center gap-4">
                                        <div>
                                            <Link
                                                :href="route('work-orders.show', { ...tenantRouteParams, workOrder: selectedWorkOrder.id })"
                                                class="group inline-block"
                                            >
                                                <h2 class="text-2xl font-black text-slate-800 group-hover:text-[#F9A826] group-hover:underline transition-colors">
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
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#F9A826]"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    <button @click="closeModal"
                                        class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Modal Tabs -->
                                <div class="flex border-b border-slate-100">
                                    <button @click="activeTab = 'budget'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'budget' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                                        Presupuesto
                                    </button>
                                    <button @click="activeTab = 'evidence'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'evidence' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                                        Evidencia Fotográfica
                                    </button>
                                    <button @click="activeTab = 'preview'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'preview' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'">
                                        Vista Previa (Cliente)
                                    </button>
                                </div>

                                <!-- Modal Body -->
                                <div class="flex-1 overflow-y-auto p-8 no-scrollbar">
                                    <div v-if="isLoadingModal" class="flex items-center justify-center h-64">
                                        <div
                                            class="animate-spin rounded-full h-12 w-12 border-4 border-[#F9A826] border-t-transparent">
                                        </div>
                                    </div>

                                    <div v-else-if="selectedWorkOrder">
                                        <!-- Budget Section -->
                                        <div v-if="activeTab === 'budget' && commercialQuotesEnabled"
                                            class="space-y-8 animate-in slide-in-from-left-4 duration-500">
                                            <div
                                                class="flex flex-col gap-4 rounded-[2rem] border border-slate-200 bg-white p-5 md:flex-row md:items-start md:justify-between">
                                                <div class="space-y-2">
                                                    <p
                                                        class="text-[10px] font-black uppercase tracking-widest text-slate-400">
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
                                                            <td
                                                                class="py-4 text-right font-bold text-slate-800 font-mono">
                                                                <div class="flex items-center justify-end gap-3">
                                                                    {{ formatCurrency(item.total_price) }}
                                                                    <button @click="removeItem(item.id)"
                                                                        class="p-1.5 text-red-400 hover:text-red-600 transition-colors">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-4 w-4" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M6 18L18 6M6 6l12 12" />
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
                                                            <td
                                                                class="pt-4 text-right text-xl font-black text-slate-900 font-mono">
                                                                {{
                                                                    formatCurrency(selectedWorkOrder.total_amount) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <!-- Add Item Form -->
                                            <div
                                                class="bg-white border-2 border-dashed border-slate-200 rounded-[2rem] p-6">
                                                <div class="flex flex-col gap-5">
                                                    <div
                                                        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                        <div>
                                                            <h4
                                                                class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">
                                                                Agregar a la cotización</h4>
                                                            <p class="mt-2 text-sm font-semibold text-slate-700">Elige
                                                                un repuesto del
                                                                inventario o un servicio activo.</p>
                                                        </div>
                                                        <div
                                                            class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
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
                                                            <p
                                                                class="mb-1 ml-2 text-[8px] font-bold uppercase text-slate-400">
                                                                {{ itemMode === 'product' ? 'Buscar repuesto' : 'Buscar servicio' }}
                                                            </p>
                                                            <select v-if="itemMode === 'product'"
                                                                v-model="itemForm.product_id"
                                                                class="w-full cursor-pointer rounded-xl border-none bg-slate-50 py-3.5 text-xs font-bold focus:ring-2 focus:ring-[#F9A826]">
                                                                <option value="" disabled>Seleccionar repuesto...
                                                                </option>
                                                                <option v-for="product in availableProducts"
                                                                    :key="product.id" :value="product.id">
                                                                    {{ product.name }} (Stock: {{ product.physical_stock
                                                                    }}) — {{
                                                                        formatCurrency(product.selling_price) }}
                                                                </option>
                                                            </select>
                                                            <select v-else v-model="itemForm.service_id"
                                                                class="w-full cursor-pointer rounded-xl border-none bg-slate-50 py-3.5 text-xs font-bold focus:ring-2 focus:ring-[#F9A826]">
                                                                <option value="" disabled>Seleccionar servicio...
                                                                </option>
                                                                <option v-for="service in availableServices"
                                                                    :key="service.id" :value="service.id">
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
                                                            <p
                                                                class="mb-1 ml-2 text-[8px] font-bold uppercase text-slate-400">
                                                                Cant.</p>
                                                            <input v-model.number="itemForm.quantity" type="number"
                                                                min="1"
                                                                class="w-full rounded-xl border-none bg-slate-50 py-3.5 text-xs font-mono font-bold focus:ring-2 focus:ring-[#F9A826]" />
                                                            <p v-if="itemErrors.quantity"
                                                                class="ml-1 mt-2 text-[10px] font-semibold text-rose-500">
                                                                {{
                                                                    itemErrors.quantity[0] }}</p>
                                                        </div>

                                                        <button @click="addItem"
                                                            class="w-full whitespace-nowrap rounded-xl bg-slate-900 px-8 py-4 text-[10px] font-black uppercase tracking-widest text-white shadow-lg transition-all hover:bg-[#F9A826] active:scale-95 md:w-auto">
                                                            Agregar ítem
                                                        </button>
                                                    </div>

                                                    <p v-if="itemErrors.description"
                                                        class="text-[10px] font-semibold text-rose-500">{{
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
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
                                                        class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-[#F9A826] transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                    </div>
                                                    <span
                                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Añadir
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
                                                        <p
                                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2.2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 5l7 7-7 7" />
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

                        <!-- Empty Placeholder for easy dropping -->
                        <div v-if="!kanban[col.id]?.length"
                            class="h-full min-h-[150px] w-full border-2 border-dashed border-slate-300 rounded-[2rem] flex items-center justify-center text-slate-400">
                            Arrastra aquí
                        </div>
                    </div>
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
</style>
