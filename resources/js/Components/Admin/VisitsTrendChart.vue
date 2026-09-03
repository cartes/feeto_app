<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { formatDateLabel, formatNumber, formatShortDate } from '@/utils/visits';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    series: { type: Array, default: () => [] },
    height: { type: [Number, String], default: 260 },
    color: { type: String, default: '#f97316' },
});

const hasData = computed(() => props.series.some((d) => d.visits > 0 || (d.unique_visitors ?? 0) > 0));

const categories = computed(() => props.series.map((d) => d.date));

const chartSeries = computed(() => [
    { name: 'Visitas', data: props.series.map((d) => d.visits) },
    { name: 'Visitantes únicos', data: props.series.map((d) => (d.unique_visitors === null ? null : d.unique_visitors)) },
]);

const chartOptions = computed(() => {
    const points = props.series.length;
    return {
        chart: {
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { speed: 400 },
            fontFamily: 'inherit',
        },
        colors: [props.color, '#3b82f6'],
        stroke: { curve: 'smooth', width: [2.5, 2], dashArray: [0, 0] },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90, 100] },
        },
        dataLabels: { enabled: false },
        markers: { size: points <= 31 ? 3 : 0, strokeWidth: 2, hover: { size: 6 } },
        xaxis: {
            type: 'category',
            categories: categories.value,
            tickAmount: Math.min(points, points > 60 ? 8 : points > 14 ? 10 : points),
            labels: {
                rotate: 0,
                hideOverlappingLabels: true,
                style: { fontSize: '11px', colors: '#94a3b8' },
                formatter: (value) => formatShortDate(value),
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: {
                style: { fontSize: '11px', colors: '#94a3b8' },
                formatter: (value) => formatNumber(Math.round(value)),
            },
        },
        grid: { strokeDashArray: 4, borderColor: '#f1f5f9', padding: { left: 8, right: 8 } },
        legend: { position: 'top', horizontalAlign: 'left', fontSize: '12px', markers: { size: 5 } },
        tooltip: {
            shared: true,
            intersect: false,
            x: { formatter: (value) => formatDateLabel(value, { weekday: 'long', day: 'numeric', month: 'long' }) },
            y: { formatter: (value) => (value === null || value === undefined ? 'Sin datos' : formatNumber(value)) },
        },
    };
});
</script>

<template>
    <div v-if="hasData">
        <VueApexCharts type="area" :height="height" :options="chartOptions" :series="chartSeries" />
    </div>
    <div v-else class="flex flex-col items-center justify-center gap-1 text-center" :style="{ height: `${height}px` }">
        <p class="text-sm font-medium text-slate-500">Sin visitas en este período</p>
        <p class="text-xs text-slate-400">Prueba con otro rango o ámbito.</p>
    </div>
</template>
