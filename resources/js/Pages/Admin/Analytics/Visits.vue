<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VisitsTrendChart from '@/Components/Admin/VisitsTrendChart.vue';
import VisitsSummaryStat from '@/Components/Admin/VisitsSummaryStat.vue';
import {
    DEVICE_LABELS,
    PERIOD_OPTIONS,
    SCOPE_META,
    WEEKDAY_LABELS,
    formatNumber,
    formatRange,
    formatShortDate,
} from '@/utils/visits';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    report: { type: Object, required: true },
});

const scopeOptions = ['all', 'site', 'tenant', 'app'].map((key) => ({ key, ...SCOPE_META[key] }));
const scopeMeta = computed(() => SCOPE_META[props.report.scope] ?? SCOPE_META.all);
const summary = computed(() => props.report.summary ?? {});
const rangeLabel = computed(() => formatRange(props.report.range));

const visitorsPartialSince = computed(() => {
    const since = props.report.range?.tracking_since;
    const from = props.report.range?.from;
    if (!from) return null;
    if (!since) return 'sin datos aún';
    return since > from ? formatShortDate(since) : null;
});

const update = (params) => {
    router.get(
        route('admin.analytics.visits'),
        { period: props.report.period, scope: props.report.scope, ...params },
        { preserveState: true, preserveScroll: true, only: ['report'] }
    );
};

const maxTopPage = computed(() => Math.max(...(props.report.top_pages ?? []).map((p) => p.visits), 1));
const maxEntryPage = computed(() => Math.max(...(props.report.entry_pages ?? []).map((p) => p.visitors), 1));
const maxTenant = computed(() => Math.max(...(props.report.by_tenant ?? []).map((t) => t.visits), 1));
const totalDevices = computed(() => (props.report.devices ?? []).reduce((acc, d) => acc + d.visitors, 0));

// Gráfico: visitas por día de la semana
const weekdayOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
    plotOptions: { bar: { borderRadius: 5, columnWidth: '52%' } },
    colors: [scopeMeta.value.color],
    dataLabels: { enabled: false },
    xaxis: {
        categories: WEEKDAY_LABELS,
        labels: { style: { fontSize: '11px', colors: '#94a3b8' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
    tooltip: { y: { formatter: (v) => `${formatNumber(v)} visitas/día` } },
}));
const weekdaySeries = computed(() => [
    { name: 'Promedio diario', data: (props.report.by_weekday ?? []).map((d) => d.avg_visits) },
]);
const hasWeekdayData = computed(() => (props.report.by_weekday ?? []).some((d) => d.total_visits > 0));

// Gráfico: dispositivos (donut)
const deviceOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit' },
    labels: (props.report.devices ?? []).map((d) => DEVICE_LABELS[d.device] ?? d.device),
    colors: ['#0f172a', scopeMeta.value.color, '#94a3b8'],
    dataLabels: { enabled: false },
    legend: { position: 'bottom', fontSize: '12px', markers: { size: 5 } },
    stroke: { width: 2, colors: ['#fff'] },
    plotOptions: {
        pie: {
            donut: {
                size: '68%',
                labels: {
                    show: true,
                    total: { show: true, label: 'Visitantes', fontSize: '11px', color: '#94a3b8', formatter: () => formatNumber(totalDevices.value) },
                    value: { fontSize: '20px', fontWeight: 800, color: '#0f172a', formatter: (v) => formatNumber(Number(v)) },
                },
            },
        },
    },
    tooltip: { y: { formatter: (v) => `${formatNumber(v)} visitantes` } },
}));
const deviceSeries = computed(() => (props.report.devices ?? []).map((d) => d.visitors));
</script>

