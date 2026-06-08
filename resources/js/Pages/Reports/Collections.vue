<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PrintableReportShell from '@/Components/PrintableReportShell.vue';
import ReportsNavigation from '@/Components/ReportsNavigation.vue';
import ReportPrintButton from '@/Components/ReportPrintButton.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    summary: Object,
    agingBuckets: Array,
    topDebtors: Array,
    criticalInvoices: Array,
    followUpInvoices: Array,
});

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
    <Head title="Reporte de Cobranza" />

    <TallerLayout>
        <PrintableReportShell>
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Caja</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reporte de Cobranza</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Mora, clientes de mayor riesgo y seguimiento de cobranza.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('invoices.index', tenantRouteParams)"
                        class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Ver facturas
                    </Link>
                    <ReportPrintButton />
                </div>
            </div>

            <ReportsNavigation />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Totales</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Abiertas</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.open_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vencidas</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.overdue_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Saldo por Cobrar</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.amount_due) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto en Mora</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ formatCurrency(summary.overdue_amount) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Promedio Días Mora</p>
                    <p class="mt-3 text-4xl font-black text-cyan-600">{{ summary.average_days_overdue }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div v-for="bucket in agingBuckets" :key="bucket.label" class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ bucket.label }}</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ bucket.count }}</p>
                    <p class="mt-2 text-sm font-black text-rose-500">{{ formatCurrency(bucket.amount) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Críticas</h2>
                    </div>

                    <div v-if="criticalInvoices.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                        No hay facturas vencidas.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">Factura</th>
                                    <th class="px-4 py-4 text-left">Cliente</th>
                                    <th class="px-4 py-4 text-right">Mora</th>
                                    <th class="px-4 py-4 text-right">Saldo</th>
                                    <th class="px-4 py-4 text-right">Recordatorios</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="invoice in criticalInvoices" :key="invoice.id" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4">
                                        <Link :href="route('invoices.show', { ...tenantRouteParams, clientInvoice: invoice.id })" class="text-sm font-black text-gray-900 hover:text-[#FF7A00]">
                                            {{ invoice.invoice_number || `Factura #${invoice.id}` }}
                                        </Link>
                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">vence {{ formatDate(invoice.due_at) }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-500">{{ invoice.client_name }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-rose-500">{{ invoice.days_overdue }} días</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ formatCurrency(invoice.amount_due) }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ invoice.whatsapp_reminder_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Principales Deudores</h2>
                    </div>

                    <div v-if="topDebtors.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin deuda vencida
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="debtor in topDebtors"
                            :key="debtor.client_id"
                            :href="route('clients.show', { ...tenantRouteParams, client: debtor.client_id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ debtor.client_name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ debtor.invoice_count }} factura{{ debtor.invoice_count !== 1 ? 's' : '' }} vencida{{ debtor.invoice_count !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <span class="text-sm font-black text-rose-500">{{ formatCurrency(debtor.amount_due) }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Seguimiento con Recordatorios</h2>
                </div>

                <div v-if="followUpInvoices.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                    Sin recordatorios enviados aún
                </div>

                <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Link
                        v-for="invoice in followUpInvoices"
                        :key="invoice.id"
                        :href="route('invoices.show', { ...tenantRouteParams, clientInvoice: invoice.id })"
                        class="rounded-2xl border border-gray-100 bg-gray-50/70 px-5 py-4 transition-colors hover:bg-gray-50"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ invoice.invoice_number || `Factura #${invoice.id}` }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ invoice.client_name }} · vence {{ formatDate(invoice.due_at) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-rose-500">{{ formatCurrency(invoice.amount_due) }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ invoice.whatsapp_reminder_count }} recordatorios</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </PrintableReportShell>
    </TallerLayout>
</template>
