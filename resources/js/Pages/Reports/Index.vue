<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import ReportsNavigation from '@/Components/ReportsNavigation.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

defineProps({
    summary: Object,
    reports: Array,
});
</script>

<template>
    <Head title="Centro de Reportes" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Centro de mando</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reportes del Taller</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Accede a ventas, supervisión, existencias, clientes y cobranza desde un mismo lugar.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('taller.dashboard', tenantRouteParams)"
                        class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Volver al dashboard
                    </Link>
                    <Link
                        :href="route('work-orders.index', tenantRouteParams)"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-gray-800"
                    >
                        Ir al Kanban
                    </Link>
                </div>
            </div>

            <ReportsNavigation />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cotizaciones</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_quotes }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Actividad comercial registrada.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Stock Crítico</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.critical_products }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Productos agotados que requieren reposición.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Clientes</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_clients }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Base de clientes con historial operativo.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Facturas Vencidas</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.overdue_invoices }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Cobranza que necesita seguimiento.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Link
                    v-for="report in reports"
                    :key="report.route"
                    :href="route(report.route, tenantRouteParams)"
                    class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm transition-transform transition-colors hover:-translate-y-1 hover:border-[#FF7A00]/30"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">{{ report.category }}</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900">{{ report.name }}</h2>
                            <p class="mt-3 max-w-xl text-sm font-medium leading-relaxed text-gray-500">{{ report.description }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-gray-500">
                            Abrir
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </TallerLayout>
</template>
