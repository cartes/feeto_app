<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, defineAsyncComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    tenant: Object,
    stats: Object,
    charts: Object,
    users: Array,
    clients: Array,
    work_orders: Array,
    quotes: Array,
    products: Array,
    appointments: Array,
    recent_logins: Array,
    payments: Array,
});

const activeTab = ref('resumen');

const tabs = [
    { id: 'resumen', label: 'Resumen' },
    { id: 'usuarios', label: 'Usuarios' },
    { id: 'work_orders', label: 'Órdenes de Trabajo' },
    { id: 'clientes', label: 'Clientes' },
    { id: 'cotizaciones', label: 'Cotizaciones' },
    { id: 'inventario', label: 'Inventario' },
    { id: 'citas', label: 'Citas' },
    { id: 'logins', label: 'Visitas/Logins' },
    { id: 'pagos', label: 'Pagos' },
];

// --- Helpers ---
const formatCLP = (v) => {
    if (!v && v !== 0) return '$0';
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(v);
};

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-CL', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// --- Chart: Work orders por día ---
const workOrdersChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, sparkline: { enabled: false } },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    colors: ['#6366f1'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.work_orders_by_day.map((d) => d.date),
        labels: { style: { fontSize: '11px' } },
        tickAmount: 7,
    },
    yaxis: { labels: { style: { fontSize: '11px' } }, min: 0 },
    tooltip: { x: { format: 'dd MMM' } },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
}));

const workOrdersSeries = computed(() => [
    { name: 'Órdenes', data: props.charts.work_orders_by_day.map((d) => d.total) },
]);

// --- Chart: Logins por día ---
const loginsChartOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false } },
    colors: ['#10b981'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.logins_by_day.map((d) => d.date),
        labels: { style: { fontSize: '11px' } },
        tickAmount: 7,
    },
    yaxis: { labels: { style: { fontSize: '11px' } }, min: 0 },
    grid: { strokeDashArray: 4, borderColor: '#f1f5f9' },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
}));

const loginsSeries = computed(() => [
    { name: 'Ingresos', data: props.charts.logins_by_day.map((d) => d.total) },
]);

// --- Chart: Estado OT (donut) ---
const statusLabelsOt = computed(() => props.charts.work_orders_by_status.map((d) => d.status));
const statusSeriesOt = computed(() => props.charts.work_orders_by_status.map((d) => d.total));
const workOrdersDonutOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: statusLabelsOt.value,
    colors: ['#6366f1', '#f59e0b', '#10b981', '#3b82f6', '#ef4444'],
    dataLabels: { style: { fontSize: '11px' } },
    legend: { position: 'bottom', fontSize: '12px' },
    plotOptions: { pie: { donut: { size: '60%' } } },
}));

// --- Chart: Cotizaciones por estado (pie) ---
const quoteStatusLabels = computed(() => props.charts.quotes_by_status.map((d) => d.status));
const quoteStatusSeries = computed(() => props.charts.quotes_by_status.map((d) => d.total));
const quotesDonutOptions = computed(() => ({
    chart: { type: 'donut' },
    labels: quoteStatusLabels.value,
    colors: ['#94a3b8', '#f59e0b', '#10b981', '#ef4444'],
    dataLabels: { style: { fontSize: '11px' } },
    legend: { position: 'bottom', fontSize: '12px' },
    plotOptions: { pie: { donut: { size: '60%' } } },
}));

// OT status label map
const otStatusLabel = {
    recepcion: 'Recepción',
    diagnostico: 'Diagnóstico',
    esperando_repuestos: 'Esp. Repuestos',
    control_calidad: 'Control Calidad',
    listo: 'Listo',
    taller: 'Taller',
    aviso_cliente: 'Aviso Cliente',
};

const quoteStatusLabel = {
    draft: 'Borrador',
    pending_customer: 'Pendiente',
    accepted: 'Aceptada',
    rejected: 'Rechazada',
};

const statusBadge = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    suspended: 'bg-rose-50 text-rose-700 ring-rose-600/20',
};
</script>

