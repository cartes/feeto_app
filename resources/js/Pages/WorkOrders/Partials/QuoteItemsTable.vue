<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';
import { useStatusConfig } from '@/composables/useStatusConfig';

const { formatCurrency, formatUf: formatUfRaw } = useFormatting();
const { QUOTE_ITEM_TYPE_LABELS } = useStatusConfig();

const props = defineProps({
    workOrderId: [Number, String],
    items: {
        type: Array,
        default: () => [],
    },
    subtotalAmount: [Number, String],
    taxAmount: [Number, String],
    totalAmount: [Number, String],
    applyTax: {
        type: Boolean,
        default: false,
    },
    taxRate: [Number, String],
    taxName: {
        type: String,
        default: 'IVA',
    },
    ufValue: {
        type: Number,
        default: null,
    },
    canManageItems: Boolean,
    canManageInventory: Boolean,
});

const emit = defineEmits(['edit-catalog-item']);

const formatUf = (clpValue) => formatUfRaw(clpValue, props.ufValue);

const displayTotalAmount = computed(() => {
    if (props.totalAmount !== undefined && props.totalAmount !== null && Number(props.totalAmount) > 0) {
        return Number(props.totalAmount);
    }
    const sub = Number(props.subtotalAmount || 0);
    const tax = props.applyTax ? Number(props.taxAmount || 0) : 0;
    return sub + tax;
});

const removeItem = (itemId) => {
    router.delete(route('work-orders.items.destroy', { workOrder: props.workOrderId, item: itemId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-50 px-6 py-4">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Detalle Cotización</h2>
            <span class="text-[10px] font-bold text-gray-400">{{ items.length }} registros</span>
        </div>

        <div v-if="items.length === 0" class="flex flex-col items-center justify-center px-6 py-14 text-center">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50">
                <svg class="h-6 w-6 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-xs font-semibold uppercase tracking-tight text-gray-400">Aún no hay ítems en la cotización</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-50">
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Descripción</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Tipo</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Cant.</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">P. Unit.</th>
                        <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-gray-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ item.description }}</p>
                                <button
                                    v-if="canManageInventory && (item.item_type === 'product' || item.item_type === 'service')"
                                    type="button"
                                    class="text-gray-400 hover:text-[#FF7A00] transition-colors"
                                    title="Editar en catálogo"
                                    @click="emit('edit-catalog-item', item)"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="item.product?.sku" class="mt-0.5 text-[10px] font-mono text-gray-400">{{ item.product.sku }}</p>
                            <p v-if="item.service?.code" class="mt-0.5 text-[10px] font-mono text-gray-400">{{ item.service.code }}</p>
                            <p v-if="Number(item.discount_percent) > 0" class="mt-0.5 text-[10px] font-black uppercase tracking-widest text-rose-500">
                                Desc. {{ item.discount_percent }}% · ahorro {{ formatCurrency(item.discount_amount) }}
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                {{ QUOTE_ITEM_TYPE_LABELS[item.item_type] ?? item.item_type }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right text-sm font-medium tabular-nums text-gray-600">{{ item.quantity }}</td>
                        <td class="px-4 py-4 text-right text-sm font-medium tabular-nums text-gray-600">
                            <span v-if="Number(item.discount_percent) > 0" class="block text-[10px] text-gray-400 line-through">{{ formatCurrency(item.original_unit_price) }}</span>
                            <span>{{ formatCurrency(item.unit_price) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-sm font-black tabular-nums text-gray-900">{{ formatCurrency(item.total_price) }}</span>
                            <span v-if="formatUf(item.total_price)" class="block text-[10px] font-medium tabular-nums text-gray-400">UF {{ formatUf(item.total_price) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <button v-if="canManageItems" type="button" class="text-gray-300 transition-colors hover:text-rose-500" @click="removeItem(item.id)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-100 bg-gray-50/40">
                        <td colspan="4" class="px-6 py-2.5 text-right text-xs font-semibold text-gray-500">Subtotal (Neto):</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold text-gray-700">{{ formatCurrency(subtotalAmount) }}</td>
                        <td></td>
                    </tr>
                    <tr v-if="applyTax" class="border-t border-gray-50 bg-gray-50/40">
                        <td colspan="4" class="px-6 py-2.5 text-right text-xs font-semibold text-gray-500">{{ taxName }} ({{ taxRate }}%):</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold text-orange-600">+ {{ formatCurrency(taxAmount) }}</td>
                        <td></td>
                    </tr>
                    <tr class="border-t-2 border-gray-100 bg-gray-50/80">
                        <td colspan="4" class="px-6 py-4 text-right text-sm font-black uppercase tracking-wider text-gray-700">Total Cotización</td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-lg font-black tabular-nums text-gray-900">{{ formatCurrency(displayTotalAmount) }}</span>
                            <span v-if="formatUf(displayTotalAmount)" class="block text-[10px] font-semibold tabular-nums text-gray-400">UF {{ formatUf(displayTotalAmount) }}</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
