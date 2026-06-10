<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

const props = defineProps({
    tenant: Object,
});

const commercialForm = useForm({
    max_discount_without_approval: props.tenant?.max_discount_without_approval ?? 10,
});

const submitCommercialSettings = () => {
    commercialForm.patch(route('taller.settings.commercial.update', tenantRouteParams.value), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-5 animate-in fade-in duration-300">
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <form class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-5" @submit.prevent="submitCommercialSettings">
                <div>
                    <p class="text-sm font-black uppercase tracking-widest text-gray-500">Política de descuentos</p>
                    <h3 class="mt-2 text-2xl font-black text-gray-900">Aprobación comercial</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">
                        Define el porcentaje máximo que cualquier usuario puede aplicar sin aprobación superior.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Descuento máximo sin aprobación (%)</label>
                        <input
                            v-model="commercialForm.max_discount_without_approval"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                        />
                        <p v-if="commercialForm.errors.max_discount_without_approval" class="text-red-500 text-xs">
                            {{ commercialForm.errors.max_discount_without_approval }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                    <p class="text-xs font-black uppercase tracking-widest text-amber-600">Regla activa</p>
                    <p class="mt-2 text-sm font-medium text-amber-900">
                        Descuentos superiores a {{ commercialForm.max_discount_without_approval || 0 }}% requerirán rol <strong>Jefe</strong> o <strong>Supervisor</strong>.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="commercialForm.processing"
                        class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50"
                    >
                        {{ commercialForm.processing ? 'Guardando...' : 'Guardar Política Comercial' }}
                    </button>
                </div>
            </form>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <p class="text-sm font-black uppercase tracking-widest text-gray-500">Roles comerciales</p>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                        <p class="text-sm font-black text-gray-900">Supervisor</p>
                        <p class="mt-1 text-sm text-gray-500">Puede revisar descuentos altos, reportes y operación comercial del taller.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                        <p class="text-sm font-black text-gray-900">Jefe</p>
                        <p class="mt-1 text-sm text-gray-500">Puede autorizar descuentos superiores al umbral y supervisar cartera atrasada.</p>
                    </div>
                    <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-4">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">Plan actual</p>
                        <p class="mt-2 text-lg font-black text-gray-900">{{ tenant?.plan_label }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
