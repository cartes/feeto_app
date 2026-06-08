<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();

const props = defineProps({
    quote: Object,
    products: Array,
    services: Array,
    discountPolicy: Object,
    uf_value: {
        type: Number,
        default: null,
    },
    canDeliverQuote: {
        type: Boolean,
        default: false,
    },
});

const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const roles = computed(() => page.props.auth?.user?.roles ?? []);
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const isSuperAdmin = computed(() => Boolean(page.props.auth?.user?.is_super_admin));

const canManageItems = computed(() => (
    isSuperAdmin.value || permissions.value.includes('work-orders.manage-items')
));

const items = computed(() => props.quote.items ?? []);

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

const quoteStatus = computed(() => quoteStatusConfig[props.quote.status] ?? quoteStatusConfig.draft);

const formatCurrency = (amount) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(Number(amount || 0));

const formatUf = (clpValue) => {
    if (!props.uf_value || !clpValue) return null;
    const uf = Number(clpValue) / props.uf_value;
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(uf);
};

const formatDateTime = (value) => {
    if (!value) return 'Sin fecha';

    return new Date(value).toLocaleString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const trackingUrl = computed(() => `${window.location.origin}/cotizacion/${props.quote.uuid}`);
const whatsAppMessage = computed(() => {
    const vehicle = `${props.quote.vehicle?.brand ?? ''} ${props.quote.vehicle?.model ?? ''}`.trim();

    return encodeURIComponent(`Hola, tu cotización para ${vehicle} (${props.quote.vehicle?.plate}) está disponible: ${trackingUrl.value}`);
});
const whatsAppLink = computed(() => {
    const phone = props.quote.client?.phone ?? '';
    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${whatsAppMessage.value}`;
});
const hasClientPhone = computed(() => Boolean(props.quote.client?.phone));
const hasClientEmail = computed(() => Boolean(props.quote.client?.email));
const canShareQuote = computed(() => props.quote.status === 'pending_customer');
const mailSubject = computed(() => encodeURIComponent(`Cotización #${props.quote.id} disponible para revisión`));
const mailBody = computed(() => {
    const clientName = props.quote.client?.name ?? 'cliente';
    const vehicle = `${props.quote.vehicle?.brand ?? ''} ${props.quote.vehicle?.model ?? ''}`.trim();

    return encodeURIComponent(
        `Hola ${clientName},%0D%0A%0D%0ATu cotización${vehicle ? ` para ${vehicle}` : ''} ya está disponible para revisión.%0D%0A%0D%0APuedes verla y responderla aquí:%0D%0A${trackingUrl.value}`
    );
});
const mailToLink = computed(() => {
    const email = props.quote.client?.email ?? '';

    return `mailto:${email}?subject=${mailSubject.value}&body=${mailBody.value}`;
});

const sendChannel = ref('manual');

const selectedMode = ref('manual');
const selectedProduct = ref(null);
const selectedService = ref(null);
const productSearch = ref('');
const serviceSearch = ref('');
const showProductDropdown = ref(false);
const showServiceDropdown = ref(false);

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
    if (!newVal && selectedProduct.value) clearProduct();
});

watch(serviceSearch, (newVal) => {
    if (!newVal && selectedService.value) clearService();
});

const addForm = useForm({
    product_id: null,
    service_id: null,
    description: '',
    quantity: 1,
    unit_price: '',
    discount_percent: 0,
});

const sendQuoteForm = useForm({
    channel: 'manual',
});

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products;

    const query = productSearch.value.toLowerCase();

    return props.products.filter((product) => (
        product.name.toLowerCase().includes(query)
        || product.sku?.toLowerCase().includes(query)
    ));
});

