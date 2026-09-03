<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';

const { tenantRouteParams } = useTenantRouting();
const { formatCurrency } = useFormatting();

const props = defineProps({
    show: Boolean,
    item: {
        type: Object,
        default: null,
    },
    taxName: {
        type: String,
        default: 'IVA',
    },
    defaultTaxRate: {
        type: Number,
        default: 19,
    },
});

const emit = defineEmits(['update:show']);

const catalogForm = useForm({
    name: '',
    sku: '',
    type: 'repuesto_nacional',
    description: '',
    cost_price: 0,
    selling_price: 0,
    tax_included: false,
    physical_stock: 0,
    min_stock: 0,
    code: '',
    estimated_minutes: 0,
    is_active: true,
});

const editingCatalogType = computed(() => props.item?.item_type ?? null);

watch(() => props.item, (item) => {
    if (!item) {
        return;
    }

    if (item.item_type === 'product') {
        catalogForm.name = item.product.name;
        catalogForm.sku = item.product.sku;
        catalogForm.type = item.product.type || 'repuesto_nacional';
        catalogForm.description = item.product.description || '';
        catalogForm.cost_price = Number(item.product.cost_price || 0);
        catalogForm.selling_price = Number(item.product.selling_price || 0);
        catalogForm.tax_included = Boolean(item.product.tax_included);
        catalogForm.physical_stock = Number(item.product.physical_stock || 0);
        catalogForm.min_stock = Number(item.product.min_stock || 0);
    } else if (item.item_type === 'service') {
        catalogForm.name = item.service.name;
        catalogForm.code = item.service.code || '';
        catalogForm.description = item.service.description || '';
        catalogForm.cost_price = Number(item.service.cost_price || 0);
        catalogForm.selling_price = Number(item.service.selling_price || 0);
        catalogForm.tax_included = Boolean(item.service.tax_included);
        catalogForm.estimated_minutes = Number(item.service.estimated_minutes || 0);
        catalogForm.is_active = Boolean(item.service.is_active);
    }

    catalogForm.clearErrors();
});

const close = () => {
    emit('update:show', false);
};

const setPriceTaxIncluded = (isIncluded) => {
    if (catalogForm.tax_included === isIncluded) return;

    const currentPrice = Number(catalogForm.selling_price) || 0;
    const rate = Number(props.defaultTaxRate) || 19;

    if (currentPrice > 0) {
        if (isIncluded) {
            catalogForm.selling_price = Math.round(currentPrice * (1 + (rate / 100)));
        } else {
            catalogForm.selling_price = Math.round(currentPrice / (1 + (rate / 100)));
        }
    }
    catalogForm.tax_included = isIncluded;
};

