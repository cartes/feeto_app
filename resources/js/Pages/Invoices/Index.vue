<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    invoices: Object,
    summary: Object,
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
    <Head title="Facturas" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Cobranza</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Facturas del Taller</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Seguimiento de saldos pendientes y mora por cliente.</p>
                </div>

                <Link
                    :href="route('reports.sales', tenantRouteParams)"
                    class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Volver a reportes
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Totales</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Atrasadas</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.overdue_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Saldo Pendiente</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.amount_due) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-8 py-6">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Listado de Facturas</h2>
                </div>

                <div v-if="invoices.data.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                    Todavía no hay facturas registradas.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <th class="px-8 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-left">Estado</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 text-right">Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="transition-colors hover:bg-gray-50/40">
                                <td class="px-8 py-4 text-sm font-black text-gray-900">
                                    <Link :href="route('invoices.show', { ...tenantRouteParams, clientInvoice: invoice.id })" class="hover:text-[#F9A826]">
                                        {{ invoice.invoice_number || `Factura #${invoice.id}` }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ invoice.client?.name || 'Sin cliente' }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-gray-600">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ formatCurrency(invoice.amount_total) }}</td>
                                <td class="px-4 py-4 text-right text-sm font-black text-rose-500">{{ formatCurrency(invoice.amount_due) }}</td>
                                <td class="px-4 py-4 text-right text-xs font-medium text-gray-500">{{ formatDate(invoice.due_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
