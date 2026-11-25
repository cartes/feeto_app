<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    email_trackings: {
        type: Array,
        default: () => [],
    },
    quick_templates: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('resumen');
const subjectInputRef = ref(null);
const bodyTextareaRef = ref(null);

const tabs = computed(() => [
    { id: 'resumen', label: 'Resumen' },
    { id: 'comunicaciones', label: 'Comunicaciones', count: props.email_trackings.length },
    { id: 'usuarios', label: 'Usuarios', count: props.users.length },
    { id: 'work_orders', label: 'Órdenes de Trabajo' },
    { id: 'clientes', label: 'Clientes' },
    { id: 'cotizaciones', label: 'Cotizaciones' },
    { id: 'inventario', label: 'Inventario' },
    { id: 'citas', label: 'Citas' },
    { id: 'logins', label: 'Visitas/Logins' },
    { id: 'pagos', label: 'Pagos' },
]);

const emailForm = useForm({
    recipient_mode: 'admin',
    user_id: props.users[0]?.id ?? null,
    template_key: 'custom',
    subject: '',
    body: '',
    action_label: 'Ir al panel de mi taller',
    action_url: props.tenant.dashboard_url || '',
});

const availableVariables = [
    { token: '{nombre_taller}', label: 'Nombre taller' },
    { token: '{nombre_usuario}', label: 'Nombre destinatario' },
    { token: '{email_usuario}', label: 'Email destinatario' },
    { token: '{link_panel}', label: 'Link panel privado' },
    { token: '{link_publico}', label: 'Link agendamiento público' },
];

