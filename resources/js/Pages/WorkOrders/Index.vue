<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import axios from 'axios';
import WorkOrderQuote from '@/Components/WorkOrderQuote.vue';

const props = defineProps({
    kanban: Object,
    tenantId: Number
});

// Modal state
const isModalOpen = ref(false);
const selectedWorkOrder = ref(null);
const isLoadingModal = ref(false);
const activeTab = ref('budget'); // 'budget' or 'evidence'

// Form for adding items
const itemForm = ref({
    description: '',
    quantity: 1,
    unit_price: 0
});

const openModal = async (orderId) => {
    isModalOpen.value = true;
    isLoadingModal.value = true;
    selectedWorkOrder.value = null;

    try {
        const response = await axios.get(route('api.work-orders.show', orderId));
        selectedWorkOrder.value = response.data;
    } catch (error) {
        console.error('Error fetching work order details:', error);
    } finally {
        isLoadingModal.value = false;
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedWorkOrder.value = null;
};

const addItem = () => {
    if (!selectedWorkOrder.value) return;

    router.post(route('work-orders.items.store', selectedWorkOrder.value.id), itemForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            // Refresh modal data
            openModal(selectedWorkOrder.value.id);
            itemForm.value = { description: '', quantity: 1, unit_price: 0 };
        }
    });
};

