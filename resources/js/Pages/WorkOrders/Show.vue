<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    workOrder: Object,
    products: Array,
});

// ─── Status badge ──────────────────────────────────────────────────────────
const statusConfig = {
    recepcion:            { label: 'Recepción',        classes: 'bg-blue-50 text-blue-600 border-blue-100' },
    diagnostico:          { label: 'En Diagnóstico',   classes: 'bg-yellow-50 text-yellow-600 border-yellow-100' },
    esperando_repuestos:  { label: 'Faltan Repuestos', classes: 'bg-red-50 text-red-500 border-red-100' },
    control_calidad:      { label: 'Control de Calidad', classes: 'bg-cyan-50 text-cyan-600 border-cyan-100' },
    listo:                { label: 'Listo',            classes: 'bg-green-50 text-green-600 border-green-100' },
};

const statusInfo = computed(() => statusConfig[props.workOrder.status] ?? { label: props.workOrder.status, classes: 'bg-gray-50 text-gray-500 border-gray-100' });

// ─── WhatsApp link ─────────────────────────────────────────────────────────
const trackingUrl = computed(() => `${window.location.origin}/ot/${props.workOrder.uuid}`);
const whatsAppMessage = computed(() => {
    const vehicle = `${props.workOrder.vehicle?.brand ?? ''} ${props.workOrder.vehicle?.model ?? ''}`.trim();
    return encodeURIComponent(`Hola! Tu presupuesto para el ${vehicle} (${props.workOrder.vehicle?.plate}) ya está disponible: ${trackingUrl.value}`);
});
const whatsAppLink = computed(() => {
    const phone = props.workOrder.vehicle?.client?.phone ?? '';
    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${whatsAppMessage.value}`;
});

// ─── Budget total ───────────────────────────────────────────────────────────
const items = computed(() => props.workOrder.items ?? []);
const grandTotal = computed(() => items.value.reduce((sum, item) => sum + parseFloat(item.total_price), 0));

const formatCurrency = (amount) =>
    new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);

// ─── Add item form ──────────────────────────────────────────────────────────
const selectedProduct = ref(null);
const productSearch = ref('');

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products;
    const q = productSearch.value.toLowerCase();
    return props.products.filter(p => p.name.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q));
});

const selectProduct = (product) => {
    selectedProduct.value = product;
    addForm.product_id  = product.id;
    addForm.description = product.name;
    addForm.unit_price  = product.selling_price;
    productSearch.value = product.name;
    showDropdown.value  = false;
};

const clearProduct = () => {
    selectedProduct.value  = null;
    productSearch.value    = '';
    addForm.product_id     = null;
    addForm.description    = '';
    addForm.unit_price     = '';
};

const showDropdown = ref(false);

const addForm = useForm({
    product_id:  null,
    description: '',
    quantity:    1,
    unit_price:  '',
});

const submitItem = () => {
    addForm.post(route('work-orders.items.store', props.workOrder.id), {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset();
            selectedProduct.value = null;
            productSearch.value    = '';
        },
    });
};

// ─── Remove item ─────────────────────────────────────────────────────────────
const removeItem = (itemId) => {
    router.delete(route('work-orders.items.destroy', { workOrder: props.workOrder.id, item: itemId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`OT #${workOrder.id} — ${workOrder.vehicle?.plate ?? ''}`" />

    <TallerLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">

            <!-- Breadcrumb -->
            <div class="px-1">
                <Link :href="route('work-orders.index')" class="inline-flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest hover:text-gray-700 transition-colors">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    Tablero Kanban
                </Link>
            </div>

            <!-- Header Card -->
            <div class="bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                    <div class="flex flex-col gap-3">
                        <!-- Status badge -->
                        <span :class="['inline-flex self-start items-center text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border', statusInfo.classes]">
                            {{ statusInfo.label }}
                        </span>

                        <!-- Vehicle info -->
                        <div>
                            <h1 class="text-3xl font-black tracking-tight text-gray-900">
                                {{ workOrder.vehicle?.brand }} {{ workOrder.vehicle?.model }}
                            </h1>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="font-mono text-lg font-bold text-gray-500 tracking-widest">
                                    {{ workOrder.vehicle?.plate }}
                                </span>
                                <span class="text-gray-200">|</span>
                                <span class="text-sm text-gray-500 font-semibold">
                                    {{ workOrder.vehicle?.client?.name ?? 'Cliente no asignado' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="workOrder.observations" class="text-sm text-gray-400 italic max-w-xl">
                            {{ workOrder.observations }}
                        </div>
                    </div>

                    <!-- WhatsApp button -->
                    <a
                        :href="whatsAppLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-3 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-bold text-sm rounded-2xl transition-all duration-200 shadow-sm hover:shadow-md active:scale-[0.98] shrink-0"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12.004 2a10 10 0 0 0-8.681 14.98L2 22l5.177-1.297A10 10 0 1 0 12.004 2zm0 18.18a8.17 8.17 0 0 1-4.163-1.14l-.3-.178-3.073.77.814-2.986-.196-.307A8.18 8.18 0 1 1 12.004 20.18z"/>
                        </svg>
                        Compartir Presupuesto
                    </a>
                </div>
            </div>

            <!-- Budget table + Add item -->
            <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">

                <!-- Budget Table (3/5) -->
                <div class="xl:col-span-3 bg-white border border-gray-100 rounded-[2rem] shadow-sm overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Presupuesto</h2>
                        <span class="text-[10px] font-bold text-gray-400">{{ items.length }} ítems</span>
                    </div>

                    <!-- Empty state -->
                    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-16 px-8 text-center">
                        <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <p class="text-gray-400 font-semibold text-xs uppercase tracking-tight">Sin ítems agregados aún</p>
                    </div>

                    <!-- Items -->
                    <div v-else>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50">
                                    <th class="px-8 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Descripción</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Cant.</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">P. Unit.</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-8 py-4">
                                        <p class="text-sm font-semibold text-gray-800">{{ item.description }}</p>
                                        <p v-if="item.product?.sku" class="text-[10px] text-gray-400 font-mono mt-0.5">{{ item.product.sku }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm tabular-nums font-medium text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-4 text-right text-sm tabular-nums font-medium text-gray-600">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-4 py-4 text-right text-sm tabular-nums font-bold text-gray-800">{{ formatCurrency(item.total_price) }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <button @click="removeItem(item.id)" class="text-gray-300 hover:text-red-400 transition-colors" title="Eliminar ítem">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-100 bg-gray-50/50">
                                    <td colspan="3" class="px-8 py-4 text-sm font-black text-gray-600 uppercase tracking-wider text-right">Total Presupuesto</td>
                                    <td class="px-4 py-4 text-right text-lg font-black text-gray-900 tabular-nums">{{ formatCurrency(grandTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Add Item Form (2/5) -->
                <div class="xl:col-span-2 bg-white border border-gray-100 rounded-[2rem] p-8 shadow-sm">
                    <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Agregar Ítem</h2>

                    <form @submit.prevent="submitItem" class="flex flex-col gap-5">

                        <!-- Product search -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Repuesto del Inventario <span class="normal-case font-normal">(opcional)</span></label>
                            <div class="relative">
                                <input
                                    v-model="productSearch"
                                    @focus="showDropdown = true"
                                    @blur="setTimeout(() => showDropdown = false, 200)"
                                    type="text"
                                    placeholder="Buscar producto por nombre o SKU..."
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent transition-all"
                                />
                                <button
                                    v-if="selectedProduct"
                                    type="button"
                                    @click="clearProduct"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-orange-500 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <!-- Dropdown -->
                                <div v-if="showDropdown && filteredProducts.length > 0" class="absolute z-20 mt-1 w-full bg-white border border-gray-100 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    <button
                                        v-for="product in filteredProducts.slice(0, 10)"
                                        :key="product.id"
                                        type="button"
                                        @click="selectProduct(product)"
                                        class="w-full flex items-center justify-between px-4 py-2.5 text-left hover:bg-orange-50 transition-colors"
                                    >
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ product.name }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono">{{ product.sku }} · Stock: {{ product.physical_stock }}</p>
                                        </div>
                                        <span class="text-sm font-bold text-orange-500 shrink-0 ml-4">{{ formatCurrency(product.selling_price) }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="selectedProduct" class="text-[10px] text-green-500 font-bold">
                                ✓ {{ selectedProduct.name }} — Stock: {{ selectedProduct.physical_stock }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Descripción</label>
                            <input
                                v-model="addForm.description"
                                type="text"
                                placeholder="Ej: Mano de obra, Cambio de aceite..."
                                required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent transition-all"
                                :class="{ 'border-red-300 ring-1 ring-red-200': addForm.errors.description }"
                            />
                            <p v-if="addForm.errors.description" class="text-[10px] text-red-400 font-semibold">{{ addForm.errors.description }}</p>
                        </div>

                        <!-- Quantity & Price -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Cantidad</label>
                                <input
                                    v-model.number="addForm.quantity"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent transition-all"
                                    :class="{ 'border-red-300 ring-1 ring-red-200': addForm.errors.quantity }"
                                />
                                <p v-if="addForm.errors.quantity" class="text-[10px] text-red-400 font-semibold">{{ addForm.errors.quantity }}</p>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Precio Unit.</label>
                                <input
                                    v-model.number="addForm.unit_price"
                                    type="number"
                                    min="0"
                                    step="1"
                                    required
                                    placeholder="0"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent transition-all"
                                    :class="{ 'border-red-300 ring-1 ring-red-200': addForm.errors.unit_price }"
                                />
                                <p v-if="addForm.errors.unit_price" class="text-[10px] text-red-400 font-semibold">{{ addForm.errors.unit_price }}</p>
                            </div>
                        </div>

                        <!-- Subtotal preview -->
                        <div v-if="addForm.quantity && addForm.unit_price" class="bg-orange-50 border border-orange-100 rounded-xl px-4 py-3 flex justify-between items-center">
                            <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Subtotal</span>
                            <span class="text-base font-black text-orange-600">{{ formatCurrency(addForm.quantity * addForm.unit_price) }}</span>
                        </div>

                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="w-full py-3 bg-gray-900 hover:bg-gray-700 text-white font-bold text-sm rounded-2xl transition-all duration-200 active:scale-[0.98] disabled:opacity-50"
                        >
                            {{ addForm.processing ? 'Agregando...' : '+ Agregar Ítem' }}
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </TallerLayout>
</template>
