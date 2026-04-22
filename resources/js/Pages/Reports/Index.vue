<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    summary: Object,
    topClients: Array,
    recentQuotes: Array,
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
    <Head title="Reportes" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Reportes Comerciales</h1>
                    <p class="mt-1 text-sm font-medium text-gray-500">Visión general del desempeño de cotizaciones por cliente y taller.</p>
                </div>

                <Link
                    :href="route('work-orders.index', tenantRouteParams)"
                    class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Ir al Kanban
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones Totales</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_quotes }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Incluye borradores, enviadas y resueltas.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto Cotizado</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.quoted_amount) }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Valor total propuesto a clientes.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto Aceptado</p>
                    <p class="mt-3 text-4xl font-black text-emerald-600">{{ formatCurrency(summary.accepted_amount) }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Trabajo aprobado por clientes.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Pendientes Cliente</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.pending_quotes }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Aceptadas</p>
                    <p class="mt-3 text-4xl font-black text-emerald-500">{{ summary.accepted_quotes }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Rechazadas</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.rejected_quotes }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes con Mayor Facturación Cotizada</h2>
                    </div>

                    <div v-if="topClients.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Aún no hay datos suficientes
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="client in topClients"
                            :key="client.id"
                            :href="route('clients.show', { ...tenantRouteParams, client: client.id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ client.name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ client.quotes }} cotizaciones · {{ client.accepted_quotes }} aceptadas
                                </p>
                            </div>
                            <span class="text-sm font-black text-gray-900">{{ formatCurrency(client.quoted_amount) }}</span>
                        </Link>
                    </div>
                </div>

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
            </div>
        </div>
    </TallerLayout>
</template>