const uploadPhoto = async (event) => {
    const file = event.target.files[0];
    if (!file || !selectedWorkOrder.value) return;

    const formData = new FormData();
    formData.append('image', file);

    try {
        await axios.post(route('api.work-orders.images.upload', selectedWorkOrder.value.id), formData, {
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
        await axios.delete(route('api.work-orders.images.destroy', imageId));
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
    { id: 'listo', title: 'Listo para Entrega', color: 'bg-green-100/50' },
];

const draggedItem = ref(null);
const currentHoverColumn = ref(null);

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
        // Enviar la petición update vía Inertia sin recargas visuales (preserveScroll)
        router.put(route('work-orders.status.update', draggedItem.value.id), {
            status: newStatus
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // Notificación o éxito manejado a través de Inertia (flash message usualmente)
            }
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
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`tenant.${props.tenantId}.work-orders`);
        window.Echo.leave(`taller.${props.tenantId}`);
    }
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

        <div class="h-[calc(100vh-220px)] lg:h-[calc(100vh-140px)] overflow-x-auto no-scrollbar pb-10">
            <!-- Kanban Board -->
            <div class="flex gap-6 min-w-max h-full items-start">
                
                <!-- Column -->
                <div 
                    v-for="col in columns" 
                    :key="col.id"
                    class="w-[300px] md:w-[320px] shrink-0 h-full flex flex-col rounded-[2rem] transition-colors duration-300 relative border border-transparent"
                    :class="[
                        currentHoverColumn === col.id ? 'border-[#F9A826] bg-[#F9A826]/5' : ''
                    ]"
                    @dragover="(e) => onDragOver(e, col.id)"
                    @drop="() => onDrop(col.id)"
                >
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
                        <div 
                            v-for="order in kanban[col.id]" 
                            :key="order.id"
                            draggable="true"
                            @dragstart="onDragStart(order, col.id)"
                            @click="openModal(order.id)"
                            class="bg-white/90 backdrop-blur-md p-5 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white cursor-pointer hover:shadow-lg transition-all duration-300 touch-none select-none relative"
                            :class="{ 
                                'opacity-50 scale-95': draggedItem?.id === order.id,
                                'ring-2 ring-[#F9A826] bg-orange-50/50': order.isNew 
                            }"
                        >
                            <!-- Etiqueta Flotante Estado / OT -->
                            <div class="absolute top-4 left-4 bg-[#E2EAF4] text-slate-600 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full z-10">
                                #OT-{{ order.id }}
                            </div>

                            <!-- Drag Handle Sutil -->
                            <div class="absolute top-4 right-4 text-slate-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>

                            <div class="mt-8 mb-4">
                                <p class="text-3xl font-black font-mono text-slate-800 tracking-wider">
                                    {{ order.vehicle?.plate || 'S/P' }}
                                </p>
                            </div>

                            <div class="space-y-1 mb-2">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ order.vehicle?.brand || 'Marca' }} {{ order.vehicle?.model || 'Modelo' }}
                                </p>
                                <p class="text-xs font-medium text-slate-500 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#F9A826]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ order.vehicle?.client?.name || 'Cliente' }}
                                </p>
                            </div>

                            <!-- O.T. Number and Date Base -->
                            <div class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-400">
                                <span class="bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">{{ new Date(order.created_at).toLocaleDateString() }}</span>
                                <div
                                    class="w-8 h-8 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-md hover:bg-orange-500 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Modal -->
                        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300" @click.self="closeModal">
                            <div class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-300">
                                <!-- Modal Header -->
                                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div v-if="selectedWorkOrder" class="flex items-center gap-4">
                                        <div>
                                            <h2 class="text-2xl font-black text-slate-800">OT #{{ selectedWorkOrder.id }} — {{ selectedWorkOrder.vehicle?.plate }}</h2>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ selectedWorkOrder.vehicle?.brand }} {{ selectedWorkOrder.vehicle?.model }}</p>
                                        </div>
                                        <button 
                                            @click="previewQuote"
                                            class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 transition-colors shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#F9A826]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            Vista Previa
                                        </button>
                                    </div>
                                    <div v-else class="animate-pulse flex space-x-4">
                                        <div class="h-8 bg-slate-200 rounded w-48"></div>
                                    </div>
                                    <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <!-- Modal Tabs -->
                                <div class="flex border-b border-slate-100">
                                    <button 
                                        @click="activeTab = 'budget'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'budget' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'"
                                    >
                                        Presupuesto
                                    </button>
                                    <button 
                                        @click="activeTab = 'evidence'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'evidence' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'"
                                    >
                                        Evidencia Fotográfica
                                    </button>
                                    <button 
                                        @click="activeTab = 'preview'"
                                        class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-all"
                                        :class="activeTab === 'preview' ? 'text-[#F9A826] border-b-2 border-[#F9A826] bg-orange-50/30' : 'text-slate-400 hover:text-slate-600'"
                                    >
                                        Vista Previa (Cliente)
                                    </button>
                                </div>

                                <!-- Modal Body -->
                                <div class="flex-1 overflow-y-auto p-8 no-scrollbar">
                                    <div v-if="isLoadingModal" class="flex items-center justify-center h-64">
                                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-[#F9A826] border-t-transparent"></div>
                                    </div>

                                    <div v-else-if="selectedWorkOrder">
                                        <!-- Budget Section -->
                                        <div v-if="activeTab === 'budget'" class="space-y-8 animate-in slide-in-from-left-4 duration-500">
                                            <div class="bg-slate-50 rounded-[2rem] p-6">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                                                            <th class="pb-3">Descripción</th>
                                                            <th class="pb-3 text-center">Cant</th>
                                                            <th class="pb-3 text-right">Precio</th>
                                                            <th class="pb-3 text-right">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="item in selectedWorkOrder.items" :key="item.id">
                                                            <td class="py-4 font-semibold text-slate-700">{{ item.description }}</td>
                                                            <td class="py-4 text-center text-slate-500 font-mono">{{ item.quantity }}</td>
                                                            <td class="py-4 text-right text-slate-500 font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                                            <td class="py-4 text-right font-bold text-slate-800 font-mono">{{ formatCurrency(item.total_price) }}</td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="border-t-2 border-slate-200">
                                                            <td colspan="3" class="pt-4 text-right text-xs font-black uppercase text-slate-400">Total</td>
                                                            <td class="pt-4 text-right text-xl font-black text-slate-900 font-mono">{{ formatCurrency(selectedWorkOrder.total_amount) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <!-- Add Item Form -->
                                            <div class="bg-white border-2 border-dashed border-slate-200 rounded-[2rem] p-6">
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Añadir Ítem</h4>
                                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                    <div class="md:col-span-2">
                                                        <input v-model="itemForm.description" type="text" placeholder="Descripción" class="w-full bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-[#F9A826]" />
                                                    </div>
                                                    <div>
                                                        <input v-model.number="itemForm.quantity" type="number" placeholder="Cant" class="w-full bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-[#F9A826]" />
                                                    </div>
                                                    <div>
                                                        <input v-model.number="itemForm.unit_price" type="number" placeholder="Precio" class="w-full bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-[#F9A826]" />
                                                    </div>
                                                </div>
                                                <button @click="addItem" class="mt-4 w-full bg-slate-900 text-white font-black text-[10px] uppercase tracking-[0.2em] py-4 rounded-xl hover:bg-[#F9A826] transition-colors shadow-lg shadow-slate-200">
                                                    Añadir al Presupuesto
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Evidence Section -->
                                        <div v-if="activeTab === 'evidence'" class="space-y-8 animate-in slide-in-from-right-4 duration-500">
                                            <!-- Image Grid -->
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                                <div v-for="image in selectedWorkOrder.images" :key="image.id" class="aspect-square rounded-3xl overflow-hidden bg-slate-100 relative group shadow-sm">
                                                    <img :src="'/media/' + image.image_path" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                                    
                                                    <!-- Delete Button -->
                                                    <button 
                                                        @click.stop="deletePhoto(image.id)" 
                                                        class="absolute top-2 right-2 p-2 bg-red-500/80 backdrop-blur-md text-white rounded-xl opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 shadow-lg"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>

                                                    <div v-if="image.notes" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4 pointer-events-none">
                                                        <p class="text-[10px] text-white font-medium">{{ image.notes }}</p>
                                                    </div>
                                                </div>

                                                <!-- Upload Button -->
                                                <label class="aspect-square rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:bg-orange-50/50 transition-colors group">
                                                    <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-[#F9A826] transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                    </div>
                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Añadir Foto</span>
                                                    <input type="file" accept="image/*" capture="camera" class="hidden" @change="uploadPhoto" />
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Preview Section -->
                                        <div v-if="activeTab === 'preview'" class="animate-in zoom-in-95 duration-500">
                                            <div class="max-w-xl mx-auto bg-white rounded-[2.5rem] p-8 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100">
                                                <WorkOrderQuote :workOrder="selectedWorkOrder" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty Placeholder for easy dropping -->
                        <div v-if="!kanban[col.id]?.length" class="h-full min-h-[150px] w-full border-2 border-dashed border-slate-300 rounded-[2rem] flex items-center justify-center text-slate-400">
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