const filteredServices = computed(() => {
    if (!serviceSearch.value) return props.services;

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
    addForm.post(route('quotes.items.store', { ...tenantRouteParams.value, quote: props.quote.id }), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const removeItem = (itemId) => {
    router.delete(route('quotes.items.destroy', { ...tenantRouteParams.value, quote: props.quote.id, item: itemId }), {
        preserveScroll: true,
    });
};

const shareQuoteByWhatsApp = () => {
    if (!hasClientPhone.value) return;
    window.open(whatsAppLink.value, '_blank', 'noopener');
};

const shareQuoteByEmail = () => {
    if (!hasClientEmail.value) return;
    window.location.href = mailToLink.value;
};

const shareQuoteByBoth = () => {
    shareQuoteByWhatsApp();
    shareQuoteByEmail();
};

const sendQuote = () => {
    sendQuoteForm.channel = sendChannel.value;

    sendQuoteForm.post(route('quotes.send', { ...tenantRouteParams.value, quote: props.quote.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (sendChannel.value === 'whatsapp') {
                shareQuoteByWhatsApp();
            } else if (sendChannel.value === 'email') {
                shareQuoteByEmail();
            } else if (sendChannel.value === 'both') {
                shareQuoteByBoth();
            }
        },
    });
};

const showApproveManuallyModal = ref(false);
const approveManuallyForm = useForm({ channel: '' });

const submitApproveManually = (channel) => {
    approveManuallyForm.channel = channel;
    approveManuallyForm.post(route('quotes.approve-manually', { ...tenantRouteParams.value, quote: props.quote.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showApproveManuallyModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Cotización #${quote.id} — ${quote.vehicle?.plate ?? ''}`" />

    <TallerLayout>
        <div class="animate-in fade-in slide-in-from-bottom-4 duration-700 space-y-6">

            <!-- Header -->
            <div class="space-y-3">
                <Link :href="route('quotes.index', tenantRouteParams)"
                    class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-gray-400 transition-colors hover:text-gray-700">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    Cotizaciones
                </Link>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', quoteStatus.classes]">
                            Cotización {{ quoteStatus.label }}
                        </span>
                        <Link v-if="quote.work_order"
                            :href="route('work-orders.show', { ...tenantRouteParams, workOrder: quote.work_order.id })"
                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-600 transition-colors hover:bg-emerald-100">
                            OT #{{ quote.work_order.id }} generada
                        </Link>
                    </div>
                </div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-gray-900">
                            {{ quote.vehicle?.brand }} {{ quote.vehicle?.model }}
                        </h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                            <span class="font-mono text-base font-bold tracking-widest text-gray-700">{{ quote.vehicle?.plate }}</span>
                            <span class="text-gray-300">·</span>
                            <span>{{ quote.client?.name ?? 'Cliente no asignado' }}</span>
                            <template v-if="quote.client?.phone">
                                <span class="text-gray-300">·</span>
                                <span>{{ quote.client.phone }}</span>
                            </template>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-black uppercase tracking-widest text-gray-300">Cotización #{{ quote.id }}</span>
                </div>

                <p v-if="quote.notes" class="text-sm italic text-gray-400">{{ quote.notes }}</p>

                <div v-if="quote.customer_response_notes" class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-500">Respuesta Cliente</p>
                    <p class="mt-1.5 text-sm font-medium text-amber-900">{{ quote.customer_response_notes }}</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div v-if="commercialQuotesEnabled" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px] xl:items-start">

                <!-- SIDEBAR -->
                <div class="space-y-4 xl:order-last xl:sticky xl:top-6">

                    <!-- Total -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Cotización</p>
                        <p class="mt-1.5 text-3xl font-black text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</p>
                        <p v-if="formatUf(quote.subtotal_amount)" class="mt-0.5 text-xs font-semibold text-gray-400">≈ UF {{ formatUf(quote.subtotal_amount) }}</p>
                        <p class="mt-1.5 text-xs font-medium text-gray-400">{{ items.length }} ítems cargados</p>
                    </div>

                    <!-- Canal de envío + botón -->
                    <template v-if="canDeliverQuote">
                        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                            <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Canal de envío</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors" :class="sendChannel === 'manual' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'" @click="sendChannel = 'manual'">Solo marcar</button>
                                <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'whatsapp' ? 'bg-green-600 text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientPhone" @click="sendChannel = 'whatsapp'">WhatsApp</button>
                                <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'email' ? 'bg-sky-600 text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientEmail" @click="sendChannel = 'email'">Email</button>
                                <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'both' ? 'bg-[#FF7A00] text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientPhone || !hasClientEmail" @click="sendChannel = 'both'">Ambos</button>
                            </div>
                        </div>
                        <button type="button"
                            class="w-full rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-[#FF7A00] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="sendQuoteForm.processing || items.length === 0 || quote.status === 'accepted'"
                            @click="sendQuote">
                            {{ sendQuoteForm.processing ? 'Enviando...' : sendChannel === 'whatsapp' ? 'Enviar por WhatsApp' : sendChannel === 'email' ? 'Enviar por Email' : sendChannel === 'both' ? 'Enviar por Ambos' : 'Marcar como Enviada' }}
                        </button>

                        <button type="button"
                            class="w-full rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="items.length === 0 || quote.status === 'accepted'"
                            @click="showApproveManuallyModal = true">
                            Aprobar manualmente
                        </button>
                        <p v-if="quote.status !== 'accepted'" class="text-center text-[10px] font-medium text-gray-400">Crea la Orden de Trabajo automáticamente al aprobar.</p>
                    </template>

                    <!-- Compartir (pending_customer) -->
                    <template v-if="canShareQuote">
                        <a v-if="hasClientPhone" :href="whatsAppLink" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-green-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-green-600">Compartir por WhatsApp</a>
                        <a v-if="hasClientEmail" :href="mailToLink" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-sky-700">Compartir por Email</a>
                        <button v-if="hasClientPhone && hasClientEmail" type="button" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#FF7A00] px-5 py-3 text-sm font-black text-white transition-colors hover:bg-[#CC6200]" @click="shareQuoteByBoth">Compartir por Ambos</button>
                    </template>

                    <!-- Tracking -->
                    <a :href="trackingUrl" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50">
                        Ver Vista del Cliente
                    </a>

                    <!-- Historial -->
                    <div class="hidden xl:block overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-50 px-5 py-4">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Historial Comercial</h2>
                            <span class="text-[10px] font-bold text-gray-400">{{ quote.events?.length ?? 0 }} eventos</span>
                        </div>
                        <div class="p-5">
                            <div v-if="!(quote.events?.length)" class="rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">Sin historial todavía</div>
                            <div v-else class="space-y-3">
                                <div v-for="event in quote.events" :key="event.id" class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">{{ event.description }}</p>
                                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ event.actor_type }} · {{ event.event_type }}</p>
                                        </div>
                                        <span class="shrink-0 text-[10px] font-semibold text-gray-400">{{ formatDateTime(event.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTENIDO PRINCIPAL -->
                <div class="space-y-6 xl:order-first">

                    <!-- Tabla de ítems -->
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
                                            <p class="text-sm font-semibold text-gray-800">{{ item.description }}</p>
                                            <p v-if="item.product?.sku" class="mt-0.5 text-[10px] font-mono text-gray-400">{{ item.product.sku }}</p>
                                            <p v-if="item.service?.code" class="mt-0.5 text-[10px] font-mono text-gray-400">{{ item.service.code }}</p>
                                            <p v-if="Number(item.discount_percent) > 0" class="mt-0.5 text-[10px] font-black uppercase tracking-widest text-rose-500">
                                                Desc. {{ item.discount_percent }}% · ahorro {{ formatCurrency(item.discount_amount) }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                                {{ quoteItemTypeLabels[item.item_type] ?? item.item_type }}
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
                                    <tr class="border-t-2 border-gray-100 bg-gray-50/50">
                                        <td colspan="4" class="px-6 py-4 text-right text-sm font-black uppercase tracking-wider text-gray-600">Total Cotización</td>
                                        <td class="px-4 py-4 text-right">
                                            <span class="text-lg font-black tabular-nums text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</span>
                                            <span v-if="formatUf(quote.subtotal_amount)" class="block text-[10px] font-semibold tabular-nums text-gray-400">UF {{ formatUf(quote.subtotal_amount) }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Formulario agregar ítem -->
                    <div v-if="canManageItems" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Agregar Ítem</h2>
                            <Link :href="route('services.index', tenantRouteParams)" class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00] hover:text-[#CC6200]">Gestionar Servicios</Link>
                        </div>

                        <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Política de descuentos</p>
                            <p class="mt-1.5 text-sm font-medium text-amber-900">
                                Hasta {{ props.discountPolicy?.threshold ?? 0 }}% sin aprobación. Sobre ese umbral requiere rol {{ (props.discountPolicy?.approver_roles || []).join(' o ') }}.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed" :class="selectedMode === 'manual' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'" :disabled="hasSelectedProduct || hasSelectedService" @click="selectMode('manual')">Manual</button>
                            <button type="button" class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed" :class="selectedMode === 'product' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'" :disabled="hasManualInput || hasSelectedService" @click="selectMode('product')">Repuesto</button>
                            <button type="button" class="rounded-xl px-3 py-2.5 text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-40 disabled:cursor-not-allowed" :class="selectedMode === 'service' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'" :disabled="hasManualInput || hasSelectedProduct" @click="selectMode('service')">Servicio</button>
                        </div>

                        <form class="mt-5 space-y-4" @submit.prevent="submitItem">
                            <div v-if="selectedMode === 'product'" class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Buscar Repuesto</label>
                                <div class="relative">
                                    <input v-model="productSearch" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300 pr-10" placeholder="Nombre o SKU" @focus="showProductDropdown = true" @blur="setTimeout(() => { showProductDropdown = false; }, 200)" />
                                    <button v-if="hasSelectedProduct" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" @click="clearProduct">
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
                                    <button v-if="hasSelectedService" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" @click="clearService">
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

                    <!-- Historial (mobile) -->
                    <div class="xl:hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-50 px-6 py-4">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Historial Comercial</h2>
                            <span class="text-[10px] font-bold text-gray-400">{{ quote.events?.length ?? 0 }} eventos</span>
                        </div>
                        <div class="p-6">
                            <div v-if="!(quote.events?.length)" class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">Sin historial todavía</div>
                            <div v-else class="space-y-3">
                                <div v-for="event in quote.events" :key="event.id" class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">{{ event.description }}</p>
                                            <p class="mt-0.5 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ event.actor_type }} · {{ event.event_type }}</p>
                                        </div>
                                        <span class="shrink-0 text-[10px] font-semibold text-gray-400">{{ formatDateTime(event.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner upgrade -->
            <div v-else class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/70 p-8">
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                <h2 class="mt-4 text-2xl font-black tracking-tight text-gray-900">Cotizaciones manuales bloqueadas para este plan</h2>
                <p class="mt-2 text-sm font-medium text-gray-600">{{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}</p>
            </div>
        </div>

        <!-- Approve Manually Modal -->
        <div v-if="showApproveManuallyModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showApproveManuallyModal = false"></div>

            <div class="relative w-full max-w-md overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white p-6 shadow-2xl animate-in zoom-in-95 duration-300">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">¿Cómo aprobó el cliente?</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">El cliente aprobó la cotización por un medio externo. Selecciona cómo fue confirmada para dejar registro. Se generará la Orden de Trabajo automáticamente.</p>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 transition-colors hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="approveManuallyForm.processing" @click="submitApproveManually('phone')">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        Llamada Telefónica
                    </button>

                    <button type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 transition-colors hover:border-green-200 hover:bg-green-50 hover:text-green-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="approveManuallyForm.processing" @click="submitApproveManually('whatsapp')">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </button>

                    <button type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 transition-colors hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="approveManuallyForm.processing" @click="submitApproveManually('email')">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        Mail Directo
                    </button>

                    <button type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="approveManuallyForm.processing" @click="submitApproveManually('other')">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Otro Medio
                    </button>
                </div>

                <div class="mt-5">
                    <button type="button" class="w-full rounded-2xl bg-gray-100 py-3 text-xs font-black uppercase tracking-widest text-gray-600 transition-colors hover:bg-gray-200" @click="showApproveManuallyModal = false">Cancelar</button>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
