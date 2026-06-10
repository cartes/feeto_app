<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useFormatting } from '@/composables/useFormatting';

const { formatCurrency } = useFormatting();

const props = defineProps({
    workOrderId: [Number, String],
    products: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    discountPolicy: Object,
});

const selectedMode = ref('manual');
const selectedProduct = ref(null);
const selectedService = ref(null);
const productSearch = ref('');
const serviceSearch = ref('');
const showProductDropdown = ref(false);
const showServiceDropdown = ref(false);

const addForm = useForm({
    product_id: null,
    service_id: null,
    description: '',
    quantity: 1,
    unit_price: '',
    discount_percent: 0,
});

const hasSelectedProduct = computed(() => selectedProduct.value !== null);
const hasSelectedService = computed(() => selectedService.value !== null);
const hasManualInput = computed(() => selectedMode.value === 'manual' && (addForm.description !== '' || addForm.unit_price !== ''));

const clearProduct = () => {
    selectedProduct.value = null;
    productSearch.value = '';
    addForm.product_id = null;
    addForm.description = '';
    addForm.unit_price = '';
};

const clearService = () => {
    selectedService.value = null;
    serviceSearch.value = '';
    addForm.service_id = null;
    addForm.description = '';
    addForm.unit_price = '';
};

watch(productSearch, (newVal) => {
    if (!newVal && selectedProduct.value) {
        clearProduct();
    }
});

watch(serviceSearch, (newVal) => {
    if (!newVal && selectedService.value) {
        clearService();
    }
});

const filteredProducts = computed(() => {
    if (!productSearch.value) {
        return props.products;
    }

    const query = productSearch.value.toLowerCase();

    return props.products.filter((product) => (
        product.name.toLowerCase().includes(query)
        || product.sku?.toLowerCase().includes(query)
    ));
});

const filteredServices = computed(() => {
    if (!serviceSearch.value) {
        return props.services;
    }

    const query = serviceSearch.value.toLowerCase();

    return props.services.filter((service) => (
        service.name.toLowerCase().includes(query)
        || service.code?.toLowerCase().includes(query)
    ));
});

const previewSubtotal = computed(() => Number(addForm.quantity || 0) * Number(addForm.unit_price || 0));
const previewDiscountAmount = computed(() => previewSubtotal.value * (Number(addForm.discount_percent || 0) / 100));
const previewTotal = computed(() => previewSubtotal.value - previewDiscountAmount.value);

const selectMode = (mode) => {
    selectedMode.value = mode;
    addForm.clearErrors();

    if (mode !== 'product') {
        selectedProduct.value = null;
        productSearch.value = '';
        addForm.product_id = null;
    }

    if (mode !== 'service') {
        selectedService.value = null;
        serviceSearch.value = '';
        addForm.service_id = null;
    }

    if (mode === 'manual') {
        addForm.description = '';
        addForm.unit_price = '';
    }
};

const selectProduct = (product) => {
    selectedMode.value = 'product';
    selectedProduct.value = product;
    selectedService.value = null;
    serviceSearch.value = '';
    addForm.service_id = null;
    addForm.product_id = product.id;
    addForm.description = product.name;
    addForm.unit_price = product.selling_price;
    productSearch.value = product.name;
    showProductDropdown.value = false;
};

const selectService = (service) => {
    selectedMode.value = 'service';
    selectedService.value = service;
    selectedProduct.value = null;
    productSearch.value = '';
    addForm.product_id = null;
    addForm.service_id = service.id;
    addForm.description = service.name;
    addForm.unit_price = service.selling_price;
    serviceSearch.value = service.name;
    showServiceDropdown.value = false;
};

const resetForm = () => {
    addForm.reset();
    addForm.quantity = 1;
    addForm.discount_percent = 0;
    selectedProduct.value = null;
    selectedService.value = null;
    productSearch.value = '';
    serviceSearch.value = '';
    selectedMode.value = 'manual';
};

