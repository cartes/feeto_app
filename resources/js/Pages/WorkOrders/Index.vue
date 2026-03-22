<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    kanban: Object,
    tenantId: Number
});

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
        window.Echo.channel(`tenant.${props.tenantId}.work-orders`)
            .listen('WorkOrderDraftCreated', (e) => {
                const newOrder = e.workOrder;
                if (!props.kanban[newOrder.status]) {
                    props.kanban[newOrder.status] = [];
                }
                props.kanban[newOrder.status].unshift(newOrder);
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leaveChannel(`tenant.${props.tenantId}.work-orders`);
    }
});
</script>

<template>
    <TallerLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900">
                Tablero de Órdenes
            </h2>
        </template>

        <div class="h-[calc(100vh-200px)] lg:h-[calc(100vh-120px)] overflow-x-auto custom-scrollbar">
            <!-- Kanban Board -->
            <div class="flex gap-4 min-w-max h-full items-start px-2 py-2">
                
                <!-- Column -->
                <div 
                    v-for="col in columns" 
                    :key="col.id"
                    class="w-[320px] md:w-[350px] shrink-0 h-full flex flex-col rounded-2xl border-2 transition-colors duration-200"
                    :class="[
                        col.color,
                        currentHoverColumn === col.id ? 'border-orange-400 border-dashed shadow-inner' : 'border-transparent'
                    ]"
                    @dragover="(e) => onDragOver(e, col.id)"
                    @drop="() => onDrop(col.id)"
                >
                    <!-- Header -->
                    <div class="px-5 py-4 border-b border-gray-200/50 bg-white/40 rounded-t-2xl">
                        <h3 class="font-bold text-gray-800 text-lg uppercase tracking-wide">
                            {{ col.title }}
                            <span class="ml-2 text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200 shadow-sm">
                                {{ kanban[col.id]?.length || 0 }}
                            </span>
                        </h3>
                    </div>

                    <!-- Cards Container -->
                    <div class="flex-1 p-3 overflow-y-auto space-y-4">
                        <div 
                            v-for="order in kanban[col.id]" 
                            :key="order.id"
                            draggable="true"
                            @dragstart="onDragStart(order, col.id)"
                            class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow touch-none select-none relative"
                            :class="{ 'opacity-50 scale-95': draggedItem?.id === order.id }"
                        >
                            <!-- Visual Drag Handle -->
                            <div class="absolute top-4 right-4 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>

                            <p class="text-3xl font-bold font-mono text-gray-900 tracking-wider mb-2">
                                {{ order.vehicle?.plate || 'S/P' }}
                            </p>

                            <div class="space-y-1 mb-4">
                                <p class="text-base font-semibold text-gray-800">
                                    {{ order.vehicle?.brand || 'Marca' }} {{ order.vehicle?.model || 'Modelo' }}
                                </p>
                                <p class="text-sm font-medium text-gray-500 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ order.vehicle?.client?.name || 'Cliente' }}
                                </p>
                            </div>

                            <!-- O.T. Number and Date -->
                            <div class="flex items-center justify-between text-xs text-gray-400 font-semibold uppercase tracking-wider border-t border-gray-100 pt-3">
                                <span>#OT-{{ order.id }}</span>
                                <span>{{ new Date(order.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>

                        <!-- Empty Placeholder for easy dropping -->
                        <div v-if="!kanban[col.id]?.length" class="h-full min-h-[150px] w-full border-2 border-dashed border-gray-300 rounded-2xl flex items-center justify-center text-gray-400">
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
