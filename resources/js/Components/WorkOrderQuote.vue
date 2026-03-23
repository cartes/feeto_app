<script setup>
const props = defineProps({
    workOrder: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-CL', {
        day: '2-digit', month: 'long', year: 'numeric'
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(value);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header de Cotización -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-6">
            <div>
                <h3 class="text-xl font-black text-slate-800">Cotización #{{ workOrder.id }}</h3>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ formatDate(workOrder.created_at) }}</p>
            </div>
            <div class="text-right px-4 py-2 bg-orange-50 rounded-2xl border border-orange-100">
                <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest leading-none">Total
                    Presupuestado</p>
                <p class="text-xl font-black text-slate-900 mt-1 leading-none">{{ formatCurrency(workOrder.total_amount)
                    }}</p>
            </div>
        </div>

        <!-- Tabla de Ítems -->
        <div class="overflow-hidden rounded-2xl border border-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Descripción</th>
                        <th
                            class="px-4 py-3 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Cant</th>
                        <th
                            class="px-4 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="item in workOrder.items" :key="item.id">
                        <td class="px-4 py-4 text-slate-700 font-medium">{{ item.description }}</td>
                        <td class="px-4 py-4 text-center text-slate-500 font-mono">{{ item.quantity }}</td>
                        <td class="px-4 py-4 text-right text-slate-800 font-black font-mono">
                            {{ formatCurrency(item.total_price) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Advertencia Importante -->
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex gap-3">
            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-[11px] text-slate-500 leading-relaxed italic">
                Este documento es una pre-visualización informativa. Los valores pueden estar sujetos a cambios según
                hallazgos técnicos adicionales durante el proceso de reparación.
            </p>
        </div>
    </div>
</template>
