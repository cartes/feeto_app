<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, defineAsyncComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import VisitsTrendChart from '@/Components/Admin/VisitsTrendChart.vue';
import VisitsSummaryStat from '@/Components/Admin/VisitsSummaryStat.vue';
import { PERIOD_OPTIONS, SCOPE_META, formatNumber, formatRange, formatShortDate } from '@/utils/visits';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    stats: Object,
    work_orders_by_tenant: Array,
    ocr_usage: Array,
    visits: { type: Object, default: () => ({ period: '30d', scope: 'site', range: null, summary: {}, by_day: [], by_scope: [] }) },
    expiring_tenants: Array,
    pending_trial_requests: { type: Number, default: 0 },
    recent_trial_requests: { type: Array, default: () => [] },
    most_active_tenants: { type: Array, default: () => [] },
    new_tenants_by_month: { type: Array, default: () => [] },
    tenant_scatter: { type: Array, default: () => [] },
    scatter_medians: { type: Object, default: () => ({ users: 0, logins: 0 }) },
});
const page = usePage();

const formatCLP = (value) => {
    if (!value && value !== 0) return '$0';
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 0,
    }).format(value);
};

const retentionColor = computed(() => {
    const pct = props.stats?.retention_percent ?? 0;
    if (pct > 70) return 'text-emerald-600';
    if (pct >= 40) return 'text-yellow-500';
    return 'text-rose-600';
});

const maxWorkOrders = computed(() => {
    if (!props.work_orders_by_tenant?.length) return 1;
    return Math.max(...props.work_orders_by_tenant.map((i) => i.total), 1);
});

const maxOcrUsage = computed(() => {
    if (!props.ocr_usage?.length) return 1;
    return Math.max(...props.ocr_usage.map((i) => i.total), 1);
});

const marketingWhatsApp = computed(() => page.props.marketing_whatsapp ?? {});
const marketingWhatsAppStatus = computed(() => {
    if (marketingWhatsApp.value?.is_ready) {
        return 'Activo y visible';
    }

    if (marketingWhatsApp.value?.is_enabled) {
        return 'Falta número válido';
    }

    return 'Desactivado';
});

const visitsScopeOptions = ['site', 'tenant', 'app'].map((key) => ({ key, ...SCOPE_META[key] }));
const visitsScopeMeta = computed(() => SCOPE_META[props.visits?.scope] ?? SCOPE_META.site);
const visitsSummary = computed(() => props.visits?.summary ?? {});
const visitsByScope = computed(() => props.visits?.by_scope ?? []);
const visitsRangeLabel = computed(() => formatRange(props.visits?.range));

// Aviso cuando el rango empieza antes de que existan datos de visitantes únicos.
const visitorsPartialSince = computed(() => {
    const since = props.visits?.range?.tracking_since;
    const from = props.visits?.range?.from;
    if (!from) return null;
    if (!since) return 'sin datos aún';
    return since > from ? formatShortDate(since) : null;
});

const updateVisits = (params) => {
    router.get(
        route('admin.dashboard'),
        { period: props.visits?.period, scope: props.visits?.scope, ...params },
        { preserveState: true, preserveScroll: true, only: ['visits'] }
    );
};

// Chart: Tenants más activos (horizontal bar)
const activeTenantsChartOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
    colors: ['#6366f1'],
    dataLabels: { enabled: false },
    xaxis: { categories: props.most_active_tenants.map((t) => t.name), labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { style: { fontSize: '11px', maxWidth: 100 } } },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
    tooltip: { y: { title: { formatter: () => 'Logins' } } },
}));
const activeTenantsChartSeries = computed(() => [
    { name: 'Logins (30d)', data: props.most_active_tenants.map((t) => t.logins) },
]);