<template>
    <Head title="Visitas al sitio" />

    <AdminLayout>
        <!-- Encabezado -->
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <nav class="mb-1 flex items-center gap-1.5 text-xs text-slate-400">
                    <Link :href="route('admin.dashboard')" class="hover:text-slate-600">Panel</Link>
                    <span>/</span>
                    <span class="text-slate-600">Visitas</span>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Visitas al sitio</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ scopeMeta.description }}. Rango: <span class="font-medium text-slate-700">{{ rangeLabel }}</span>.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-900/5" role="tablist" aria-label="Ámbito">
                    <button
                        v-for="s in scopeOptions"
                        :key="s.key"
                        type="button"
                        role="tab"
                        :aria-selected="report.scope === s.key"
                        @click="update({ scope: s.key })"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                            report.scope === s.key ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :style="{ background: report.scope === s.key ? '#fff' : s.color }" />
                        {{ s.label }}
                    </button>
                </div>
                <div class="inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-900/5" role="tablist" aria-label="Período">
                    <button
                        v-for="p in PERIOD_OPTIONS"
                        :key="p.key"
                        type="button"
                        role="tab"
                        :aria-selected="report.period === p.key"
                        @click="update({ period: p.key })"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                            report.period === p.key ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <VisitsSummaryStat
                    label="Visitas"
                    :value="summary.visits ?? 0"
                    :change="summary.change?.visits ?? null"
                    :previous="summary.previous?.visits ?? 0"
                    :hint="`${formatNumber(summary.avg_daily_visits ?? 0)} por día · ${summary.days_with_traffic ?? 0} de ${report.range?.days ?? 0} días con tráfico`"
                    :accent="scopeMeta.color"
                />
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <VisitsSummaryStat
                    label="Visitantes únicos"
                    :value="summary.unique_visitors ?? 0"
                    :change="summary.change?.unique_visitors ?? null"
                    :previous="summary.previous?.unique_visitors ?? 0"
                    :hint="`${formatNumber(summary.avg_daily_visitors ?? 0)} visitantes por día`"
                    accent="#3b82f6"
                />
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <VisitsSummaryStat
                    label="Páginas por visitante"
                    :value="summary.pages_per_visitor ?? '—'"
                    hint="Promedio de páginas vistas por persona y día"
                />
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
                <VisitsSummaryStat
                    label="Mejor día"
                    :value="summary.best_day ? formatNumber(summary.best_day.visits) : '—'"
                    :hint="summary.best_day ? `${formatShortDate(summary.best_day.date)} · ${formatNumber(summary.best_day.unique_visitors)} únicos` : 'Sin visitas en el período'"
                />
            </div>
        </div>

        <!-- Tendencia -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Tendencia diaria</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Visitas totales y personas distintas por día.</p>
                </div>
            </div>
            <div class="p-6">
                <VisitsTrendChart :series="report.by_day" :color="scopeMeta.color" :height="300" />
                <p v-if="visitorsPartialSince" class="mt-2 text-[11px] text-slate-400">
                    <span v-if="visitorsPartialSince === 'sin datos aún'">El conteo de visitantes únicos empezará a registrarse con las próximas visitas.</span>
                    <span v-else>Los visitantes únicos se registran desde el {{ visitorsPartialSince }}; los días anteriores solo tienen visitas totales.</span>
                </p>
            </div>
        </div>

        <!-- Reparto por ámbito -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <button
                v-for="s in report.by_scope"
                :key="s.scope"
                type="button"
                @click="update({ scope: s.scope })"
                :class="[
                    'rounded-xl bg-white p-5 text-left shadow-sm ring-1 transition hover:ring-slate-300',
                    report.scope === s.scope ? 'ring-2 ring-slate-900' : 'ring-slate-900/5'
                ]"
            >
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ background: SCOPE_META[s.scope]?.color }" />
                    <span class="text-sm font-semibold text-slate-800">{{ SCOPE_META[s.scope]?.label }}</span>
                    <span class="ml-auto text-xs font-medium text-slate-400">{{ s.share }}% del total</span>
                </div>
                <div class="mt-3 flex items-baseline gap-4">
                    <div>
                        <span class="text-2xl font-black tabular-nums text-slate-900">{{ formatNumber(s.visits) }}</span>
                        <span class="ml-1 text-xs text-slate-500">visitas</span>
                    </div>
                    <div>
                        <span class="text-lg font-bold tabular-nums text-slate-700">{{ formatNumber(s.unique_visitors) }}</span>
                        <span class="ml-1 text-xs text-slate-500">únicos</span>
                    </div>
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full" :style="{ width: `${s.share}%`, background: SCOPE_META[s.scope]?.color }" />
                </div>
            </button>
        </div>

        <!-- Páginas -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 lg:col-span-3">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Páginas más vistas</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Visitas por página y cuántas personas la vieron cada día.</p>
                    </div>
                    <span class="text-xs text-slate-400">Top {{ report.top_pages?.length ?? 0 }}</span>
                </div>
                <div v-if="report.top_pages?.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="py-2.5 pl-6 pr-3 text-left font-semibold">Página</th>
                                <th class="px-3 py-2.5 text-right font-semibold">Visitas</th>
                                <th class="px-3 py-2.5 text-right font-semibold">Únicos</th>
                                <th class="py-2.5 pl-3 pr-6 text-right font-semibold">Peso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="page in report.top_pages" :key="page.scope + page.path" class="hover:bg-slate-50/60">
                                <td class="max-w-0 py-2.5 pl-6 pr-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :style="{ background: SCOPE_META[page.scope]?.color }" :title="SCOPE_META[page.scope]?.label" />
                                        <span class="truncate font-mono text-xs text-slate-700" :title="page.path">{{ page.path }}</span>
                                    </div>
                                    <div class="mt-1 ml-3.5 h-1 w-full max-w-xs overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-slate-300" :style="{ width: `${(page.visits / maxTopPage) * 100}%` }" />
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold tabular-nums text-slate-900">{{ formatNumber(page.visits) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums text-slate-500">{{ formatNumber(page.unique_visits) }}</td>
                                <td class="whitespace-nowrap py-2.5 pl-3 pr-6 text-right tabular-nums text-slate-400">{{ page.share }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="px-6 py-10 text-center text-sm text-slate-400">Sin páginas visitadas en el período.</p>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 lg:col-span-2">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Páginas de entrada</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Primera página que ve cada visitante.</p>
                </div>
                <ul v-if="report.entry_pages?.length" class="divide-y divide-slate-100">
                    <li v-for="page in report.entry_pages" :key="page.path" class="px-6 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate font-mono text-xs text-slate-700" :title="page.path">{{ page.path }}</span>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-slate-900">{{ formatNumber(page.visitors) }}</span>
                        </div>
                        <div class="mt-1 h-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-400" :style="{ width: `${(page.visitors / maxEntryPage) * 100}%` }" />
                        </div>
                    </li>
                </ul>
                <p v-else class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay datos de entrada.</p>
            </div>
        </div>

        <!-- Origen, dispositivos, día de la semana -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Fuentes de tráfico</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Sitio de origen o utm_source de cada visitante.</p>
                </div>
                <ul v-if="report.referrers?.length" class="divide-y divide-slate-100">
                    <li v-for="ref in report.referrers" :key="ref.source" class="flex items-center gap-3 px-6 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-700" :title="ref.source">{{ ref.source }}</p>
                            <div class="mt-1 h-1 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full" :style="{ width: `${ref.share}%`, background: scopeMeta.color }" />
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold tabular-nums text-slate-900">{{ formatNumber(ref.visitors) }}</p>
                            <p class="text-[11px] text-slate-400">{{ ref.share }}%</p>
                        </div>
                    </li>
                </ul>
                <p v-else class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay datos de origen.</p>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Dispositivos</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Visitantes por tipo de dispositivo.</p>
                </div>
                <div class="p-4">
                    <VueApexCharts v-if="totalDevices > 0" type="donut" height="250" :options="deviceOptions" :series="deviceSeries" />
                    <p v-else class="py-10 text-center text-sm text-slate-400">Aún no hay datos de dispositivos.</p>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Visitas por día de la semana</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Promedio diario dentro del período.</p>
                </div>
                <div class="p-4">
                    <VueApexCharts v-if="hasWeekdayData" type="bar" height="250" :options="weekdayOptions" :series="weekdaySeries" />
                    <p v-else class="py-10 text-center text-sm text-slate-400">Sin visitas en el período.</p>
                </div>
            </div>
        </div>

        <!-- Talleres -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Tráfico público por taller</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Landing, checkout y cotizaciones públicas de cada taller, sin importar el ámbito seleccionado.</p>
                </div>
                <Link :href="route('admin.tenants.index')" class="text-xs font-semibold text-amber-600 hover:text-amber-800">Ver talleres →</Link>
            </div>
            <ul v-if="report.by_tenant?.length" class="divide-y divide-slate-100">
                <li v-for="(t, i) in report.by_tenant" :key="t.id" class="flex items-center gap-4 px-6 py-3 hover:bg-slate-50/60">
                    <span class="w-5 text-right text-xs font-bold text-slate-400">{{ i + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.tenants.activity', t.id)" class="truncate text-sm font-medium text-slate-800 hover:text-indigo-600">{{ t.name }}</Link>
                            <a v-if="t.slug" :href="`/taller/${t.slug}`" target="_blank" rel="noopener" class="shrink-0 font-mono text-[11px] text-slate-400 hover:text-slate-600">/taller/{{ t.slug }}</a>
                        </div>
                        <div class="mt-1 h-1 w-full max-w-md overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-500" :style="{ width: `${(t.visits / maxTenant) * 100}%` }" />
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-5 text-right">
                        <div>
                            <p class="text-sm font-semibold tabular-nums text-slate-900">{{ formatNumber(t.visits) }}</p>
                            <p class="text-[11px] text-slate-400">visitas</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold tabular-nums text-slate-700">{{ formatNumber(t.unique_visitors) }}</p>
                            <p class="text-[11px] text-slate-400">únicos</p>
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="px-6 py-10 text-center text-sm text-slate-400">Ningún taller recibió visitas públicas en el período.</p>
        </div>

        <p class="mt-6 text-[11px] text-slate-400">
            Los visitantes únicos se identifican con un hash irreversible de IP y navegador (o del usuario autenticado). No se guardan direcciones IP.
            Se descartan bots, prefetch, recargas parciales, peticiones JSON internas y la navegación del super-admin.
        </p>
    </AdminLayout>
</template>
