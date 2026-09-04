<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';
import { useIdentification } from '@/composables/useIdentification';

const page = usePage();
const { tenantRouteParams } = useTenantRouting();
const { formatCurrency, formatDate, formatDateTime } = useFormatting();
const { getCountryConfig } = useIdentification();

const tenantCountry = computed(() => page.props.tenantContext?.country || 'CL');
const countryConfig = computed(() => getCountryConfig(tenantCountry.value));
const docLabel = computed(() => countryConfig.value.docName);

const props = defineProps({
    client: Object,
    vehicles: Array,
    historyTimeline: Array,
    internalNotes: Array,
    quoteSummary: Object,
    invoiceSummary: Object,
    invoices: Array,
    salesManagementEnabled: Boolean,
    crmMetrics: Object,
});

const activeTab = ref('historial');

const invoiceForm = useForm({
    client_id: props.client.id,
    invoice_number: '',
    amount_total: '',
    amount_due: '',
    issued_at: new Date().toISOString().slice(0, 10),
    due_at: '',
    notes: '',
});

const noteForm = useForm({
    content: '',
});

const clientWhatsAppLink = computed(() => {
    const phone = props.client.phone ?? '';
    const amountDue = props.invoiceSummary?.overdue_amount || props.invoiceSummary?.amount_due || 0;

    if (!phone) {
        return null;
    }

    const message = encodeURIComponent(
        `Hola ${props.client.name}, te contactamos desde el taller para hacer seguimiento a tu saldo pendiente por ${formatCurrency(amountDue)}.`
    );

    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${message}`;
});

const noteAuthorInitial = (note) => (note.user?.name?.charAt(0) ?? 'N').toUpperCase();
const clientInitial = computed(() => props.client.name?.charAt(0)?.toUpperCase() ?? 'C');

const eventTypeClasses = (type) => {
    switch (type) {
        case 'work_order':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'appointment':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'internal_note':
            return 'border-purple-200 bg-purple-50 text-purple-700';
        default:
            return 'border-gray-200 bg-gray-50 text-gray-700';
    }
};

const eventStatusClasses = (type) => {
    switch (type) {
        case 'work_order':
            return 'bg-gray-900 text-white';
        case 'appointment':
            return 'bg-sky-600 text-white';
        case 'internal_note':
            return 'bg-purple-600 text-white';
        default:
            return 'bg-gray-500 text-white';
    }
};

const submitInvoice = () => {
    invoiceForm.post(route('invoices.store', tenantRouteParams.value), {
        preserveScroll: true,
        onSuccess: () => {
            invoiceForm.reset();
            invoiceForm.client_id = props.client.id;
            invoiceForm.issued_at = new Date().toISOString().slice(0, 10);
        },
    });
};

const submitNote = () => {
    noteForm.post(route('clients.notes.store', { ...tenantRouteParams.value, client: props.client.id }), {
        preserveScroll: true,
        onSuccess: () => noteForm.reset(),
    });
};
</script>

<template>
    <Head :title="`Perfil de Cliente | ${client.name}`" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <Link :href="route('clients.index', tenantRouteParams)"
                    class="inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-gray-500 transition-colors hover:text-[#FF7A00]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al directorio
                </Link>

                <a v-if="clientWhatsAppLink" :href="clientWhatsAppLink" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-2xl bg-green-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-green-600">
                    Seguimiento por WhatsApp
                </a>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <aside class="space-y-6 rounded-[2.5rem] border border-gray-100 bg-white p-8 shadow-sm lg:col-span-1">
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="mb-4 flex h-24 w-24 items-center justify-center rounded-3xl bg-[#FF7A00]/10 text-3xl font-black uppercase tracking-widest text-[#FF7A00]">
                            {{ clientInitial }}
                        </div>
                        <h1 class="text-2xl font-black uppercase tracking-tight text-gray-900">{{ client.name }}</h1>
                        <p class="mt-1 text-xs font-bold uppercase tracking-widest text-gray-400">Perfil CRM unificado</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-2xl bg-gray-50/80 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ docLabel }}</p>
                            <p class="mt-1 text-sm font-bold text-gray-900">{{ client.rut }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Teléfono</p>
                            <p class="mt-1 text-sm font-bold text-gray-900">{{ client.phone || 'No registrado' }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Email</p>
                            <p class="mt-1 break-all text-sm font-bold text-gray-900">{{ client.email || 'No registrado' }}</p>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Crédito máximo</p>
                            <p class="mt-1 text-sm font-bold text-gray-900">{{ formatCurrency(client.max_credit_limit) }}</p>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-gray-100 bg-gray-50/70 p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Resumen de relación</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cliente desde</p>
                                <p class="mt-2 text-sm font-black text-gray-900">{{ formatDate(client.created_at) }}</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículos</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ crmMetrics.vehicles_count }}</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Visitas</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ crmMetrics.visits_count }}</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notas</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ crmMetrics.notes_count }}</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="space-y-8 lg:col-span-2">
                    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Gasto total</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ formatCurrency(crmMetrics.total_spent) }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Última visita</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ formatDate(crmMetrics.last_visit) }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Ticket promedio</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ formatCurrency(crmMetrics.average_ticket) }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículos asociados</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ crmMetrics.vehicles_count }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Visitas registradas</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ crmMetrics.visits_count }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notas internas</p>
                            <p class="mt-2 text-3xl font-black text-gray-900">{{ crmMetrics.notes_count }}</p>
                        </div>
                    </section>

                    <section class="rounded-[2.5rem] border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ quoteSummary.total_quotes }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ quoteSummary.accepted_quotes }} aceptadas</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto cotizado</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ formatCurrency(quoteSummary.quoted_amount) }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ quoteSummary.pending_quotes }} pendientes</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto aceptado</p>
                                <p class="mt-2 text-2xl font-black text-emerald-600">{{ formatCurrency(quoteSummary.accepted_amount) }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ quoteSummary.rejected_quotes }} rechazadas</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2.5rem] border border-gray-100 bg-white p-8 shadow-sm">
                        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Crédito y cobranza</p>
                                <h2 class="mt-2 text-xl font-black uppercase tracking-tight text-gray-900">Facturación del cliente</h2>
                                <p class="mt-2 text-sm font-medium text-gray-500">Controla su línea de crédito, saldo pendiente y recordatorios manuales.</p>
                            </div>

                            <form v-if="salesManagementEnabled"
                                class="grid w-full gap-3 rounded-3xl border border-gray-100 bg-gray-50 p-5 xl:max-w-xl xl:grid-cols-2"
                                @submit.prevent="submitInvoice">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Número factura</label>
                                    <input v-model="invoiceForm.invoice_number" type="text"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                        placeholder="FAC-001" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto total</label>
                                    <input v-model="invoiceForm.amount_total" type="number" min="1"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                                    <p v-if="invoiceForm.errors.amount_total" class="text-xs text-rose-500">{{ invoiceForm.errors.amount_total }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Saldo pendiente</label>
                                    <input v-model="invoiceForm.amount_due" type="number" min="0"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Emisión</label>
                                    <input v-model="invoiceForm.issued_at" type="date"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vencimiento</label>
                                    <input v-model="invoiceForm.due_at" type="date"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                                    <p v-if="invoiceForm.errors.due_at" class="text-xs text-rose-500">{{ invoiceForm.errors.due_at }}</p>
                                </div>
                                <div class="space-y-1 xl:col-span-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notas</label>
                                    <textarea v-model="invoiceForm.notes" rows="3"
                                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                        placeholder="Detalle del trabajo, condiciones o seguimiento..." />
                                </div>
                                <div class="flex justify-end xl:col-span-2">
                                    <button type="submit" :disabled="invoiceForm.processing"
                                        class="rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-gray-800 disabled:opacity-50">
                                        {{ invoiceForm.processing ? 'Registrando...' : 'Registrar factura' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div v-if="salesManagementEnabled" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ invoiceSummary.total_invoices }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto total facturado</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ formatCurrency(invoiceSummary.total_amount) }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto en mora</p>
                                <p class="mt-2 text-2xl font-black text-rose-500">{{ formatCurrency(invoiceSummary.overdue_amount) }}</p>
                            </div>
                        </div>

                        <div v-if="salesManagementEnabled" class="mt-6 space-y-3">
                            <div v-if="invoices.length === 0"
                                class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm font-medium text-gray-500">
                                Todavía no hay facturas registradas para este cliente.
                            </div>

                            <Link v-for="invoice in invoices" :key="invoice.id"
                                :href="route('invoices.show', { ...tenantRouteParams, clientInvoice: invoice.id })"
                                class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50">
                                <div>
                                    <p class="text-sm font-black text-gray-900">{{ invoice.invoice_number || `Factura #${invoice.id}` }}</p>
                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                        {{ invoice.status }} · vence {{ formatDate(invoice.due_at) }}
                                    </p>
                                </div>
                                <span class="text-sm font-black text-rose-500">{{ formatCurrency(invoice.amount_due) }}</span>
                            </Link>
                        </div>
                    </section>

                    <section class="space-y-6">
                        <div class="flex flex-wrap gap-6 border-b border-gray-200" data-support="client-tabs">
                            <button @click="activeTab = 'historial'"
                                :class="['pb-4 text-sm font-bold uppercase tracking-widest transition-colors', activeTab === 'historial' ? 'border-b-2 border-[#FF7A00] text-[#FF7A00]' : 'text-gray-400 hover:text-gray-600']">
                                Timeline CRM
                            </button>
                            <button @click="activeTab = 'vehiculos'"
                                :class="['pb-4 text-sm font-bold uppercase tracking-widest transition-colors', activeTab === 'vehiculos' ? 'border-b-2 border-[#FF7A00] text-[#FF7A00]' : 'text-gray-400 hover:text-gray-600']">
                                Vehículos
                            </button>
                            <button @click="activeTab = 'notas'"
                                :class="['pb-4 text-sm font-bold uppercase tracking-widest transition-colors', activeTab === 'notas' ? 'border-b-2 border-[#FF7A00] text-[#FF7A00]' : 'text-gray-400 hover:text-gray-600']">
                                Notas internas
                            </button>
                        </div>

                        <div v-show="activeTab === 'historial'" class="rounded-[2.5rem] border border-gray-200 bg-white p-8 shadow-sm">
                            <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-6">
                                <span class="h-2.5 w-2.5 rounded-full bg-[#FF7A00]"></span>
                                <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Historial unificado</h3>
                            </div>

                            <div v-if="historyTimeline.length === 0" class="py-10 text-center">
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-500">Sin eventos</p>
                                <p class="mt-1 text-xs text-gray-400">Este cliente aún no registra visitas ni actividad interna.</p>
                            </div>

                            <div v-else class="space-y-4">
                                <article v-for="event in historyTimeline" :key="event.id"
                                    class="rounded-[2rem] border border-gray-100 bg-gray-50/70 p-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="space-y-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-widest"
                                                    :class="eventTypeClasses(event.type)">
                                                    {{ event.type_label }}
                                                </span>
                                                <span v-if="event.status_label"
                                                    class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-widest"
                                                    :class="eventStatusClasses(event.type)">
                                                    {{ event.status_label }}
                                                </span>
                                                <span v-if="event.quote"
                                                    class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-600">
                                                    Cotización {{ event.quote.status }}
                                                </span>
                                            </div>

                                            <div>
                                                <h4 class="text-lg font-black uppercase tracking-tight text-gray-900">{{ event.title }}</h4>
                                                <p class="mt-1 text-sm font-semibold text-gray-500">{{ event.subtitle }}</p>
                                            </div>

                                            <p class="max-w-2xl text-sm leading-relaxed text-gray-600">{{ event.description }}</p>

                                            <div class="flex flex-wrap gap-3 text-xs font-bold uppercase tracking-widest text-gray-400">
                                                <span>{{ formatDateTime(event.occurred_at) }}</span>
                                                <span v-if="event.amount !== null" class="text-gray-500">{{ formatCurrency(event.amount) }}</span>
                                                <a v-if="event.tracking_url" :href="event.tracking_url" target="_blank"
                                                    class="text-blue-600 transition-colors hover:text-blue-700">
                                                    Ver tracking
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div v-show="activeTab === 'vehiculos'" class="rounded-[2.5rem] border border-gray-100 bg-gray-50 p-8">
                            <div class="mb-6 flex items-center gap-3">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Vehículos asociados</h3>
                            </div>

                            <div v-if="vehicles.length === 0" class="py-6 text-center">
                                <p class="text-sm font-medium text-gray-500">Este cliente no tiene vehículos asociados.</p>
                            </div>

                            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div v-for="vehicle in vehicles" :key="vehicle.id"
                                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                                    <div class="mb-3 flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-lg font-black uppercase text-gray-900">
                                                {{ vehicle.brand || 'Vehículo' }}
                                                <span class="text-gray-500">{{ vehicle.model || 'Sin modelo' }}</span>
                                            </h4>
                                            <p class="mt-1 font-mono text-sm font-bold tracking-widest text-[#FF7A00]">{{ vehicle.plate }}</p>
                                        </div>
                                        <span
                                            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                            {{ vehicle.work_orders_count }} OT
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full border border-gray-100 bg-gray-50 px-2.5 py-1 font-semibold text-gray-600">
                                            Color: {{ vehicle.color || 'N/A' }}
                                        </span>
                                        <span class="rounded-full border border-gray-100 bg-gray-50 px-2.5 py-1 font-semibold text-gray-600">
                                            VIN: {{ vehicle.vin || 'N/A' }}
                                        </span>
                                    </div>

                                    <p class="mt-4 text-xs font-bold uppercase tracking-widest text-gray-400">
                                        Última OT: {{ formatDate(vehicle.last_work_order_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeTab === 'notas'" class="rounded-[2.5rem] border border-gray-200 bg-white p-8 shadow-sm">
                            <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-6">
                                <span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                                <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">Notas internas</h3>
                            </div>

                            <form @submit.prevent="submitNote" class="mb-8 rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <textarea v-model="noteForm.content" rows="3"
                                    placeholder="Escribe una nota interna sobre el cliente..."
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-[#FF7A00]"></textarea>
                                <p v-if="noteForm.errors.content" class="mt-2 text-xs text-rose-500">{{ noteForm.errors.content }}</p>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit" :disabled="noteForm.processing"
                                        class="rounded-2xl bg-gray-900 px-5 py-2 text-sm font-black text-white transition-colors hover:bg-gray-800 disabled:opacity-50">
                                        {{ noteForm.processing ? 'Guardando...' : 'Agregar nota' }}
                                    </button>
                                </div>
                            </form>

                            <div v-if="internalNotes.length === 0" class="py-10 text-center">
                                <p class="text-sm font-bold uppercase tracking-widest text-gray-500">Sin notas</p>
                                <p class="mt-1 text-xs text-gray-400">No hay notas internas registradas para este cliente.</p>
                            </div>

                            <div v-else class="space-y-4">
                                <article v-for="note in internalNotes" :key="note.id"
                                    class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs font-bold uppercase text-gray-600">
                                                {{ noteAuthorInitial(note) }}
                                            </div>
                                            <span class="text-sm font-bold text-gray-900">{{ note.user?.name || 'Equipo interno' }}</span>
                                        </div>
                                        <span class="text-xs font-medium text-gray-400">{{ formatDateTime(note.created_at) }}</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700">{{ note.content }}</p>
                                </article>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
