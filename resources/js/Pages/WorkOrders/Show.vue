<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();

const props = defineProps({
    workOrder: Object,
    products: Array,
    services: Array,
});

const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);

const quote = computed(() => props.workOrder.quote ?? {
    status: 'draft',
    subtotal_amount: props.workOrder.total_amount ?? 0,
    items: [],
    events: [],
});

const items = computed(() => quote.value.items ?? []);

const workOrderStatusConfig = {
    recepcion: { label: 'Recepción', classes: 'bg-blue-50 text-blue-600 border-blue-100' },
    diagnostico: { label: 'En Diagnóstico', classes: 'bg-yellow-50 text-yellow-600 border-yellow-100' },
    esperando_repuestos: { label: 'Esperando Repuestos', classes: 'bg-red-50 text-red-500 border-red-100' },
    control_calidad: { label: 'Control de Calidad', classes: 'bg-cyan-50 text-cyan-600 border-cyan-100' },
    listo: { label: 'Listo', classes: 'bg-green-50 text-green-600 border-green-100' },
};

const quoteStatusConfig = {
    draft: { label: 'Borrador', classes: 'bg-slate-100 text-slate-600 border-slate-200' },
    pending_customer: { label: 'Pendiente Cliente', classes: 'bg-amber-50 text-amber-600 border-amber-200' },
    accepted: { label: 'Aceptada', classes: 'bg-emerald-50 text-emerald-600 border-emerald-200' },
    rejected: { label: 'Rechazada', classes: 'bg-rose-50 text-rose-600 border-rose-200' },
};

const quoteItemTypeLabels = {
    product: 'Repuesto',
    service: 'Servicio',
    manual: 'Manual',
};

const workOrderStatus = computed(() => workOrderStatusConfig[props.workOrder.status] ?? {
    label: props.workOrder.status,
    classes: 'bg-gray-50 text-gray-600 border-gray-200',
});

const quoteStatus = computed(() => quoteStatusConfig[quote.value.status] ?? quoteStatusConfig.draft);

const formatCurrency = (amount) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(Number(amount || 0));