<template>
    <Head :title="`Actividad — ${tenant.name}`" />

    <AdminLayout>
        <!-- Encabezado -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <Link :href="route('admin.tenants.index')" class="hover:text-slate-800 transition-colors">Talleres</Link>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-800 font-medium">{{ tenant.name }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ tenant.name }}</h1>
                <span
                    :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset', statusBadge[tenant.status] ?? statusBadge.suspended]"
                >
                    {{ tenant.status === 'active' ? 'Activo' : 'Suspendido' }}
                </span>
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 uppercase tracking-wide">
                    {{ tenant.plan || 'Básico' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-mono">{{ tenant.domain }}</span>
                <span v-if="tenant.rut_taller" class="ml-3 text-slate-400">RUT: {{ tenant.rut_taller }}</span>
                <span v-if="tenant.subscription_ends_at" class="ml-3 text-slate-400">Suscripción: {{ formatDate(tenant.subscription_ends_at) }}</span>
            </p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-slate-200 mb-6 overflow-x-auto">
            <nav class="-mb-px flex gap-0 min-w-max">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
                        activeTab === tab.id
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- ===== TAB: RESUMEN ===== -->
        <div v-if="activeTab === 'resumen'">
            <!-- KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Usuarios</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.users_count }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Clientes</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.clients_count }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">OT Totales</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.work_orders_total }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ stats.work_orders_30d }} en 30 días</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Cotizaciones</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.quotes_total }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ stats.quotes_accepted }} aceptadas</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Productos</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.products_count }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Citas</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.appointments_total }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Logins (30d)</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ stats.logins_30d }}</p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate">{{ stats.last_login_at ? formatDateTime(stats.last_login_at) : 'Sin actividad' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ingresos</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ formatCLP(stats.revenue_total) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Total pagos aprobados</p>
                </div>
            </div>

            <!-- Gráficos fila 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Órdenes de trabajo — últimos 30 días</h3>
                    <VueApexCharts
                        v-if="charts.work_orders_by_day.length"
                        type="area"
                        height="200"
                        :options="workOrdersChartOptions"
                        :series="workOrdersSeries"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">Sin datos en este período</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Ingresos al sistema — últimos 30 días</h3>
                    <VueApexCharts
                        v-if="charts.logins_by_day.length"
                        type="bar"
                        height="200"
                        :options="loginsChartOptions"
                        :series="loginsSeries"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">Sin actividad en este período</div>
                </div>
            </div>

            <!-- Gráficos fila 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Estado de Órdenes de Trabajo</h3>
                    <VueApexCharts
                        v-if="charts.work_orders_by_status.length"
                        type="donut"
                        height="240"
                        :options="workOrdersDonutOptions"
                        :series="statusSeriesOt"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">Sin órdenes de trabajo</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Estado de Cotizaciones</h3>
                    <VueApexCharts
                        v-if="charts.quotes_by_status.length"
                        type="donut"
                        height="240"
                        :options="quotesDonutOptions"
                        :series="quoteStatusSeries"
                    />
                    <div v-else class="flex items-center justify-center h-48 text-sm text-slate-400">Sin cotizaciones</div>
                </div>
            </div>
        </div>

        <!-- ===== TAB: USUARIOS ===== -->
        <div v-if="activeTab === 'usuarios'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nombre</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Email</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Logins</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Último acceso</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ u.name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ u.email }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ u.login_count ?? 0 }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDateTime(u.last_login_at) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(u.created_at) }}</td>
                        </tr>
                        <tr v-if="!users.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin usuarios</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: ÓRDENES DE TRABAJO ===== -->
        <div v-if="activeTab === 'work_orders'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">#</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Patente</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Total</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="wo in work_orders" :key="wo.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm text-slate-500 sm:pl-6">#{{ wo.id }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900">{{ wo.vehicle?.plate || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ otStatusLabel[wo.status] || wo.status }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatCLP(wo.total_amount) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(wo.created_at) }}</td>
                        </tr>
                        <tr v-if="!work_orders.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin órdenes de trabajo</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: CLIENTES ===== -->
        <div v-if="activeTab === 'clientes'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nombre</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">RUT</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Teléfono</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Vehículos</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="c in clients" :key="c.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ c.name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ c.rut || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ c.phone || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ c.vehicles_count }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(c.created_at) }}</td>
                        </tr>
                        <tr v-if="!clients.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin clientes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: COTIZACIONES ===== -->
        <div v-if="activeTab === 'cotizaciones'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">#</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Cliente</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Subtotal</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Creada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="q in quotes" :key="q.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm text-slate-500 sm:pl-6">#{{ q.id }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900">{{ q.client?.name || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ quoteStatusLabel[q.status] || q.status }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatCLP(q.subtotal_amount) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(q.created_at) }}</td>
                        </tr>
                        <tr v-if="!quotes.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin cotizaciones</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: INVENTARIO ===== -->
        <div v-if="activeTab === 'inventario'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nombre</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">SKU</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Tipo</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Precio</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="p in products" :key="p.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                {{ p.name }}
                                <span v-if="p.physical_stock <= p.min_stock" class="ml-1.5 inline-flex items-center rounded bg-rose-50 px-1 py-0.5 text-xs font-medium text-rose-600 ring-1 ring-inset ring-rose-600/20">Bajo</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ p.sku || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ p.type }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatCLP(p.selling_price) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ p.physical_stock }} / mín {{ p.min_stock }}</td>
                        </tr>
                        <tr v-if="!products.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin productos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: CITAS ===== -->
        <div v-if="activeTab === 'citas'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Cliente</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Teléfono</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Patente</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Fecha cita</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="a in appointments" :key="a.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ a.customer_name || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ a.phone || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ a.plate || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDateTime(a.appointment_date) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ a.status }}</td>
                        </tr>
                        <tr v-if="!appointments.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin citas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: LOGINS ===== -->
        <div v-if="activeTab === 'logins'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Usuario</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Email</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">IP</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="log in recent_logins" :key="log.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ log.user?.name || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ log.user?.email || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-mono text-slate-500">{{ log.ip }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDateTime(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!recent_logins.length">
                            <td colspan="4" class="py-8 text-center text-sm text-slate-400">Sin registros de acceso</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB: PAGOS ===== -->
        <div v-if="activeTab === 'pagos'">
            <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">#</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Monto</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Período</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Fecha pago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="pmt in payments" :key="pmt.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm text-slate-500 sm:pl-6">#{{ pmt.id }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900">{{ formatCLP(pmt.amount) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500 capitalize">{{ pmt.billing_period || '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm">
                                <span :class="[
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    pmt.status === 'approved' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' :
                                    pmt.status === 'pending'  ? 'bg-amber-50 text-amber-700 ring-amber-600/20' :
                                    'bg-rose-50 text-rose-700 ring-rose-600/20'
                                ]">
                                    {{ pmt.status === 'approved' ? 'Aprobado' : pmt.status === 'pending' ? 'Pendiente' : 'Rechazado' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(pmt.paid_at ?? pmt.created_at) }}</td>
                        </tr>
                        <tr v-if="!payments.length">
                            <td colspan="5" class="py-8 text-center text-sm text-slate-400">Sin pagos registrados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