// Chart: Nuevos tenants por mes (columnas)
const newTenantsChartOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    colors: ['#10b981'],
    dataLabels: { enabled: false },
    xaxis: { categories: props.new_tenants_by_month.map((d) => d.month), labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { style: { fontSize: '11px' } }, min: 0 },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
}));
const newTenantsChartSeries = computed(() => [
    { name: 'Nuevos talleres', data: props.new_tenants_by_month.map((d) => d.total) },
]);

// ── Scatter: actividad vs. tamaño ───────────────────────────────────────────
const QUADRANTS = {
    champions: { label: 'Champions', color: '#10b981', description: 'Equipo grande y alta actividad' },
    growing: { label: 'Creciendo', color: '#6366f1', description: 'Equipo pequeño y alta actividad' },
    at_risk: { label: 'En riesgo', color: '#f59e0b', description: 'Equipo grande y baja actividad' },
    sleeping: { label: 'Dormidos', color: '#94a3b8', description: 'Equipo pequeño y baja actividad' },
};

const scatterSeries = computed(() => {
    const groups = { champions: [], growing: [], at_risk: [], sleeping: [] };
    for (const t of props.tenant_scatter) {
        groups[t.quadrant]?.push({
            x: t.users,
            y: t.logins,
            z: Math.max(t.work_orders, 1),
            name: t.name,
            plan: t.plan,
            id: t.id,
            work_orders: t.work_orders,
        });
    }
    return Object.entries(QUADRANTS).map(([key, meta]) => ({
        name: meta.label,
        data: groups[key],
    }));
});

const scatterChartOptions = computed(() => {
    const mu = props.scatter_medians.users ?? 1;
    const ml = props.scatter_medians.logins ?? 1;
    return {
        chart: {
            type: 'bubble',
            toolbar: { show: false },
            zoom: { enabled: true },
            events: {
                dataPointSelection: (_e, _ctx, { seriesIndex, dataPointIndex }) => {
                    const series = scatterSeries.value[seriesIndex];
                    const pt = series?.data?.[dataPointIndex];
                    if (pt?.id) window.location.href = route('admin.tenants.activity', pt.id);
                },
            },
        },
        colors: Object.values(QUADRANTS).map((q) => q.color),
        dataLabels: { enabled: false },
        fill: { opacity: 0.75 },
        xaxis: {
            title: { text: 'Usuarios del taller', style: { fontSize: '11px', color: '#64748b' } },
            tickAmount: 5,
            labels: { style: { fontSize: '11px' } },
            min: 0,
        },
        yaxis: {
            title: { text: 'Logins últimos 30 días', style: { fontSize: '11px', color: '#64748b' } },
            labels: { style: { fontSize: '11px' } },
            min: 0,
        },
        annotations: {
            xaxis: [{
                x: mu,
                borderColor: '#cbd5e1',
                strokeDashArray: 5,
                label: { text: 'Mediana usuarios', style: { fontSize: '10px', color: '#94a3b8', background: 'transparent' } },
            }],
            yaxis: [{
                y: ml,
                borderColor: '#cbd5e1',
                strokeDashArray: 5,
                label: { text: 'Mediana logins', style: { fontSize: '10px', color: '#94a3b8', background: 'transparent' } },
            }],
        },
        tooltip: {
            custom: ({ seriesIndex, dataPointIndex, w }) => {
                const pt = w.config.series[seriesIndex]?.data?.[dataPointIndex];
                if (!pt) return '';
                const q = Object.values(QUADRANTS)[seriesIndex];
                return `
                    <div class="px-3 py-2 text-xs bg-white shadow-lg rounded-lg border border-slate-200 min-w-[160px]">
                        <p class="font-semibold text-slate-800 mb-1">${pt.name}</p>
                        <p class="text-slate-500">Plan: <span class="font-medium text-slate-700 uppercase">${pt.plan}</span></p>
                        <p class="text-slate-500">Usuarios: <span class="font-medium text-slate-700">${pt.x}</span></p>
                        <p class="text-slate-500">Logins 30d: <span class="font-medium text-slate-700">${pt.y}</span></p>
                        <p class="text-slate-500">OTs 30d: <span class="font-medium text-slate-700">${pt.work_orders}</span></p>
                        <p class="mt-1 font-semibold" style="color:${q.color}">${q.label}</p>
                        <p class="mt-1 border-t border-slate-100 pt-1 text-slate-400">Haz clic para ver el detalle</p>
                    </div>`;
            },
        },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
        grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
        plotOptions: { bubble: { minBubbleRadius: 6, maxBubbleRadius: 30 } },
    };
});

