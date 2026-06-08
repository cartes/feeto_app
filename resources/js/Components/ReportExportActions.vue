<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    report: {
        type: String,
        required: true,
    },
});

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
</script>

<template>
    <div class="flex flex-wrap gap-3">
        <a
            :href="route('reports.export.pdf', { ...tenantRouteParams, report: props.report })"
            class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
        >
            Descargar PDF
        </a>
        <a
            :href="route('reports.export.excel', { ...tenantRouteParams, report: props.report })"
            class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-emerald-500"
        >
            Descargar Excel
        </a>
    </div>
</template>
