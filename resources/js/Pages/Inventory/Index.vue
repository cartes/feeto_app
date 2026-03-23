<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const editingProduct = ref(null);
const showDeleteConfirm = ref(null);

// Debounce utility
let searchTimeout;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('inventory.index'), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const form = useForm({
    name: '',
    sku: '',
    description: '',
    cost_price: 0,
    selling_price: 0,
    physical_stock: 0,
    min_stock: 0,
});

const modalTitle = computed(() => editingProduct.value ? 'Editar Repuesto' : 'Nuevo Repuesto');

const openCreateModal = () => {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.name = product.name;
    form.sku = product.sku;
    form.description = product.description || '';
    form.cost_price = product.cost_price;
    form.selling_price = product.selling_price;
    form.physical_stock = product.physical_stock;
    form.min_stock = product.min_stock;
    form.clearErrors();
    showModal.value = true;
};

const handleSubmit = () => {
    if (editingProduct.value) {
        form.put(route('inventory.update', editingProduct.value.id), {
            onSuccess: () => { showModal.value = false; },
            preserveScroll: true,
        });
    } else {
        form.post(route('inventory.store'), {
            onSuccess: () => { showModal.value = false; },
            preserveScroll: true,
        });
    }
};

const handleDelete = (product) => {
    router.delete(route('inventory.destroy', product.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteConfirm.value = null; },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head title="Inventario de Stock" />

    <TallerLayout>
        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Inventario</h1>
                <p class="text-sm font-medium text-gray-500 mt-1">Gestiona los repuestos e insumos del taller.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative w-full md:w-80">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Buscar por nombre o SKU..."
                        class="w-full pl-11 pr-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F9A826]/50 focus:border-[#F9A826] transition-all shadow-sm" />
                </div>
                <button @click="openCreateModal"
                    class="flex-shrink-0 bg-[#F9A826] hover:bg-[#E59A22] text-white px-5 py-3 rounded-2xl font-bold text-sm shadow-sm transition-all active:scale-95 flex items-center gap-2 uppercase tracking-wide">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar
                </button>
            </div>
        </div>

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
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-600">{{ formatCurrency(product.cost_price) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ formatCurrency(product.selling_price) }}</td>
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
                                    <button @click="openEditModal(product)" title="Editar"
                                        class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button @click="showDeleteConfirm = product.id" title="Eliminar"
                                        class="p-2 rounded-xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <!-- Delete Confirmation Mini-Popover -->
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
                        :class="link.active ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
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
                            class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
                            placeholder="Ej: Filtro de Aceite" />
                        <p v-if="form.errors.name" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- SKU + Description -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">SKU / Código</label>
                            <input v-model="form.sku" type="text"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent font-mono uppercase transition-all shadow-sm"
                                placeholder="FLT-001" />
                            <p v-if="form.errors.sku" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.sku }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Descripción</label>
                            <input v-model="form.description" type="text"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-medium rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
                                placeholder="Opcional..." />
                        </div>
                    </div>

                    <!-- Prices -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precio Costo ($)</label>
                            <input v-model.number="form.cost_price" type="number" step="1" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
                                placeholder="0" />
                            <p v-if="form.errors.cost_price" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.cost_price }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Precio Venta ($)</label>
                            <input v-model.number="form.selling_price" type="number" step="1" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
                                placeholder="0" />
                            <p v-if="form.errors.selling_price" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.selling_price }}</p>
                        </div>
                    </div>

                    <!-- Stock -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Stock Actual</label>
                            <input v-model.number="form.physical_stock" type="number" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
                                placeholder="0" />
                            <p v-if="form.errors.physical_stock" class="text-red-500 text-[10px] font-medium ml-1">{{ form.errors.physical_stock }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest ml-1">Stock Mínimo</label>
                            <input v-model.number="form.min_stock" type="number" min="0"
                                class="w-full bg-white border border-gray-300 text-gray-900 text-sm font-bold rounded-2xl px-5 py-3.5 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F9A826] focus:border-transparent transition-all shadow-sm"
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
                            class="order-1 sm:order-2 flex-[2] py-4 bg-[#F9A826] hover:bg-[#E59A22] text-white rounded-full font-black uppercase shadow-[0_8px_20px_rgba(249,168,38,0.3)] transition-all active:scale-95 disabled:opacity-50 disabled:cursor-wait flex items-center justify-center gap-2 tracking-wide text-lg">
                            <div v-if="form.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ form.processing ? 'Guardando...' : (editingProduct ? 'Actualizar' : 'Guardar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TallerLayout>
</template>
