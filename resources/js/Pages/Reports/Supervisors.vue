<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    summary: Object,
    discountThreshold: Number,
    recentDiscounts: Array,
    overdueInvoices: Array,
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
    <Head title="Reportes de Supervisión" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Supervisión</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reportes de Supervisores</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Descuentos aplicados, cotizaciones pendientes y foco de cobranza.</p>
                </div>

                <Link
                    :href="route('reports.sales', tenantRouteParams)"
                    class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Volver a ventas
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Umbral Configurado</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ discountThreshold }}%</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Ítems con Descuento</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.discounted_items }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Sobre Umbral</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.high_discount_items }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Impacto Descuentos</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ formatCurrency(summary.high_discount_amount) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones Pendientes</p>
                    <p class="mt-3 text-4xl font-black text-cyan-500">{{ summary.pending_quotes }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Descuentos Recientes</h2>
                    </div>

                    <div v-if="recentDiscounts.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                        No hay descuentos registrados.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">Ítem</th>
                                    <th class="px-4 py-4 text-left">Cliente</th>
                                    <th class="px-4 py-4 text-right">% Desc.</th>
                                    <th class="px-4 py-4 text-right">Impacto</th>
                                    <th class="px-4 py-4 text-right">OT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in recentDiscounts" :key="item.id" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4 text-sm font-semibold text-gray-700">{{ item.description }}</td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-500">{{ item.client_name || 'Sin cliente' }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-amber-500">{{ item.discount_percent }}%</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-rose-500">{{ formatCurrency(item.discount_amount) }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">#{{ item.work_order_id }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Críticas</h2>
                    </div>

                    <div v-if="overdueInvoices.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin mora crítica
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