const formatDateTime = (value) => {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const trackingUrl = computed(() => `${window.location.origin}/ot/${props.workOrder.uuid}`);
const whatsAppMessage = computed(() => {
    const vehicle = `${props.workOrder.vehicle?.brand ?? ''} ${props.workOrder.vehicle?.model ?? ''}`.trim();

    return encodeURIComponent(`Hola, tu cotización para ${vehicle} (${props.workOrder.vehicle?.plate}) está disponible: ${trackingUrl.value}`);
});

const whatsAppLink = computed(() => {
    const phone = props.workOrder.vehicle?.client?.phone ?? '';
    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${whatsAppMessage.value}`;
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
});

const sendQuoteForm = useForm({});

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
    selectedProduct.value = null;
    selectedService.value = null;
    productSearch.value = '';
    serviceSearch.value = '';
    selectedMode.value = 'manual';
};

const submitItem = () => {
    addForm.post(route('work-orders.items.store', props.workOrder.id), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const removeItem = (itemId) => {
    router.delete(route('work-orders.items.destroy', { workOrder: props.workOrder.id, item: itemId }), {
        preserveScroll: true,
    });
};

const sendQuote = () => {
    sendQuoteForm.post(route('work-orders.quote.send', props.workOrder.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`OT #${workOrder.id} — ${workOrder.vehicle?.plate ?? ''}`" />

    <TallerLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="px-1">
                <Link
                    :href="route('work-orders.index')"
                    class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-400 transition-colors hover:text-gray-700"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    Tablero Kanban
                </Link>
            </div>

            <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-start">
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-3">
                            <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', workOrderStatus.classes]">
                                {{ workOrderStatus.label }}
                            </span>
                            <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', quoteStatus.classes]">
                                Cotización {{ quoteStatus.label }}
                            </span>
                        </div>

                        <div>
                            <h1 class="text-3xl font-black tracking-tight text-gray-900">
                                {{ workOrder.vehicle?.brand }} {{ workOrder.vehicle?.model }}
                            </h1>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm font-semibold text-gray-500">
                                <span class="font-mono text-lg tracking-widest">{{ workOrder.vehicle?.plate }}</span>
                                <span class="text-gray-200">|</span>
                                <span>{{ workOrder.vehicle?.client?.name ?? 'Cliente no asignado' }}</span>
                                <span v-if="workOrder.vehicle?.client?.phone" class="text-gray-200">|</span>
                                <span v-if="workOrder.vehicle?.client?.phone">{{ workOrder.vehicle.client.phone }}</span>
                            </div>
                        </div>

                        <p v-if="workOrder.observations" class="max-w-2xl text-sm italic text-gray-400">
                            {{ workOrder.observations }}
                        </p>

                        <div v-if="quote.customer_response_notes" class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-amber-500">Respuesta Cliente</p>
                            <p class="mt-2 text-sm font-medium text-amber-900">{{ quote.customer_response_notes }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 xl:min-w-[280px]">
                        <div class="rounded-[1.75rem] border border-gray-100 bg-gray-50 p-5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Cotización</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</p>
                            <p class="mt-2 text-xs font-medium text-gray-500">{{ items.length }} ítems cargados</p>
                        </div>

                        <button
                            v-if="commercialQuotesEnabled"
                            type="button"
                            class="rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-[#F9A826] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="sendQuoteForm.processing || items.length === 0 || quote.status === 'accepted'"
                            @click="sendQuote"
                        >
                            {{ sendQuoteForm.processing ? 'Enviando...' : 'Enviar Cotización al Cliente' }}
                        </button>

                        <a
                            v-if="commercialQuotesEnabled"
                            :href="whatsAppLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-green-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-green-600"
                        >
                            Compartir por WhatsApp
                        </a>

                        <a
                            v-if="commercialQuotesEnabled"
                            :href="trackingUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Abrir Tracking del Cliente
                        </a>
                    </div>
                </div>
            </div>

            <div v-if="commercialQuotesEnabled" class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="xl:col-span-3 overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-50 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Detalle Cotización</h2>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ items.length }} registros</span>
                    </div>

                    <div v-if="items.length === 0" class="flex flex-col items-center justify-center px-8 py-16 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50">
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
                                    <th class="px-8 py-3 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Descripción</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Tipo</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Cant.</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">P. Unit.</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-gray-50/50">
                                    <td class="px-8 py-4">
                                        <p class="text-sm font-semibold text-gray-800">{{ item.description }}</p>
                                        <p v-if="item.product?.sku" class="mt-1 text-[10px] font-mono text-gray-400">{{ item.product.sku }}</p>
                                        <p v-if="item.service?.code" class="mt-1 text-[10px] font-mono text-gray-400">{{ item.service.code }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                            {{ quoteItemTypeLabels[item.item_type] ?? item.item_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm font-medium tabular-nums text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-medium tabular-nums text-gray-600">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black tabular-nums text-gray-900">{{ formatCurrency(item.total_price) }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <button
                                            type="button"
                                            class="text-gray-300 transition-colors hover:text-rose-500"
                                            @click="removeItem(item.id)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-100 bg-gray-50/50">
                                    <td colspan="4" class="px-8 py-4 text-right text-sm font-black uppercase tracking-wider text-gray-600">Total Cotización</td>
                                    <td class="px-4 py-4 text-right text-lg font-black tabular-nums text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="space-y-6 xl:col-span-2">
                    <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Agregar Ítem</h2>
                            <Link :href="route('services.index')" class="text-[10px] font-black uppercase tracking-widest text-[#F9A826] hover:text-[#dd9219]">
                                Gestionar Servicios
                            </Link>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                class="rounded-2xl px-3 py-3 text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="selectedMode === 'manual' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                                @click="selectMode('manual')"
                            >
                                Manual
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl px-3 py-3 text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="selectedMode === 'product' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                                @click="selectMode('product')"
                            >
                                Repuesto
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl px-3 py-3 text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="selectedMode === 'service' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'"
                                @click="selectMode('service')"
                            >
                                Servicio
                            </button>
                        </div>

                        <form class="mt-6 space-y-5" @submit.prevent="submitItem">
                            <div v-if="selectedMode === 'product'" class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Buscar Repuesto</label>
                                <div class="relative">
                                    <input
                                        v-model="productSearch"
                                        type="text"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                                        placeholder="Nombre o SKU"
                                        @focus="showProductDropdown = true"
                                        @blur="setTimeout(() => { showProductDropdown = false; }, 200)"
                                    />
                                    <div v-if="showProductDropdown && filteredProducts.length" class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-2xl border border-gray-100 bg-white shadow-lg">
                                        <button
                                            v-for="product in filteredProducts.slice(0, 8)"
                                            :key="product.id"
                                            type="button"
                                            class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-orange-50"
                                            @click="selectProduct(product)"
                                        >
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">{{ product.name }}</p>
                                                <p class="text-[10px] font-mono text-gray-400">{{ product.sku }} · Stock {{ product.physical_stock }}</p>
                                            </div>
                                            <span class="text-sm font-black text-orange-500">{{ formatCurrency(product.selling_price) }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="selectedMode === 'service'" class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Buscar Servicio</label>
                                <div class="relative">
                                    <input
                                        v-model="serviceSearch"
                                        type="text"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                                        placeholder="Nombre o código"
                                        @focus="showServiceDropdown = true"
                                        @blur="setTimeout(() => { showServiceDropdown = false; }, 200)"
                                    />
                                    <div v-if="showServiceDropdown && filteredServices.length" class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-2xl border border-gray-100 bg-white shadow-lg">
                                        <button
                                            v-for="service in filteredServices.slice(0, 8)"
                                            :key="service.id"
                                            type="button"
                                            class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-orange-50"
                                            @click="selectService(service)"
                                        >
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">{{ service.name }}</p>
                                                <p class="text-[10px] font-mono text-gray-400">{{ service.code || 'Sin código' }} · {{ service.estimated_minutes }} min</p>
                                            </div>
                                            <span class="text-sm font-black text-orange-500">{{ formatCurrency(service.selling_price) }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Descripción</label>
                                <input
                                    v-model="addForm.description"
                                    type="text"
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                                    placeholder="Ej: Cambio de aceite, diagnóstico eléctrico"
                                />
                                <p v-if="addForm.errors.description" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.description }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cantidad</label>
                                    <input
                                        v-model.number="addForm.quantity"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                                    />
                                    <p v-if="addForm.errors.quantity" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.quantity }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Precio Unitario</label>
                                    <input
                                        v-model.number="addForm.unit_price"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                                    />
                                    <p v-if="addForm.errors.unit_price" class="text-[10px] font-semibold text-rose-500">{{ addForm.errors.unit_price }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3">
                                <span class="text-[10px] font-black uppercase tracking-widest text-orange-500">Subtotal</span>
                                <span class="text-base font-black text-orange-600">{{ formatCurrency((addForm.quantity || 0) * (addForm.unit_price || 0)) }}</span>
                            </div>

                            <button
                                type="submit"
                                class="w-full rounded-2xl bg-gray-900 py-3 text-sm font-black text-white transition-colors hover:bg-gray-700 disabled:opacity-50"
                                :disabled="addForm.processing"
                            >
                                {{ addForm.processing ? 'Agregando...' : 'Agregar a Cotización' }}
                            </button>
                        </form>
                    </div>

                    <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Historial Comercial</h2>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ quote.events?.length ?? 0 }} eventos</span>
                        </div>

                        <div v-if="!(quote.events?.length)" class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Sin historial todavía
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="event in quote.events"
                                :key="event.id"
                                class="rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ event.description }}</p>
                                        <p class="mt-1 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                            {{ event.actor_type }} · {{ event.event_type }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-semibold text-gray-400">{{ formatDateTime(event.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-[2rem] border border-dashed border-orange-200 bg-orange-50/70 p-8 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="max-w-2xl">
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                        <h2 class="mt-4 text-2xl font-black tracking-tight text-gray-900">Cotizaciones comerciales bloqueadas para este plan</h2>
                        <p class="mt-2 text-sm font-medium text-gray-600">
                            Este taller puede seguir operando con órdenes de trabajo, pero el flujo comercial avanzado
                            de servicios, cotización formal y aprobación del cliente está reservado para planes superiores.
                        </p>
                        <p class="mt-3 text-sm font-semibold text-orange-700">
                            {{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}
                        </p>
                    </div>
                    <div class="rounded-3xl border border-orange-100 bg-white px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Plan actual</p>
                        <p class="mt-2 text-lg font-black text-gray-900">{{ planAccess?.plan_name || 'Sin plan' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
