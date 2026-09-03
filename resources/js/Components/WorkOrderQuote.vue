<script setup>
import { computed } from 'vue';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    workOrder: Object,
    ufValue: {
        type: Number,
        default: null,
    },
    taxName: {
        type: String,
        default: 'IVA',
    },
});

const { formatCurrency, formatDate, formatUf: formatUfRaw } = useFormatting();

const quote = computed(() => props.workOrder?.quote ?? props.workOrder ?? { items: [], subtotal_amount: 0, total_amount: 0 });

const subtotalAmount = computed(() => Number(quote.value.subtotal_amount ?? props.workOrder?.total_amount ?? 0));
const applyTax = computed(() => Boolean(quote.value.apply_tax ?? false));
const taxRate = computed(() => Number(quote.value.tax_rate ?? 0));
const taxAmount = computed(() => applyTax.value ? Number(quote.value.tax_amount ?? 0) : 0);
const finalTotalAmount = computed(() => {
    if (quote.value.total_amount !== undefined && quote.value.total_amount !== null && Number(quote.value.total_amount) > 0) {
        return Number(quote.value.total_amount);
    }
    return applyTax.value ? subtotalAmount.value + taxAmount.value : subtotalAmount.value;
});

const formatUf = (clpValue) => formatUfRaw(clpValue, props.ufValue);
</script>

<template>
    <div class="space-y-6">
        <!-- Header de Cotización -->
        <div class="flex justify-between items-start border-b border-slate-100 pb-6">
            <div>
                <h3 class="text-xl font-black text-slate-800">Cotización #{{ workOrder.id }}</h3>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ formatDate(quote.sent_at || workOrder.created_at) }}</p>
            </div>
            <div class="text-right px-4 py-2 bg-orange-50 rounded-2xl border border-orange-100">
                <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest leading-none">Total Presupuestado</p>
                <p class="text-xl font-black text-slate-900 mt-1 leading-none">
                    {{ formatCurrency(finalTotalAmount) }}
                </p>
                <p v-if="formatUf(finalTotalAmount)" class="text-[10px] font-bold text-orange-400 mt-0.5">
                    ≈ UF {{ formatUf(finalTotalAmount) }}
                </p>
                <p v-if="applyTax" class="text-[9px] font-bold text-orange-600 mt-1">
                    Incluye {{ taxName }} ({{ taxRate }}%)
                </p>
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
                    <tr v-for="item in quote.items" :key="item.id">
                        <td class="px-4 py-4 text-slate-700 font-medium">{{ item.description }}</td>
                        <td class="px-4 py-4 text-center text-slate-500 font-mono">{{ item.quantity }}</td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-slate-800 font-black font-mono">{{ formatCurrency(item.total_price) }}</span>
                            <span v-if="formatUf(item.total_price)" class="block text-[10px] font-medium text-slate-400 font-mono">
                                UF {{ formatUf(item.total_price) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
                <!-- Resumen de Totales y Desglose de IVA -->
                <tfoot class="border-t border-slate-100 bg-slate-50/50">
                    <tr>
                        <td colspan="2" class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500">Subtotal (Neto):</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-700">{{ formatCurrency(subtotalAmount) }}</td>
                    </tr>
                    <tr v-if="applyTax">
                        <td colspan="2" class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500">{{ taxName }} ({{ taxRate }}%):</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold text-orange-600">+ {{ formatCurrency(taxAmount) }}</td>
                    </tr>
                    <tr class="border-t border-slate-200 bg-orange-50/30">
                        <td colspan="2" class="px-4 py-3 text-right text-xs font-black uppercase tracking-wider text-slate-900">Total a Pagar:</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-mono text-base font-black text-slate-900">{{ formatCurrency(finalTotalAmount) }}</span>
                            <span v-if="formatUf(finalTotalAmount)" class="block text-[10px] font-medium text-slate-400 font-mono">UF {{ formatUf(finalTotalAmount) }}</span>
                        </td>
                    </tr>
                </tfoot>
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