const emailTypeMeta = {
    admin_direct: { label: 'Mensaje directo', badge: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20' },
    marketing_tutorials: { label: 'Guía / Tutoriales', badge: 'bg-orange-50 text-orange-700 ring-orange-600/20' },
    marketing_custom_tool: { label: 'Herramienta a medida', badge: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
    renewal_offer: { label: 'Oferta renovación', badge: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
    renewal_reminder: { label: 'Recordatorio pago', badge: 'bg-blue-50 text-blue-700 ring-blue-600/20' },
};

const getEmailTypeMeta = (type) => {
    return emailTypeMeta[type] || { label: type || 'Correo', badge: 'bg-slate-100 text-slate-700 ring-slate-500/20' };
};

const applyTemplate = (tpl) => {
    emailForm.template_key = tpl.key;
    emailForm.subject = tpl.subject;
    emailForm.body = tpl.body;
    emailForm.action_label = tpl.action_label || 'Ir al panel de mi taller';
    emailForm.action_url = tpl.action_url || props.tenant.dashboard_url || '';
};

const insertVariable = (token) => {
    const el = bodyTextareaRef.value;
    if (!el) {
        emailForm.body = `${emailForm.body}${token}`;
        return;
    }

    const start = el.selectionStart ?? emailForm.body.length;
    const end = el.selectionEnd ?? emailForm.body.length;
    const before = emailForm.body.slice(0, start);
    const after = emailForm.body.slice(end);
    emailForm.body = `${before}${token}${after}`;

    requestAnimationFrame(() => {
        el.focus();
        const cursor = start + token.length;
        el.setSelectionRange(cursor, cursor);
    });
};

const openComposeForUser = (user = null) => {
    activeTab.value = 'comunicaciones';
    if (user) {
        emailForm.recipient_mode = 'user';
        emailForm.user_id = user.id;
    } else {
        emailForm.recipient_mode = 'admin';
    }

    requestAnimationFrame(() => {
        subjectInputRef.value?.focus();
    });
};

const submitDirectEmail = () => {
    emailForm.post(route('admin.tenants.send-email', props.tenant.id), {
        preserveScroll: true,
        onSuccess: () => {
            emailForm.reset('subject', 'body');
            emailForm.template_key = 'custom';
        },
    });
};

const selectedTrackDetail = ref(null);

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
        <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
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
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-500">
                    <div class="flex items-center gap-1.5">
                        <a
                            :href="tenant.public_url || ('/taller/' + tenant.slug)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 font-mono text-xs sm:text-sm font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded-md border border-amber-200 transition-colors"
                            title="Ver página pública del taller"
                        >
                            <span>/taller/{{ tenant.slug }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <a
                            :href="tenant.dashboard_url || ('/taller/' + tenant.slug + '/dashboard')"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800 px-2 py-0.5 rounded-md hover:bg-slate-100 transition-colors"
                            title="Abrir panel del taller en nueva pestaña"
                        >
                            <span>Panel</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                    <span v-if="tenant.rut_taller" class="text-slate-400">RUT: {{ tenant.rut_taller }}</span>
                    <span v-if="tenant.subscription_ends_at" class="text-slate-400">Suscripción: {{ formatDate(tenant.subscription_ends_at) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="openComposeForUser()"
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Enviar correo al taller</span>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-slate-200 mb-6 overflow-x-auto no-scrollbar scrollbar-none">
            <nav class="-mb-px flex gap-1 min-w-max">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 sm:px-3.5 lg:px-4 py-2.5 text-sm border-b-2 whitespace-nowrap transition-colors',
                        activeTab === tab.id
                            ? 'border-orange-500 text-orange-600 font-semibold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.count !== undefined"
                        :class="[
                            'rounded-full px-1.5 py-0.5 text-[11px] font-semibold',
                            activeTab === tab.id ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600',
                        ]"
                    >
                        {{ tab.count }}
                    </span>
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

        <!-- ===== TAB: COMUNICACIONES ===== -->
        <div v-if="activeTab === 'comunicaciones'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Redactor de Correo -->
            <div class="lg:col-span-5">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Enviar correo al taller</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Incluye píxel de lectura y seguimiento de clics</p>
                        </div>
                    </div>

                    <!-- Plantillas rápidas -->
                    <div v-if="quick_templates.length" class="mb-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                            Cargar plantilla rápida
                        </label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="tpl in quick_templates"
                                :key="tpl.key"
                                type="button"
                                @click="applyTemplate(tpl)"
                                :class="[
                                    'rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors text-left',
                                    emailForm.template_key === tpl.key
                                        ? 'border-orange-500 bg-orange-50 text-orange-700'
                                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100',
                                ]"
                            >
                                {{ tpl.label }}
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submitDirectEmail" class="space-y-4">
                        <!-- Destinatario -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">
                                Destinatario
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="emailForm.recipient_mode = 'admin'"
                                    :class="[
                                        'rounded-lg border px-2.5 py-2 text-xs font-medium text-center transition-colors',
                                        emailForm.recipient_mode === 'admin'
                                            ? 'border-orange-500 bg-orange-50 text-orange-700 font-semibold'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    Admin principal
                                </button>
                                <button
                                    type="button"
                                    @click="emailForm.recipient_mode = 'user'"
                                    :class="[
                                        'rounded-lg border px-2.5 py-2 text-xs font-medium text-center transition-colors',
                                        emailForm.recipient_mode === 'user'
                                            ? 'border-orange-500 bg-orange-50 text-orange-700 font-semibold'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    Usuario específico
                                </button>
                                <button
                                    type="button"
                                    @click="emailForm.recipient_mode = 'all'"
                                    :class="[
                                        'rounded-lg border px-2.5 py-2 text-xs font-medium text-center transition-colors',
                                        emailForm.recipient_mode === 'all'
                                            ? 'border-orange-500 bg-orange-50 text-orange-700 font-semibold'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    Todos ({{ users.length }})
                                </button>
                            </div>
                            <p v-if="emailForm.errors.recipient_mode" class="mt-1 text-xs text-rose-600">
                                {{ emailForm.errors.recipient_mode }}
                            </p>
                        </div>

                        <!-- Selector de usuario si es modo user -->
                        <div v-if="emailForm.recipient_mode === 'user'">
                            <label for="recipient-user-select" class="block text-xs font-medium text-slate-700 mb-1">
                                Seleccionar usuario
                            </label>
                            <select
                                id="recipient-user-select"
                                v-model="emailForm.user_id"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            >
                                <option v-for="u in users" :key="u.id" :value="u.id">
                                    {{ u.name }} ({{ u.email }})
                                </option>
                            </select>
                            <p v-if="emailForm.errors.user_id" class="mt-1 text-xs text-rose-600">
                                {{ emailForm.errors.user_id }}
                            </p>
                        </div>

                        <!-- Asunto -->
                        <div>
                            <label for="email-subject" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1">
                                Asunto
                            </label>
                            <input
                                id="email-subject"
                                ref="subjectInputRef"
                                v-model="emailForm.subject"
                                type="text"
                                required
                                placeholder="Ej: [Taller Flow] Seguimiento de tu cuenta en {nombre_taller}"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            />
                            <p v-if="emailForm.errors.subject" class="mt-1 text-xs text-rose-600">
                                {{ emailForm.errors.subject }}
                            </p>
                        </div>

                        <!-- Variables dinámicas -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="email-body" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                                    Mensaje
                                </label>
                                <span class="text-[11px] text-slate-400">Clic para insertar variable</span>
                            </div>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <button
                                    v-for="v in availableVariables"
                                    :key="v.token"
                                    type="button"
                                    @click="insertVariable(v.token)"
                                    class="inline-flex items-center rounded bg-slate-100 hover:bg-slate-200 px-2 py-0.5 text-[11px] font-mono text-slate-700 transition-colors"
                                    :title="v.label"
                                >
                                    {{ v.token }}
                                </button>
                            </div>
                            <textarea
                                id="email-body"
                                ref="bodyTextareaRef"
                                v-model="emailForm.body"
                                rows="7"
                                required
                                placeholder="Escribe aquí el mensaje para el taller..."
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            ></textarea>
                            <p v-if="emailForm.errors.body" class="mt-1 text-xs text-rose-600">
                                {{ emailForm.errors.body }}
                            </p>
                        </div>

                        <!-- Botón CTA Opcional -->
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 space-y-3">
                            <p class="text-xs font-semibold text-slate-700">Botón de acción en el correo (opcional)</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label for="email-action-label" class="block text-[11px] font-medium text-slate-500 mb-1">
                                        Texto del botón
                                    </label>
                                    <input
                                        id="email-action-label"
                                        v-model="emailForm.action_label"
                                        type="text"
                                        placeholder="Ir a mi taller"
                                        class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    />
                                    <p v-if="emailForm.errors.action_label" class="mt-1 text-xs text-rose-600">
                                        {{ emailForm.errors.action_label }}
                                    </p>
                                </div>
                                <div>
                                    <label for="email-action-url" class="block text-[11px] font-medium text-slate-500 mb-1">
                                        URL de destino
                                    </label>
                                    <input
                                        id="email-action-url"
                                        v-model="emailForm.action_url"
                                        type="url"
                                        placeholder="https://..."
                                        class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    />
                                    <p v-if="emailForm.errors.action_url" class="mt-1 text-xs text-rose-600">
                                        {{ emailForm.errors.action_url }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="emailForm.processing || !users.length"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 disabled:opacity-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <span>{{ emailForm.processing ? 'Enviando correo...' : 'Enviar correo ahora' }}</span>
                            </button>
                            <p v-if="!users.length" class="mt-2 text-xs text-amber-600 text-center">
                                Este taller no tiene usuarios registrados para recibir correos.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de Comunicaciones y Tracking -->
            <div class="lg:col-span-7">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Historial de correos enviados</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Registro de correos directos, recordatorios de renovación y campañas enviadas a {{ tenant.name }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                            {{ email_trackings.length }} enviados
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="py-3 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 sm:pl-5">Asunto / Destinatario</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Lectura</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Clics</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Enviado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="track in email_trackings" :key="track.id" class="hover:bg-slate-50/70">
                                    <td class="py-3 pl-4 pr-3 text-sm sm:pl-5 max-w-xs">
                                        <div class="font-medium text-slate-900 truncate" :title="track.subject">
                                            {{ track.subject || 'Sin asunto' }}
                                        </div>
                                        <div class="text-xs text-slate-500 truncate">
                                            {{ track.recipient_email }}
                                            <span v-if="track.metadata?.sent_by" class="text-slate-400">
                                                &bull; por {{ track.metadata.sent_by }}
                                            </span>
                                        </div>
                                        <button
                                            v-if="track.metadata?.body || track.metadata?.custom_message"
                                            type="button"
                                            @click="selectedTrackDetail = selectedTrackDetail?.id === track.id ? null : track"
                                            class="mt-1 text-xs font-medium text-orange-600 hover:text-orange-700"
                                        >
                                            {{ selectedTrackDetail?.id === track.id ? 'Ocultar mensaje' : 'Ver mensaje enviado' }}
                                        </button>
                                        <div
                                            v-if="selectedTrackDetail?.id === track.id"
                                            class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-700 whitespace-pre-line"
                                        >
                                            {{ track.metadata?.body || track.metadata?.custom_message }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                getEmailTypeMeta(track.type).badge,
                                            ]"
                                        >
                                            {{ getEmailTypeMeta(track.type).label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs">
                                        <div v-if="track.opened_at" class="flex flex-col">
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Abierto ({{ track.open_count }})
                                            </span>
                                            <span class="text-[11px] text-slate-400">{{ formatDateTime(track.opened_at) }}</span>
                                        </div>
                                        <span v-else class="inline-flex items-center gap-1 text-slate-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                            No abierto
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs">
                                        <div v-if="track.clicked_at" class="flex flex-col">
                                            <span class="font-semibold text-indigo-700">{{ track.click_count }} clic(s)</span>
                                            <span class="text-[11px] text-slate-400">{{ formatDateTime(track.clicked_at) }}</span>
                                        </div>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-500">
                                        {{ formatDateTime(track.sent_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!email_trackings.length">
                                    <td colspan="5" class="py-10 text-center text-sm text-slate-400">
                                        Aún no se han enviado correos a este taller.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 sm:pr-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/50">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">{{ u.name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ u.email }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ u.login_count ?? 0 }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDateTime(u.last_login_at) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-sm sm:pr-6">
                                <button
                                    type="button"
                                    @click="openComposeForUser(u)"
                                    class="inline-flex items-center gap-1 rounded-md bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 hover:bg-orange-100 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span>Escribir correo</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!users.length">
                            <td colspan="6" class="py-8 text-center text-sm text-slate-400">Sin usuarios</td>
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

<style scoped>
.no-scrollbar::-webkit-scrollbar,
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.no-scrollbar,
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
