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
        console.log('[Echo] Suscribiendo Kanban a:', `tenant.${props.tenantId}.work-orders`);
        
        window.Echo.private(`tenant.${props.tenantId}.work-orders`)
            .listen('WorkOrderDraftCreated', (e) => {
                console.log('[Echo] Nueva OT recibida:', e);
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
            .listen('.WorkOrderStatusUpdated', (e) => {
                console.log('[Echo] Cambio de estado recibido:', e);
                // Aquí podrías implementar la lógica para mover el card en el Kanban si es necesario
                // Por ahora solo logueamos para confirmar conexión
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
                            class="bg-white/90 backdrop-blur-md p-5 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white cursor-grab active:cursor-grabbing hover:shadow-lg transition-all duration-300 touch-none select-none relative"
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

                            <!-- O.T. Number and Date Base (estilo botón pequeño a la vista adjunta) -->
                            <div class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-400">
                                <span class="bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">{{ new Date(order.created_at).toLocaleDateString() }}</span>
                                <button class="w-8 h-8 rounded-full bg-[#1C1C1E] text-white flex items-center justify-center shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </button>
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
