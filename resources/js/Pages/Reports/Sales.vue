<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    summary: Object,
    recentQuotes: Array,
    overdueInvoices: Array,
});

const quoteStatusLabels = {
    draft: 'Borrador',
    pending_customer: 'Pendiente cliente',
    accepted: 'Aceptada',
    rejected: 'Rechazada',
};

const formatCurrency = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Reportes de Ventas" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Comercial</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reportes de Ventas</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Seguimiento de cotizaciones, aceptaciones y cartera vencida.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('reports.supervisors', tenantRouteParams)"
                        class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Ver reportes de supervisión
                    </Link>
                    <Link
                        :href="route('invoices.index', tenantRouteParams)"
                        class="inline-flex items-center rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-gray-800"
                    >
                        Ver facturas
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones Totales</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_quotes }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto Cotizado</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.quoted_amount) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto Aceptado</p>
                    <p class="mt-3 text-4xl font-black text-emerald-600">{{ formatCurrency(summary.accepted_amount) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones Aceptadas</p>
                    <p class="mt-3 text-4xl font-black text-emerald-500">{{ summary.accepted_quotes }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Atrasadas</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.overdue_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto en Mora</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ formatCurrency(summary.overdue_amount) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones Recientes</h2>
                    </div>

                    <div v-if="recentQuotes.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                        No hay cotizaciones recientes.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">OT</th>
                                    <th class="px-4 py-4 text-left">Cliente</th>
                                    <th class="px-4 py-4 text-left">Patente</th>
                                    <th class="px-4 py-4 text-left">Estado</th>
                                    <th class="px-4 py-4 text-right">Monto</th>
                                    <th class="px-4 py-4 text-right">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="quote in recentQuotes" :key="quote.id" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4 text-sm font-black text-gray-900">#{{ quote.work_order_id }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ quote.client_name || 'Sin cliente' }}</td>
                                    <td class="px-4 py-4 font-mono text-sm font-bold tracking-widest text-[#F9A826]">{{ quote.plate || 'N/A' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-gray-600">
                                            {{ quoteStatusLabels[quote.status] || quote.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</td>
                                    <td class="px-4 py-4 text-right text-xs font-medium text-gray-500">{{ formatDate(quote.responded_at || quote.sent_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Atrasadas</h2>
                        <Link :href="route('invoices.index', tenantRouteParams)" class="text-[10px] font-black uppercase tracking-widest text-[#F9A826] hover:text-[#dd9219]">
                            Ver todas
                        </Link>
                    </div>

                    <div v-if="overdueInvoices.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        No hay facturas en mora
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="invoice in overdueInvoices"
                            :key="invoice.id"
                            :href="route('invoices.show', { ...tenantRouteParams, clientInvoice: invoice.id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ invoice.invoice_number || `Factura #${invoice.id}` }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ invoice.client_name }} · vence {{ formatDate(invoice.due_at) }}
                                </p>
                            </div>
                            <span class="text-sm font-black text-rose-500">{{ formatCurrency(invoice.amount_due) }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