const submitItem = () => {
    addForm.post(route('work-orders.items.store', props.workOrderId), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Agregar Ítem</h2>
            <Link :href="route('services.index')" class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00] hover:text-[#CC6200]">Gestionar Servicios</Link>
        </div>

        <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Política de descuentos</p>
            <p class="mt-1.5 text-sm font-medium text-amber-900">
                Hasta {{ discountPolicy?.threshold ?? 0 }}% sin aprobación. Sobre ese umbral requiere rol {{ (discountPolicy?.approver_roles || []).join(' o ') }}.
            </p>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <button
                type="button"
                class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                :class="selectedMode === 'manual' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                :disabled="hasSelectedProduct || hasSelectedService"
                @click="selectMode('manual')"
            >
                Manual
            </button>
            <button
                type="button"
                class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                :class="selectedMode === 'product' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                :disabled="hasManualInput || hasSelectedService"
                @click="selectMode('product')"
            >
                Repuesto
            </button>
            <button
                type="button"
                class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                :class="selectedMode === 'service' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                :disabled="hasManualInput || hasSelectedProduct"
                @click="selectMode('service')"
            >
                Servicio
            </button>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="submitItem">
            <div v-if="selectedMode === 'product'" class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Buscar Repuesto</label>
                <div class="relative">
                    <input v-model="productSearch" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300 pr-10" placeholder="Nombre o SKU" @focus="showProductDropdown = true" @blur="setTimeout(() => { showProductDropdown = false; }, 200)" />
                    <button
                        v-if="hasSelectedProduct"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        @click="clearProduct"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div v-if="showProductDropdown && filteredProducts.length" class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
                        <button v-for="product in filteredProducts.slice(0, 8)" :key="product.id" type="button" class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-orange-50" @click="selectProduct(product)">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ product.name }}</p>
                                <p class="text-[10px] font-mono text-gray-400">{{ product.sku }} · Stock {{ product.physical_stock }}</p>
                            </div>
                            <span class="text-sm font-black text-orange-500">{{ formatCurrency(product.selling_price) }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="selectedMode === 'service'" class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Buscar Servicio</label>
                <div class="relative">
                    <input v-model="serviceSearch" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300 pr-10" placeholder="Nombre o código" @focus="showServiceDropdown = true" @blur="setTimeout(() => { showServiceDropdown = false; }, 200)" />
                    <button
                        v-if="hasSelectedService"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        @click="clearService"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div v-if="showServiceDropdown && filteredServices.length" class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
                        <button v-for="service in filteredServices.slice(0, 8)" :key="service.id" type="button" class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-orange-50" @click="selectService(service)">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ service.name }}</p>
                                <p class="text-[10px] font-mono text-gray-400">{{ service.code || 'Sin código' }} · {{ service.estimated_minutes }} min</p>
                            </div>
                            <span class="text-sm font-black text-orange-500">{{ formatCurrency(service.selling_price) }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Descripción</label>
                <input v-model="addForm.description" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300" placeholder="Ej: Cambio de aceite, diagnóstico eléctrico" />
                <p v-if="addForm.errors.description" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.description }}</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cantidad</label>
                    <input v-model.number="addForm.quantity" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300" />
                    <p v-if="addForm.errors.quantity" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.quantity }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">P. Unitario</label>
                    <input v-model.number="addForm.unit_price" type="number" min="0" step="1" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300" />
                    <p v-if="addForm.errors.unit_price" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.unit_price }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Desc. (%)</label>
                    <input v-model.number="addForm.discount_percent" type="number" min="0" max="100" step="0.01" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300" />
                    <p v-if="addForm.errors.discount_percent" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.discount_percent }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-orange-100 bg-orange-50 px-4 py-3">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-orange-500">Subtotal</span>
                    <p v-if="Number(addForm.discount_percent) > 0" class="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-rose-500">
                        Desc. {{ addForm.discount_percent }}% · ahorro {{ formatCurrency(previewDiscountAmount) }}
                    </p>
                </div>
                <div class="text-right">
                    <span v-if="Number(addForm.discount_percent) > 0" class="block text-xs font-semibold text-gray-400 line-through">{{ formatCurrency(previewSubtotal) }}</span>
                    <span class="text-base font-black text-orange-600">{{ formatCurrency(previewTotal) }}</span>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-gray-900 py-2.5 text-sm font-black text-white transition-colors hover:bg-gray-700 disabled:opacity-50" :disabled="addForm.processing">
                {{ addForm.processing ? 'Agregando...' : 'Agregar a Cotización' }}
            </button>
        </form>
    </div>
</template>
