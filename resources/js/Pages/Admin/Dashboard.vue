<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    work_orders_by_tenant: Array,
    ocr_usage: Array,
    visits_by_day: Array,
    expiring_tenants: Array,
});

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

const visitsLinePoints = computed(() => {
    const data = props.visits_by_day;
    if (!data || data.length < 2) return '';
    const maxV = Math.max(...data.map((d) => d.visits));
    const minV = Math.min(...data.map((d) => d.visits));
    const range = maxV - minV || 1;
    const W = 580;
    const H = 130;
    const PAD = 10;
    return data
        .map((d, i) => {
            const x = PAD + (i / (data.length - 1)) * (W - 2 * PAD);
            const y = PAD + (1 - (d.visits - minV) / range) * (H - 2 * PAD);
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
});

const visitsAreaPoints = computed(() => {
    const data = props.visits_by_day;
    if (!data || data.length < 2) return '';
    const maxV = Math.max(...data.map((d) => d.visits));
    const minV = Math.min(...data.map((d) => d.visits));
    const range = maxV - minV || 1;
    const W = 580;
    const H = 130;
    const PAD = 10;
    const pts = data.map((d, i) => {
        const x = PAD + (i / (data.length - 1)) * (W - 2 * PAD);
        const y = PAD + (1 - (d.visits - minV) / range) * (H - 2 * PAD);
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    });
    const firstX = PAD;
    const lastX = (PAD + (580 - 2 * PAD)).toFixed(1);
    const bottom = H;
    return `${firstX},${bottom} ${pts.join(' ')} ${lastX},${bottom}`;
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

        <!-- Row 3: Visits daily chart -->
        <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Visitas Diarias</h2>
            <div v-if="visits_by_day && visits_by_day.length >= 2">
                <svg viewBox="0 0 600 150" class="w-full" preserveAspectRatio="none" style="height: 160px;">
                    <polygon :points="visitsAreaPoints" fill="#f59e0b" fill-opacity="0.12" />
                    <polyline :points="visitsLinePoints" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round" />
                </svg>
                <div class="mt-2 flex justify-between text-xs text-slate-400">
                    <span>{{ visits_by_day[0]?.date }}</span>
                    <span>{{ visits_by_day[Math.floor(visits_by_day.length / 2)]?.date }}</span>
                    <span>{{ visits_by_day[visits_by_day.length - 1]?.date }}</span>
                </div>
            </div>
            <div v-else-if="visits_by_day && visits_by_day.length === 1" class="py-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500">{{ visits_by_day[0].date }}</span>
                    <span class="text-lg font-semibold text-slate-900">{{ visits_by_day[0].visits }} visitas</span>
                </div>
            </div>
            <p v-else class="text-sm text-slate-400 text-center py-6">Sin datos de visitas disponibles</p>
        </div>

        <!-- Row 4: Expiring tenants -->
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Próximas a Vencer</h2>
                <p class="text-xs text-slate-500 mt-0.5">Suscripciones con vencimiento próximo</p>
            </div>
            <div v-if="expiring_tenants && expiring_tenants.length">
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
    </AdminLayout>
</template>
