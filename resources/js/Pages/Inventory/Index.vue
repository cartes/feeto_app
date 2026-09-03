<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';
import { useDebounce } from '@/composables/useDebounce';

const page = usePage();
const { tenantRouteParams } = useTenantRouting();
const { formatCurrency, formatDateTime } = useFormatting();
const { debounce } = useDebounce();

const props = defineProps({
    products: Object,
    categories: Array,
    taxName: {
        type: String,
        default: 'IVA',
    },
    defaultTaxRate: {
        type: Number,
        default: 19,
    },
    filters: Object,
});

// Search & Filters
const search = ref(props.filters?.search || '');
const filterCategory = ref(props.filters?.category || '');
const filterType = ref(props.filters?.type || '');
const filterStockStatus = ref(props.filters?.stock_status || '');
const filterPriceMin = ref(props.filters?.price_min || '');
const filterPriceMax = ref(props.filters?.price_max || '');
const showFilters = ref(false);
const showImportModal = ref(false);
const importInput = ref(null);
const flash = computed(() => page.props.flash ?? {});
const importSummary = computed(() => flash.value.import_summary?.kind === 'products' ? flash.value.import_summary : null);
const visibleImportErrors = computed(() => importSummary.value?.errors?.slice(0, 5) ?? []);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterCategory.value) count++;
    if (filterType.value) count++;
    if (filterStockStatus.value) count++;
    if (filterPriceMin.value) count++;
    if (filterPriceMax.value) count++;
    return count;
});

