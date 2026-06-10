<script setup>
import { router } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

const props = defineProps({
    show: Boolean,
    workOrder: Object,
});

const emit = defineEmits(['update:show']);

const close = () => {
    emit('update:show', false);
};

const submitDeleteWorkOrder = () => {
    router.delete(route('work-orders.destroy', { ...tenantRouteParams.value, workOrder: props.workOrder.id }), {
        onSuccess: close,
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="close"></div>

        <div class="relative w-full max-w-md overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white p-6 shadow-2xl animate-in zoom-in-95 duration-300">
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">¿Eliminar Orden de Trabajo?</h3>
                <p class="mt-2 text-sm text-gray-500 font-medium">Esta acción no se puede deshacer de forma sencilla. Por favor confirma los datos de la OT a eliminar:</p>
            </div>

            <div class="mt-5 rounded-2xl bg-gray-50 p-4 space-y-2.5 text-xs text-gray-700 font-medium border border-gray-100">
                <div class="flex justify-between">
                    <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Número de OT:</span>
                    <span class="font-bold text-gray-900">#OT-{{ workOrder.id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Fecha de Ingreso:</span>
                    <span class="font-bold text-gray-900">{{ new Date(workOrder.created_at).toLocaleDateString('es-CL') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Cliente:</span>
                    <span class="font-bold text-gray-900">{{ workOrder.vehicle?.client?.name || 'No registrado' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400 uppercase tracking-widest text-[9px] font-bold">Patente:</span>
                    <span class="font-mono font-bold text-gray-900 tracking-wider">{{ workOrder.vehicle?.plate || 'S/P' }}</span>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" class="flex-1 rounded-2xl bg-gray-100 py-3.5 text-xs font-black uppercase tracking-widest text-gray-600 transition-colors hover:bg-gray-200" @click="close">Cancelar</button>
                <button type="button" class="flex-1 rounded-2xl bg-red-600 py-3.5 text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-red-700" @click="submitDeleteWorkOrder">Eliminar OT</button>
            </div>
        </div>
    </div>
</template>
