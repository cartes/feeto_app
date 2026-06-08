<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import ReportsNavigation from '@/Components/ReportsNavigation.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

defineProps({
    summary: Object,
    topClients: Array,
    inactiveClients: Array,
    overdueClients: Array,
});

const formatCurrency = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) {
        return 'Sin visitas';
    }

    return new Date(value).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Reporte de Clientes" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">CRM</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reporte de Clientes</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Actividad, valor generado y foco comercial de la cartera del taller.</p>
                </div>

                <Link
                    :href="route('clients.index', tenantRouteParams)"
                    class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Ver directorio
                </Link>
            </div>

            <ReportsNavigation />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes Totales</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_clients }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Activos 90 Días</p>
                    <p class="mt-3 text-4xl font-black text-emerald-500">{{ summary.active_clients }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Inactivos</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.inactive_clients }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Con Mora</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.clients_with_overdue_invoices }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Valor Histórico</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.lifetime_value) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Ticket Promedio</p>
                    <p class="mt-3 text-4xl font-black text-cyan-600">{{ formatCurrency(summary.average_ticket) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes con Mayor Valor</h2>
                    </div>

                    <div v-if="topClients.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                        Aún no hay clientes con historial suficiente.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">Cliente</th>
                                    <th class="px-4 py-4 text-right">Visitas</th>
                                    <th class="px-4 py-4 text-right">Vehículos</th>
                                    <th class="px-4 py-4 text-right">Gasto</th>
                                    <th class="px-4 py-4 text-right">Última visita</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="client in topClients" :key="client.id" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4">
                                        <Link :href="route('clients.show', { ...tenantRouteParams, client: client.id })" class="text-sm font-black text-gray-900 hover:text-[#FF7A00]">
                                            {{ client.name }}
                                        </Link>
                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ client.rut }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ client.metrics.visits_count }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ client.metrics.vehicles_count }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ formatCurrency(client.metrics.total_spent) }}</td>
                                    <td class="px-4 py-4 text-right text-xs font-medium text-gray-500">{{ formatDate(client.metrics.last_visit) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes con Mora</h2>
                    </div>

                    <div v-if="overdueClients.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin mora activa
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="client in overdueClients"
                            :key="client.client_id"
                            :href="route('clients.show', { ...tenantRouteParams, client: client.client_id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ client.client_name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ client.invoice_count }} factura{{ client.invoice_count !== 1 ? 's' : '' }} vencida{{ client.invoice_count !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <span class="text-sm font-black text-rose-500">{{ formatCurrency(client.amount_due) }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes para Reactivar</h2>
                </div>

                <div v-if="inactiveClients.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                    No hay clientes inactivos para seguimiento
                </div>

                <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Link
                        v-for="client in inactiveClients"
                        :key="client.id"
                        :href="route('clients.show', { ...tenantRouteParams, client: client.id })"
                        class="rounded-2xl border border-gray-100 bg-gray-50/70 px-5 py-4 transition-colors hover:bg-gray-50"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ client.name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ client.metrics.visits_count }} visitas · {{ client.metrics.vehicles_count }} vehículos
                                </p>
                            </div>
                            <span class="text-xs font-bold text-gray-500">{{ formatDate(client.metrics.last_visit) }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