const applyFilters = () => {
    router.get(route('inventory.index', tenantRouteParams.value), {
        search: search.value || undefined,
        category: filterCategory.value || undefined,
        type: filterType.value || undefined,
        stock_status: filterStockStatus.value || undefined,
        price_min: filterPriceMin.value || undefined,
        price_max: filterPriceMax.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filterCategory.value = '';
    filterType.value = '';
    filterStockStatus.value = '';
    filterPriceMin.value = '';
    filterPriceMax.value = '';
    applyFilters();
};

watch(search, debounce(() => applyFilters(), 300));
watch([filterCategory, filterType, filterStockStatus], () => applyFilters());
watch([filterPriceMin, filterPriceMax], debounce(() => applyFilters(), 500));

// Product Modal
const showModal = ref(false);
const editingProduct = ref(null);
const showDeleteConfirm = ref(null);

const form = useForm({
    name: '',
    sku: '',
    description: '',
    category_id: null,
    cost_price: 0,
    selling_price: 0,
    tax_included: false,
    physical_stock: 0,
    min_stock: 0,
});

const importForm = useForm({
    workbook: null,
});

const modalTitle = computed(() => editingProduct.value ? 'Editar Repuesto' : 'Nuevo Repuesto');

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    form.tax_included = false;
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.name = product.name;
    form.sku = product.sku;
    form.description = product.description || '';
    form.category_id = product.category_id || null;
    form.cost_price = product.cost_price;
    form.selling_price = product.selling_price;
    form.tax_included = Boolean(product.tax_included);
    form.physical_stock = product.physical_stock;
    form.min_stock = product.min_stock;
    form.clearErrors();
    showModal.value = true;
};

const handleSubmit = () => {
    if (editingProduct.value) {
        form.put(route('inventory.update', { ...tenantRouteParams.value, product: editingProduct.value.id }), {
            onSuccess: () => { showModal.value = false; },
            preserveScroll: true,
        });
    } else {
        form.post(route('inventory.store', tenantRouteParams.value), {
            onSuccess: () => { showModal.value = false; },
            preserveScroll: true,
        });
    }
};

const handleDelete = (product) => {
    router.delete(route('inventory.destroy', { ...tenantRouteParams.value, product: product.id }), {
        preserveScroll: true,
        onSuccess: () => { showDeleteConfirm.value = null; },
    });
};

// Category Modal
const showCategoryModal = ref(false);
const editingCategory = ref(null);

const categoryForm = useForm({
    name: '',
});

const openCreateCategoryModal = () => {
    editingCategory.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
    editingCategory.value = cat;
    categoryForm.name = cat.name;
    categoryForm.clearErrors();
    showCategoryModal.value = true;
};

const handleCategorySubmit = () => {
    if (editingCategory.value) {
        categoryForm.put(route('product-categories.update', { ...tenantRouteParams.value, productCategory: editingCategory.value.id }), {
            onSuccess: () => { showCategoryModal.value = false; },
            preserveScroll: true,
        });
    } else {
        categoryForm.post(route('product-categories.store', tenantRouteParams.value), {
            onSuccess: () => { showCategoryModal.value = false; },
            preserveScroll: true,
        });
    }
};

const handleCategoryDelete = (cat) => {
    if (!confirm(`¿Eliminar la categoría "${cat.name}"? Los productos quedarán sin categoría.`)) return;
    router.delete(route('product-categories.destroy', { ...tenantRouteParams.value, productCategory: cat.id }), {
        preserveScroll: true,
    });
};

// Movements Modal
const showMovementsModal = ref(false);
const movementsProduct = ref(null);
const movements = ref([]);
const movementsLoading = ref(false);
const movementsPagination = ref(null);

const movementTypeBadge = (type) => {
    const map = {
        entry: { label: 'Entrada', class: 'bg-green-50 text-green-700 border-green-200' },
        exit: { label: 'Salida', class: 'bg-red-50 text-red-700 border-red-200' },
        adjustment: { label: 'Ajuste', class: 'bg-blue-50 text-blue-700 border-blue-200' },
        reservation: { label: 'Reserva', class: 'bg-amber-50 text-amber-700 border-amber-200' },
        release: { label: 'Liberación', class: 'bg-gray-50 text-gray-600 border-gray-200' },
    };
    return map[type] || { label: type, class: 'bg-gray-50 text-gray-600 border-gray-200' };
};

const openMovementsModal = async (product) => {
    movementsProduct.value = product;
    movements.value = [];
    movementsPagination.value = null;
    showMovementsModal.value = true;
    await loadMovements(route('inventory.movements', { ...tenantRouteParams.value, product: product.id }));
};

const loadMovements = async (url) => {
    movementsLoading.value = true;
    try {
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();
        movements.value = data.data;
        movementsPagination.value = data;
    } finally {
        movementsLoading.value = false;
    }
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();

    if (importInput.value) {
        importInput.value.value = '';
    }
};

const handleImportFileChange = (event) => {
    importForm.workbook = event.target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(route('inventory.import', tenantRouteParams.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeImportModal(),
    });
};

</script>

<template>
    <Head title="Inventario de Stock" />

    <TallerLayout>
        <!-- Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Inventario</h1>
                <p class="text-sm font-medium text-gray-500 mt-1">Gestiona los repuestos e insumos del taller.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative w-full md:w-80" data-tour="inventory-search">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Buscar por nombre o SKU..."
                        class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/50 focus:border-[#FF7A00] transition-all shadow-sm" />
                </div>
                <!-- Filter Toggle -->
                <button @click="showFilters = !showFilters"
                    class="relative flex-shrink-0 p-3 rounded-2xl border transition-all shadow-sm"
                    :class="showFilters || activeFilterCount ? 'bg-[#FF7A00]/10 border-[#FF7A00] text-[#FF7A00]' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span v-if="activeFilterCount"
                        class="absolute -top-1 -right-1 w-5 h-5 bg-[#FF7A00] text-white text-[10px] font-black rounded-full flex items-center justify-center">
                        {{ activeFilterCount }}
                    </span>
                </button>
                <!-- Categories -->
                <button @click="openCreateCategoryModal"
                    class="flex-shrink-0 p-3 rounded-2xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition-all shadow-sm"
                    title="Gestionar categorías">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </button>
                <button @click="showImportModal = true"
                    class="flex-shrink-0 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                    Importar Excel
                </button>
                <button @click="openCreateModal" data-tour="inventory-add"
                    class="flex-shrink-0 bg-[#FF7A00] hover:bg-[#CC6200] text-white px-5 py-3 rounded-2xl font-bold text-sm shadow-sm transition-all active:scale-95 flex items-center gap-2 uppercase tracking-wide">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar
                </button>
            </div>
        </div>

        <div v-if="importSummary" class="mb-6 rounded-[2rem] border border-amber-200 bg-amber-50/70 p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Ultima importacion</p>
                    <h2 class="mt-2 text-lg font-black uppercase tracking-tight text-gray-900">Repuestos</h2>
                    <p class="mt-1 text-sm font-medium text-gray-600">
                        {{ importSummary.processed_rows }} filas procesadas, {{ importSummary.skipped_rows }} omitidas y {{ importSummary.error_rows }} con error.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Repuestos nuevos</p>
                        <p class="mt-1 text-2xl font-black text-gray-900">{{ importSummary.created_products }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Repuestos actualizados</p>
                        <p class="mt-1 text-2xl font-black text-gray-900">{{ importSummary.updated_products }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Categorias creadas</p>
                        <p class="mt-1 text-2xl font-black text-gray-900">{{ importSummary.created_categories }}</p>
                    </div>
                </div>
            </div>

            <div v-if="visibleImportErrors.length" class="mt-5 rounded-2xl border border-amber-200 bg-white p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Filas con observaciones</p>
                <div class="mt-3 space-y-2">
                    <div v-for="error in visibleImportErrors" :key="`${error.row}-${error.message}`"
                        class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-medium text-gray-700">
                        <span class="font-black text-amber-700">Fila {{ error.row }}:</span> {{ error.message }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div v-if="showFilters" class="mb-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Filtros</h3>
                <button v-if="activeFilterCount" @click="clearFilters"
                    class="text-xs font-bold text-[#FF7A00] hover:text-[#CC6200] transition-colors">
                    Limpiar filtros
                </button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Category -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Categoría</label>
                    <select v-model="filterCategory"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all">
                        <option value="">Todas</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <!-- Type -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Tipo</label>
                    <select v-model="filterType"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all">
                        <option value="">Todos</option>
                        <option value="repuesto_nacional">Repuesto Nacional</option>
                        <option value="repuesto_internacional">Repuesto Internacional</option>
                        <option value="insumo">Insumo</option>
                    </select>
                </div>
                <!-- Stock Status -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Estado Stock</label>
                    <select v-model="filterStockStatus"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all">
                        <option value="">Todos</option>
                        <option value="critical">Crítico</option>
                        <option value="low">Bajo</option>
                        <option value="normal">Normal</option>
                    </select>
                </div>
                <!-- Price Min -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precio Mín ($)</label>
                    <input v-model.number="filterPriceMin" type="number" min="0" placeholder="0"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-xl px-4 py-2.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all" />
                </div>
                <!-- Price Max -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precio Máx ($)</label>
                    <input v-model.number="filterPriceMax" type="number" min="0" placeholder="∞"
                        class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-xl px-4 py-2.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all" />
                </div>
            </div>
        </div>

        <!-- Categories List (when modal is open for management) -->

        <!-- Table -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
            <div v-if="products.data.length === 0" class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Sin repuestos registrados</h3>
                <p class="text-gray-500 text-sm mt-1">Agrega tu primer repuesto con el botón superior.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100 uppercase text-[10px] font-black tracking-widest text-gray-400">
                            <th class="px-6 py-4">Producto</th>
                            <th class="px-6 py-4">SKU</th>
                            <th class="px-6 py-4">Categoría</th>
                            <th class="px-6 py-4 text-right">Costo</th>
                            <th class="px-6 py-4 text-right">Venta</th>
                            <th class="px-6 py-4 text-center">Stock</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="product in products.data" :key="product.id"
                            class="hover:bg-gray-50/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-sm">{{ product.name }}</div>
                                <div v-if="product.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1 max-w-xs">{{ product.description }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-bold text-gray-500 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">{{ product.sku }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="product.category"
                                    class="text-xs font-bold text-[#FF7A00] bg-[#FF7A00]/10 px-2.5 py-1 rounded-full">
                                    {{ product.category.name }}
                                </span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-600">{{ formatCurrency(product.cost_price) }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-gray-900">{{ formatCurrency(product.selling_price) }}</div>
                                <span
                                    :class="product.tax_included ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="inline-block mt-0.5 text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border"
                                >
                                    {{ product.tax_included ? `${taxName} incl.` : `+ ${taxName}` }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-sm font-black text-gray-900">{{ product.physical_stock }}</span>
                                    <span v-if="product.physical_stock <= product.min_stock"
                                        class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border bg-red-50 text-red-600 border-red-200">
                                        Stock Crítico
                                    </span>
                                    <span v-else-if="product.physical_stock <= product.min_stock * 2"
                                        class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border bg-amber-50 text-amber-600 border-amber-200">
                                        Bajo
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <!-- Movements History -->
                                    <button @click="openMovementsModal(product)" title="Historial de movimientos"
                                        class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <!-- Edit -->
                                    <button @click="openEditModal(product)" title="Editar"
                                        class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <!-- Delete -->
                                    <button @click="showDeleteConfirm = product.id" title="Eliminar"
                                        class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <!-- Delete Confirmation -->
                                    <div v-if="showDeleteConfirm === product.id"
                                        class="absolute right-8 mt-20 bg-white border border-gray-200 rounded-2xl shadow-xl p-4 z-50 w-56">
                                        <p class="text-xs font-bold text-gray-800 mb-3">¿Eliminar este repuesto?</p>
                                        <div class="flex gap-2">
                                            <button @click="showDeleteConfirm = null"
                                                class="flex-1 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors">No</button>
                                            <button @click="handleDelete(product)"
                                                class="flex-1 py-2 bg-red-500 text-white rounded-xl text-xs font-bold hover:bg-red-600 transition-colors">Sí, Eliminar</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="products.links && products.links.length > 3"
                class="px-6 py-4 border-t border-gray-100 flex items-center flex-wrap justify-center gap-1">
                <template v-for="(link, i) in products.links" :key="i">
                    <a v-if="link.url" :href="link.url" v-html="link.label"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="link.active ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                        @click.prevent="router.get(link.url, {}, { preserveState: true })" />
                    <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                </template>
            </div>
        </div>

        <!-- MODAL: Create / Edit Product -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative w-full max-w-lg max-h-[95vh] overflow-y-auto bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)] overflow-x-hidden">
                <!-- Header -->
                <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">{{ modalTitle }}</h2>
                        <p class="text-xs font-medium text-gray-400 mt-1">Completa los datos del repuesto o insumo.</p>
                    </div>
                    <button @click="showModal = false"
                        class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 lg:p-8 space-y-6">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nombre del Repuesto</label>
                        <input v-model="form.name" type="text"
                            class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                            placeholder="Ej: Filtro de Aceite" />
                        <p v-if="form.errors.name" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- SKU + Category -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">SKU / Código</label>
                            <input v-model="form.sku" type="text"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent font-mono uppercase transition-all shadow-sm"
                                placeholder="FLT-001" />
                            <p v-if="form.errors.sku" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.sku }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Categoría</label>
                            <select v-model="form.category_id"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-2xl px-5 py-3.5 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm">
                                <option :value="null">Sin categoría</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.category_id }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Descripción</label>
                        <input v-model="form.description" type="text"
                            class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                            placeholder="Opcional..." />
                    </div>

                    <!-- Prices & Tax -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precios y {{ taxName }}</label>
                            <!-- Selector Tipo de Precio -->
                            <div class="inline-flex rounded-xl bg-gray-100 p-0.5 border border-gray-200">
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                    :class="!form.tax_included ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    @click="form.tax_included = false"
                                >
                                    + {{ taxName }} (Neto)
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider transition-all"
                                    :class="form.tax_included ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    @click="form.tax_included = true"
                                >
                                    Con {{ taxName }} (Bruto)
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precio Costo ($)</label>
                                <input v-model.number="form.cost_price" type="number" step="1" min="0"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                    placeholder="0" />
                                <p v-if="form.errors.cost_price" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.cost_price }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">
                                    Precio Venta ($) <span class="text-[#FF7A00]">{{ form.tax_included ? `(Con ${taxName})` : `(+ ${taxName})` }}</span>
                                </label>
                                <input v-model.number="form.selling_price" type="number" step="1" min="0"
                                    class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                    placeholder="0" />
                                <p v-if="form.errors.selling_price" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.selling_price }}</p>
                                <p v-if="Number(form.selling_price) > 0" class="text-[11px] font-semibold text-gray-400 ml-1">
                                    <span v-if="form.tax_included">
                                        Neto estimado: <strong class="text-gray-700 font-mono">{{ formatCurrency(Math.round(form.selling_price / (1 + (defaultTaxRate / 100)))) }}</strong>
                                    </span>
                                    <span v-else>
                                        Total con {{ taxName }} ({{ defaultTaxRate }}%): <strong class="text-orange-600 font-mono">{{ formatCurrency(Math.round(form.selling_price * (1 + (defaultTaxRate / 100)))) }}</strong>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stock -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Stock Actual</label>
                            <input v-model.number="form.physical_stock" type="number" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                placeholder="0" />
                            <p v-if="form.errors.physical_stock" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.physical_stock }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Stock Mínimo</label>
                            <input v-model.number="form.min_stock" type="number" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm"
                                placeholder="5" />
                            <p v-if="form.errors.min_stock" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.min_stock }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-100">
                        <button type="button" @click="showModal = false"
                            class="order-2 sm:order-1 flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full font-bold transition-all active:scale-95 text-sm uppercase">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="order-1 sm:order-2 flex-[2] py-4 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-full font-black uppercase shadow-[0_8px_20px_rgba(249,168,38,0.3)] transition-all active:scale-95 disabled:opacity-50 disabled:cursor-wait flex items-center justify-center gap-2 tracking-wide text-lg">
                            <div v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ form.processing ? 'Guardando...' : (editingProduct ? 'Actualizar' : 'Guardar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showImportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeImportModal"></div>

            <div class="relative w-full max-w-2xl bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)]">
                <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Importar repuestos</h2>
                        <p class="text-xs font-medium text-gray-400 mt-1">Carga tu inventario desde Excel o CSV.</p>
                    </div>
                    <button @click="closeImportModal"
                        class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 lg:p-8 space-y-6">
                    <div class="grid gap-4 rounded-[2rem] border border-gray-100 bg-gray-50/70 p-5 md:grid-cols-2">
                        <div class="rounded-2xl bg-white p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Plantilla sugerida</p>
                            <p class="mt-2 text-sm font-medium text-gray-600">Incluye SKU, nombre, categoria, tipo, precios y stock.</p>
                            <a :href="route('inventory.import.template', tenantRouteParams)"
                                class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-white transition-colors hover:bg-gray-800">
                                Descargar plantilla
                            </a>
                        </div>

                        <div class="rounded-2xl bg-white p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Comportamiento</p>
                            <p class="mt-2 text-sm font-medium text-gray-600">Si un SKU ya existe, la importacion actualiza el repuesto y registra el ajuste de stock.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Archivo Excel o CSV</label>
                            <input ref="importInput" type="file" accept=".xlsx,.xls,.csv" @change="handleImportFileChange"
                                class="block w-full rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-4 text-sm font-medium text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-[#FF7A00] file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:file:bg-[#CC6200]" />
                            <p class="text-xs font-medium text-gray-500">Acepta archivos .xlsx, .xls y .csv de hasta 10 MB.</p>
                            <p v-if="importForm.errors.workbook" class="text-xs text-rose-500">{{ importForm.errors.workbook }}</p>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <button type="button" @click="closeImportModal"
                                class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="importForm.processing || !importForm.workbook"
                                class="inline-flex items-center justify-center rounded-2xl bg-[#FF7A00] px-6 py-3 text-sm font-black text-white transition-all hover:bg-[#CC6200] disabled:opacity-50">
                                <div v-if="importForm.processing" class="mr-2 w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                {{ importForm.processing ? 'Importando...' : 'Importar archivo' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: Categories Management -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showCategoryModal = false"></div>

            <div class="relative w-full max-w-md max-h-[95vh] overflow-y-auto bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)]">
                <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Categorías</h2>
                        <p class="text-xs font-medium text-gray-400 mt-1">Organiza tus repuestos en categorías.</p>
                    </div>
                    <button @click="showCategoryModal = false"
                        class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Create/Edit Form -->
                <form @submit.prevent="handleCategorySubmit" class="p-6 lg:p-8 border-b border-gray-100">
                    <div class="flex gap-3">
                        <div class="flex-1 space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">
                                {{ editingCategory ? 'Editar categoría' : 'Nueva categoría' }}
                            </label>
                            <input v-model="categoryForm.name" type="text" placeholder="Ej: Filtros"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:border-transparent transition-all shadow-sm" />
                            <p v-if="categoryForm.errors.name" class="text-red-500 text-[10px] font-medium ml-1">{{ categoryForm.errors.name }}</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" :disabled="categoryForm.processing"
                                class="p-3 bg-[#FF7A00] hover:bg-[#CC6200] text-white rounded-2xl shadow-sm transition-all active:scale-95 disabled:opacity-50">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <button v-if="editingCategory" type="button" @click="editingCategory = null; categoryForm.reset(); categoryForm.clearErrors();"
                                class="p-3 bg-gray-100 text-gray-500 rounded-2xl hover:bg-gray-200 transition-all">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Categories List -->
                <div class="p-6 lg:p-8 space-y-2">
                    <div v-if="categories.length === 0" class="text-center py-6">
                        <p class="text-sm text-gray-400">No hay categorías creadas.</p>
                    </div>
                    <div v-for="cat in categories" :key="cat.id"
                        class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-xl group hover:bg-gray-100 transition-colors">
                        <div>
                            <span class="text-sm font-bold text-gray-900">{{ cat.name }}</span>
                            <span v-if="cat.products_count !== undefined" class="text-xs text-gray-400 ml-2">{{ cat.products_count }} productos</span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditCategoryModal(cat)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="handleCategoryDelete(cat)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Stock Movements History -->
        <div v-if="showMovementsModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showMovementsModal = false"></div>

            <div class="relative w-full max-w-2xl max-h-[95vh] overflow-y-auto bg-white border border-gray-100 rounded-[2.5rem] shadow-[0_32px_64px_rgba(0,0,0,0.1)]">
                <div class="p-6 lg:p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Historial</h2>
                        <p class="text-xs font-medium text-gray-400 mt-1">
                            Movimientos de stock de <span class="font-bold text-gray-600">{{ movementsProduct?.name }}</span>
                        </p>
                    </div>
                    <button @click="showMovementsModal = false"
                        class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all border border-gray-200 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 lg:p-8">
                    <!-- Loading -->
                    <div v-if="movementsLoading" class="flex items-center justify-center py-12">
                        <div class="w-8 h-8 border-3 border-gray-200 border-t-[#FF7A00] rounded-full animate-spin"></div>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="movements.length === 0" class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-gray-400">Sin movimientos registrados.</p>
                    </div>

                    <!-- Movements Table -->
                    <div v-else class="space-y-3">
                        <div v-for="mov in movements" :key="mov.id"
                            class="flex items-center gap-4 py-3 px-4 bg-gray-50 rounded-xl">
                            <div class="flex-shrink-0">
                                <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border"
                                    :class="movementTypeBadge(mov.type).class">
                                    {{ movementTypeBadge(mov.type).label }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black" :class="mov.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ mov.quantity > 0 ? '+' : '' }}{{ mov.quantity }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ mov.stock_before }} → {{ mov.stock_after }}
                                    </span>
                                </div>
                                <p v-if="mov.notes" class="text-xs text-gray-500 mt-0.5 truncate">{{ mov.notes }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-xs text-gray-400">{{ formatDateTime(mov.created_at) }}</div>
                                <div v-if="mov.user" class="text-[10px] text-gray-300">{{ mov.user.name }}</div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="movementsPagination && movementsPagination.last_page > 1"
                            class="flex items-center justify-center gap-2 pt-4">
                            <button v-if="movementsPagination.prev_page_url"
                                @click="loadMovements(movementsPagination.prev_page_url)"
                                class="px-4 py-2 text-sm font-bold text-gray-500 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                                Anterior
                            </button>
                            <span class="text-xs text-gray-400">
                                Página {{ movementsPagination.current_page }} de {{ movementsPagination.last_page }}
                            </span>
                            <button v-if="movementsPagination.next_page_url"
                                @click="loadMovements(movementsPagination.next_page_url)"
                                class="px-4 py-2 text-sm font-bold text-gray-500 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