const submitCatalogEdit = () => {
    if (editingCatalogType.value === 'product') {
        catalogForm.put(route('inventory.update', { ...tenantRouteParams.value, product: props.item.product.id }), {
            preserveScroll: true,
            onSuccess: close,
        });
    } else if (editingCatalogType.value === 'service') {
        catalogForm.put(route('services.update', { ...tenantRouteParams.value, service: props.item.service.id }), {
            preserveScroll: true,
            onSuccess: close,
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="close"></div>

        <div class="relative w-full max-w-lg overflow-y-auto rounded-[2.5rem] border border-gray-100 bg-white shadow-[0_32px_64px_rgba(0,0,0,0.1)] max-h-[90vh] animate-in zoom-in-95 duration-300">
            <div class="flex items-center justify-between border-b border-gray-50 bg-gray-50/50 p-6 lg:p-8">
                <div>
                    <h2 class="text-2xl font-black uppercase tracking-tight text-gray-900">
                        {{ editingCatalogType === 'product' ? 'Editar Repuesto' : 'Editar Servicio' }}
                    </h2>
                    <p class="mt-1 text-xs font-medium text-gray-400">Edita la información del catálogo general.</p>
                </div>
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-600"
                    @click="close"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form class="space-y-6 p-6 lg:p-8" @submit.prevent="submitCatalogEdit">
                <div class="space-y-1.5">
                    <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Nombre</label>
                    <input v-model="catalogForm.name" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                    <p v-if="catalogForm.errors.name" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.name }}</p>
                </div>

                <template v-if="editingCatalogType === 'product'">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">SKU</label>
                            <input v-model="catalogForm.sku" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 font-mono text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.sku" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.sku }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Tipo de Repuesto</label>
                            <select v-model="catalogForm.type" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]">
                                <option value="repuesto_nacional">Repuesto Nacional</option>
                                <option value="repuesto_internacional">Repuesto Internacional</option>
                                <option value="insumo">Insumo</option>
                            </select>
                            <p v-if="catalogForm.errors.type" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.type }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Stock Físico</label>
                            <input v-model.number="catalogForm.physical_stock" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.physical_stock" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.physical_stock }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Stock Mínimo</label>
                            <input v-model.number="catalogForm.min_stock" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.min_stock" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.min_stock }}</p>
                        </div>
                    </div>
                </template>

                <template v-if="editingCatalogType === 'service'">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Código</label>
                            <input v-model="catalogForm.code" type="text" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 font-mono text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.code" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.code }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Duración Estimada (min)</label>
                            <input v-model.number="catalogForm.estimated_minutes" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.estimated_minutes" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.estimated_minutes }}</p>
                        </div>
                    </div>
                </template>

                <!-- Precios e Impuesto -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Precios y {{ taxName }}</label>
                        <!-- Selector Tipo de Precio -->
                        <div class="inline-flex rounded-xl bg-gray-100 p-0.5 border border-gray-200">
                            <button
                                type="button"
                                class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                :class="!catalogForm.tax_included ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                @click="setPriceTaxIncluded(false)"
                            >
                                + {{ taxName }} (Neto)
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                :class="catalogForm.tax_included ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                @click="setPriceTaxIncluded(true)"
                            >
                                Con {{ taxName }} (Bruto)
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Costo ($)</label>
                            <input v-model.number="catalogForm.cost_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.cost_price" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.cost_price }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">
                                Precio Venta ($) <span class="text-[#FF7A00]">{{ catalogForm.tax_included ? `(Con ${taxName})` : `(+ ${taxName})` }}</span>
                            </label>
                            <input v-model.number="catalogForm.selling_price" type="number" min="0" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-bold text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="catalogForm.errors.selling_price" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.selling_price }}</p>
                            <p v-if="Number(catalogForm.selling_price) > 0" class="text-[11px] font-semibold text-gray-400 ml-1">
                                <span v-if="catalogForm.tax_included">
                                    Neto estimado: <strong class="text-gray-700 font-mono">{{ formatCurrency(Math.round(catalogForm.selling_price / (1 + (defaultTaxRate / 100)))) }}</strong>
                                </span>
                                <span v-else>
                                    Total con {{ taxName }} ({{ defaultTaxRate }}%): <strong class="text-orange-600 font-mono">{{ formatCurrency(Math.round(catalogForm.selling_price * (1 + (defaultTaxRate / 100)))) }}</strong>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="ml-1 block text-[9px] font-bold uppercase tracking-widest text-gray-400">Descripción</label>
                    <textarea v-model="catalogForm.description" rows="3" class="w-full rounded-2xl border border-gray-300 px-5 py-3.5 text-sm font-medium text-gray-900 shadow-sm outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-[#FF7A00]" placeholder="Detalle opcional"></textarea>
                    <p v-if="catalogForm.errors.description" class="ml-1 text-[10px] font-medium text-red-500">{{ catalogForm.errors.description }}</p>
                </div>

                <label v-if="editingCatalogType === 'service'" class="flex items-center justify-between rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Servicio activo</span>
                    <input v-model="catalogForm.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#FF7A00] focus:ring-[#FF7A00]" />
                </label>

                <button type="submit" class="w-full rounded-2xl bg-gray-900 py-3.5 text-sm font-black uppercase tracking-wide text-white transition-colors hover:bg-[#FF7A00]" :disabled="catalogForm.processing">
                    {{ catalogForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </form>
        </div>
    </div>
</template>
