<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

const reports = [
    { label: 'Inicio', route: 'reports.index' },
    { label: 'Ventas', route: 'reports.sales' },
    { label: 'Supervisión', route: 'reports.supervisors' },
    { label: 'Existencias', route: 'reports.inventory' },
    { label: 'Clientes', route: 'reports.customers' },
    { label: 'Cobranza', route: 'reports.collections' },
    { label: 'Adquisición', route: 'reports.acquisition' },
];
</script>

<template>
    <div class="overflow-x-auto pb-1">
        <div class="flex w-max gap-3">
            <Link
                v-for="report in reports"
                :key="report.route"
                :href="route(report.route, tenantRouteParams)"
                class="inline-flex items-center rounded-full border px-4 py-2 text-xs font-black uppercase tracking-widest transition-colors"
                :class="route().current(report.route) ? 'border-[#FF7A00] bg-[#FF7A00] text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
            >
                {{ report.label }}
            </Link>
        </div>
    </div>
</template>