// Tabla resumen por cuadrante
const scatterByQuadrant = computed(() =>
    Object.entries(QUADRANTS).map(([key, meta]) => ({
        key,
        ...meta,
        tenants: props.tenant_scatter.filter((t) => t.quadrant === key),
    }))
);

const scatterThresholds = computed(() => {
    const users = props.scatter_medians.users ?? 0;
    const logins = props.scatter_medians.logins ?? 0;

    return {
        champions: `≥ ${users} usuarios · ≥ ${logins} logins`,
        growing: `< ${users} usuarios · ≥ ${logins} logins`,
        at_risk: `≥ ${users} usuarios · < ${logins} logins`,
        sleeping: `< ${users} usuarios · < ${logins} logins`,
    };
});
</script>

<template>
    <Head title="Panel de Administración Global" />

    <AdminLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Panel de Administración Global</h1>
            <p class="mt-1 text-sm text-slate-500">Métricas principales de toda la plataforma SaaS.</p>
        </div>

        <!-- Row 1: Stat cards -->
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                <dt class="truncate text-sm font-medium text-slate-500">Total Talleres</dt>
                <dd class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tracking-tight text-slate-900">{{ stats?.total_tenants ?? 0 }}</span>
                    <span class="text-sm text-slate-400">registrados</span>
                </dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                <dt class="truncate text-sm font-medium text-slate-500">% Retención</dt>
                <dd class="mt-1 flex items-baseline gap-2">
                    <span :class="['text-3xl font-semibold tracking-tight', retentionColor]">
                        {{ stats?.retention_percent ?? 0 }}%
                    </span>
                    <span class="text-sm text-slate-400">activos/total</span>
                </dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                <dt class="truncate text-sm font-medium text-slate-500">Suscripciones Vencidas</dt>
                <dd class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tracking-tight text-rose-600">{{ stats?.expired_subscriptions ?? 0 }}</span>
                    <span class="text-sm text-slate-400">
                        <span v-if="stats?.expiring_soon" class="text-yellow-600">+{{ stats.expiring_soon }} próx.</span>
                    </span>
                </dd>
            </div>
            <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                <dt class="truncate text-sm font-medium text-slate-500">Ingresos Últ. 30d</dt>
                <dd class="mt-1">
                    <span class="text-3xl font-semibold tracking-tight text-emerald-600">{{ formatCLP(stats?.approved_revenue_30d) }}</span>
                </dd>
            </div>
        </dl>

        <div class="mt-6 rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-sm ring-1 ring-slate-900/10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Marketing Directo</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight">Canal orgánico por WhatsApp del super-admin</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        El botón flotante global {{ marketingWhatsAppStatus.toLowerCase() }}. Úsalo para capturar consultas de visitantes en la home, planes, blog y trial.
                    </p>
                </div>

                <div class="flex flex-col gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 min-w-[18rem]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-300">Estado</span>
                        <span :class="marketingWhatsApp.is_ready ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-100'" class="rounded-full px-3 py-1 text-xs font-semibold">
                            {{ marketingWhatsAppStatus }}
                        </span>
                    </div>
                    <p class="text-sm font-semibold">{{ marketingWhatsApp.number || 'Sin número configurado' }}</p>
                    <Link :href="route('admin.landing-seo.index')" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">
                        Configurar marketing
                    </Link>
                </div>
            </div>
        </div>

        <!-- Row 2: Work Orders + OCR Usage -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Work Orders -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Work Orders por Taller</h2>
                <div v-if="work_orders_by_tenant && work_orders_by_tenant.length" class="space-y-3">
                    <div v-for="item in work_orders_by_tenant" :key="item.tenant" class="flex items-center gap-3">
                        <div class="w-36 text-xs text-slate-600 truncate shrink-0">{{ item.tenant }}</div>
                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                :style="{ width: `${(item.total / maxWorkOrders) * 100}%` }"
                                class="bg-amber-500 h-2 rounded-full transition-all"
                            ></div>
                        </div>
                        <div class="w-8 text-xs text-slate-900 text-right font-semibold shrink-0">{{ item.total }}</div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 text-center py-6">Sin datos disponibles</p>
            </div>

            <!-- OCR Usage -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Uso OCR por Taller</h2>
                <div v-if="ocr_usage && ocr_usage.length" class="space-y-3">
                    <div v-for="item in ocr_usage" :key="item.tenant" class="flex items-center gap-3">
                        <div class="w-36 text-xs text-slate-600 truncate shrink-0">{{ item.tenant }}</div>
                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                :style="{ width: `${(item.total / maxOcrUsage) * 100}%` }"
                                class="bg-blue-500 h-2 rounded-full transition-all"
                            ></div>
                        </div>
                        <div class="w-8 text-xs text-slate-900 text-right font-semibold shrink-0">{{ item.total }}</div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 text-center py-6">Sin datos disponibles</p>
            </div>
        </div>

        <!-- Row 3: Visitas -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">Visitas · {{ visitsScopeMeta.label }}</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ visitsRangeLabel }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-500">{{ visitsScopeMeta.description }}. Se excluyen bots, prefetch, peticiones internas y tu propia navegación.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <!-- Ámbito -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Ámbito de las visitas">
                        <button
                            v-for="s in visitsScopeOptions"
                            :key="s.key"
                            type="button"
                            role="tab"
                            :aria-selected="visits.scope === s.key"
                            @click="updateVisits({ scope: s.key })"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                                visits.scope === s.key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                            ]"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ background: s.color }" />
                            {{ s.label }}
                        </button>
                    </div>

                    <!-- Período -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Período">
                        <button
                            v-for="p in PERIOD_OPTIONS"
                            :key="p.key"
                            type="button"
                            role="tab"
                            :aria-selected="visits.period === p.key"
                            @click="updateVisits({ period: p.key })"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                                visits.period === p.key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>

                    <Link
                        :href="route('admin.analytics.visits', { period: visits.period, scope: visits.scope })"
                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-600 transition hover:bg-amber-50 hover:text-amber-800"
                    >
                        Análisis completo
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12" /></svg>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-4">
                <div class="min-w-0 lg:col-span-3">
                    <VisitsTrendChart :series="visits.by_day" :color="visitsScopeMeta.color" :height="260" />
                    <p v-if="visitorsPartialSince" class="mt-2 flex items-start gap-1.5 text-[11px] text-slate-400">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span v-if="visitorsPartialSince === 'sin datos aún'">El conteo de visitantes únicos empezará a registrarse con las próximas visitas.</span>
                        <span v-else>Los visitantes únicos se registran desde el {{ visitorsPartialSince }}; los días anteriores solo tienen visitas totales.</span>
                    </p>
                </div>

                <div class="flex flex-col gap-5 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-950/5">
                    <VisitsSummaryStat
                        label="Visitas"
                        :value="visitsSummary.visits ?? 0"
                        :change="visitsSummary.change?.visits ?? null"
                        :previous="visitsSummary.previous?.visits ?? 0"
                        :hint="`${formatNumber(visitsSummary.avg_daily_visits ?? 0)} por día en promedio`"
                        :accent="visitsScopeMeta.color"
                    />
                    <div class="border-t border-slate-200/80 pt-4">
                        <VisitsSummaryStat
                            label="Visitantes únicos"
                            :value="visitsSummary.unique_visitors ?? 0"
                            :change="visitsSummary.change?.unique_visitors ?? null"
                            :previous="visitsSummary.previous?.unique_visitors ?? 0"
                            hint="Personas distintas en el período"
                            accent="#3b82f6"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-t border-slate-200/80 pt-4">
                        <VisitsSummaryStat
                            label="Págs./visitante"
                            :value="visitsSummary.pages_per_visitor ?? '—'"
                        />
                        <VisitsSummaryStat
                            label="Mejor día"
                            :value="visitsSummary.best_day ? formatNumber(visitsSummary.best_day.visits) : '—'"
                            :hint="visitsSummary.best_day ? formatShortDate(visitsSummary.best_day.date) : ''"
                        />
                    </div>
                </div>
            </div>

            <!-- Reparto por ámbito -->
            <div v-if="visitsByScope.length" class="grid grid-cols-1 gap-px border-t border-slate-100 bg-slate-100 sm:grid-cols-3">
                <button
                    v-for="s in visitsByScope"
                    :key="s.scope"
                    type="button"
                    @click="updateVisits({ scope: s.scope })"
                    :class="['flex items-center gap-3 bg-white px-6 py-3 text-left transition hover:bg-slate-50', visits.scope === s.scope ? 'bg-slate-50/80' : '']"
                >
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: SCOPE_META[s.scope]?.color }" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-semibold text-slate-700">{{ SCOPE_META[s.scope]?.label }}</span>
                        <span class="block text-[11px] text-slate-400">{{ formatNumber(s.unique_visitors) }} visitantes únicos</span>
                    </span>
                    <span class="text-right">
                        <span class="block text-sm font-bold tabular-nums text-slate-900">{{ formatNumber(s.visits) }}</span>
                        <span class="block text-[11px] text-slate-400">{{ s.share }}%</span>
                    </span>
                </button>
            </div>
        </div>

        <!-- Row 4: Expiring tenants -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Próximas a Vencer</h2>
                <p class="text-xs text-slate-500 mt-0.5">Suscripciones con vencimiento próximo</p>
            </div>
            <div v-if="expiring_tenants && expiring_tenants.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Taller</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Vence</th>
                            <th class="py-3 pl-3 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="tenant in expiring_tenants" :key="tenant.id">
                            <td class="whitespace-nowrap py-3 pl-6 pr-3 text-sm font-medium text-slate-900">{{ tenant.name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-rose-600 font-medium">
                                {{ new Date(tenant.subscription_ends_at).toLocaleDateString('es-CL') }}
                            </td>
                            <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm">
                                <Link :href="route('admin.tenants.edit', tenant.id)" class="text-amber-600 hover:text-amber-900 font-semibold">Gestionar</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-6 py-8 text-sm text-slate-400 text-center">No hay talleres próximos a vencer.</p>
        </div>

        <!-- Solicitudes de prueba pendientes -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        Solicitudes de Prueba Gratuita
                        <span v-if="pending_trial_requests > 0" class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ pending_trial_requests }} pendiente{{ pending_trial_requests !== 1 ? 's' : '' }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Últimas solicitudes sin revisar</p>
                </div>
                <Link :href="route('admin.trial-requests.index')" class="text-xs text-amber-600 hover:text-amber-800 font-semibold transition-colors">
                    Ver todas →
                </Link>
            </div>
            <div v-if="recent_trial_requests && recent_trial_requests.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Solicitante</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Negocio</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Ciudad</th>
                            <th class="py-3 pl-3 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="req in recent_trial_requests" :key="req.id">
                            <td class="py-3 pl-6 pr-3">
                                <p class="text-sm font-medium text-slate-900">{{ req.name }}</p>
                                <p class="text-xs text-slate-500">{{ req.email }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <p class="text-sm text-slate-700">{{ req.business_name }}</p>
                                <p class="text-xs text-slate-400">{{ req.business_type }}</p>
                            </td>
                            <td class="px-3 py-3 text-sm text-slate-500">{{ req.city || '—' }}</td>
                            <td class="py-3 pl-3 pr-6 text-right">
                                <Link :href="route('admin.trial-requests.index')" class="text-xs text-amber-600 hover:text-amber-900 font-semibold">
                                    Revisar
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-6 py-8 text-sm text-slate-400 text-center">No hay solicitudes pendientes.</p>
        </div>

        <!-- ===== SECCIÓN: ACTIVIDAD DE TENANTS ===== -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-800">Actividad de Talleres</h2>
                <Link :href="route('admin.tenants.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    Ver todos →
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Más activos -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700">Talleres más activos — últimos 30 días</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Por número de ingresos al sistema</p>
                    </div>
                    <div class="p-4">
                        <VueApexCharts
                            v-if="most_active_tenants.length"
                            type="bar"
                            height="240"
                            :options="activeTenantsChartOptions"
                            :series="activeTenantsChartSeries"
                        />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">
                            Sin actividad registrada
                        </div>
                    </div>
                </div>

                <!-- Nuevos tenants por mes -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700">Nuevos talleres registrados</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Últimos 6 meses</p>
                    </div>
                    <div class="p-4">
                        <VueApexCharts
                            v-if="new_tenants_by_month.length"
                            type="bar"
                            height="240"
                            :options="newTenantsChartOptions"
                            :series="newTenantsChartSeries"
                        />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">
                            Sin datos disponibles
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista compacta de tenants más activos -->
            <div v-if="most_active_tenants.length" class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Top talleres activos</h3>
                    <span class="text-xs text-slate-400">Logins últimos 30 días</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    <li v-for="(t, i) in most_active_tenants" :key="t.name" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/60 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="w-6 text-xs font-bold text-slate-400 text-right">{{ i + 1 }}</span>
                            <span class="text-sm font-medium text-slate-800">{{ t.name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-indigo-500"
                                    :style="{ width: Math.round(t.logins / most_active_tenants[0].logins * 100) + '%' }"
                                />
                            </div>
                            <span class="text-xs font-semibold text-slate-600 w-8 text-right">{{ t.logins }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ===== SCATTER: Actividad vs. Tamaño ===== -->
        <div v-if="tenant_scatter.length" class="mt-8">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-800">Actividad y tamaño de cada taller</h2>
                        <div class="group relative">
                            <button
                                type="button"
                                aria-label="Cómo se clasifican los talleres"
                                class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 text-xs font-bold text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                            >
                                ?
                            </button>
                            <div
                                role="tooltip"
                                class="pointer-events-none absolute left-0 top-7 z-20 w-64 rounded-lg bg-slate-900 px-3 py-2 text-xs leading-5 text-white opacity-0 shadow-xl transition group-hover:opacity-100 group-focus-within:opacity-100"
                            >
                                Las líneas punteadas marcan la mediana de todos los talleres. Las cuatro categorías se calculan según si cada taller queda sobre o bajo esos cortes.
                            </div>
                        </div>
                    </div>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Compara el tamaño del equipo con su uso reciente. Pasa el cursor por una burbuja para ver sus cifras.
                    </p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700">Haz clic en una burbuja para abrir el detalle</span>
            </div>

            <!-- Clasificación de cuadrantes -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div v-for="q in scatterByQuadrant" :key="q.key" class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: q.color }" />
                        <p class="text-sm font-semibold text-slate-800">{{ q.label }}</p>
                        <span class="ml-auto text-xs font-medium text-slate-500">{{ q.tenants.length }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ q.description }}</p>
                    <p class="mt-1 text-[11px] font-medium text-slate-400">{{ scatterThresholds[q.key] }}</p>
                </div>
            </div>

            <!-- Gráfico bubble -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="grid gap-px border-b border-slate-100 bg-slate-100 sm:grid-cols-3">
                    <div class="bg-white px-5 py-3">
                        <p class="text-xs font-semibold text-slate-700">1. Posición horizontal</p>
                        <p class="mt-0.5 text-xs text-slate-500">Más a la derecha = más usuarios en el equipo.</p>
                    </div>
                    <div class="bg-white px-5 py-3">
                        <p class="text-xs font-semibold text-slate-700">2. Posición vertical</p>
                        <p class="mt-0.5 text-xs text-slate-500">Más arriba = más logins durante los últimos 30 días.</p>
                    </div>
                    <div class="bg-white px-5 py-3">
                        <p class="text-xs font-semibold text-slate-700">3. Tamaño de burbuja</p>
                        <p class="mt-0.5 text-xs text-slate-500">Más grande = más órdenes de trabajo en los últimos 30 días.</p>
                    </div>
                </div>
                <div class="p-2">
                    <VueApexCharts
                        type="bubble"
                        height="380"
                        :options="scatterChartOptions"
                        :series="scatterSeries"
                    />
                </div>
            </div>

            <!-- Tabla de "En riesgo" y "Creciendo" (acción inmediata) -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- En riesgo -->
                <div class="rounded-xl border border-amber-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-amber-100 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 flex-shrink-0" />
                        <h3 class="text-sm font-semibold text-slate-800">En riesgo de churn</h3>
                        <span class="ml-auto text-xs text-slate-400">Equipo grande · baja actividad</span>
                    </div>
                    <ul v-if="scatterByQuadrant.find(q=>q.key==='at_risk')?.tenants.length" class="divide-y divide-slate-100">
                        <li
                            v-for="t in scatterByQuadrant.find(q=>q.key==='at_risk')?.tenants"
                            :key="t.id"
                            class="flex items-center justify-between px-5 py-2.5 hover:bg-amber-50/40 transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ t.name }}</p>
                                <p class="text-xs text-slate-400 uppercase tracking-wide">{{ t.plan }}</p>
                            </div>
                            <div class="flex items-center gap-4 ml-3 flex-shrink-0 text-xs text-slate-500">
                                <span>{{ t.users }} usuarios</span>
                                <span class="text-amber-600 font-semibold">{{ t.logins }} logins</span>
                                <Link :href="route('admin.tenants.activity', t.id)" class="text-amber-600 hover:text-amber-900 font-semibold">Ver →</Link>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-6 text-sm text-slate-400 text-center">Sin talleres en esta categoría</p>
                </div>

                <!-- Creciendo → upsell -->
                <div class="rounded-xl border border-indigo-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-indigo-100 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 flex-shrink-0" />
                        <h3 class="text-sm font-semibold text-slate-800">Candidatos a upsell</h3>
                        <span class="ml-auto text-xs text-slate-400">Muy activos · equipo pequeño</span>
                    </div>
                    <ul v-if="scatterByQuadrant.find(q=>q.key==='growing')?.tenants.length" class="divide-y divide-slate-100">
                        <li
                            v-for="t in scatterByQuadrant.find(q=>q.key==='growing')?.tenants"
                            :key="t.id"
                            class="flex items-center justify-between px-5 py-2.5 hover:bg-indigo-50/40 transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ t.name }}</p>
                                <p class="text-xs text-slate-400 uppercase tracking-wide">{{ t.plan }}</p>
                            </div>
                            <div class="flex items-center gap-4 ml-3 flex-shrink-0 text-xs text-slate-500">
                                <span>{{ t.users }} usuarios</span>
                                <span class="text-indigo-600 font-semibold">{{ t.logins }} logins</span>
                                <Link :href="route('admin.tenants.activity', t.id)" class="text-indigo-600 hover:text-indigo-900 font-semibold">Ver →</Link>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-6 text-sm text-slate-400 text-center">Sin talleres en esta categoría</p>
                </div>
            </div>
        </div>
        <div v-else class="mt-8 rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center text-sm text-slate-400">
            No hay tenants activos para mostrar en el comparativo.
        </div>
    </AdminLayout>
</template>
