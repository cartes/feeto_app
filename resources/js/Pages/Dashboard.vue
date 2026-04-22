<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppointmentCalendar from '@/Components/AppointmentCalendar.vue';
import AppointmentList from '@/Components/AppointmentList.vue';
import PlanUpgradeBanner from '@/Components/PlanUpgradeBanner.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    initialActivities: {
        type: Array,
        default: () => [],
    },
    quoteNotifications: {
        type: Array,
        default: () => [],
    },
    appointments: {
        type: Array,
        default: () => [],
    },
    todayAppointments: {
        type: Array,
        default: () => [],
    },
    appointmentNotifications: {
        type: Array,
        default: () => [],
    },
    today: {
        type: String,
        default: '',
    },
    calendarRange: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const dismissedKey = 'dismissed_activities';

const tenantId = computed(() => page.props.tenant?.id ?? page.props.auth?.user?.tenant_id ?? null);
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const hasPermission = (permission) => permissions.value.includes(permission);
const canManageAppointments = computed(() => hasPermission('appointments.manage'));
const canViewWorkOrders = computed(() => ['work-orders.view', 'work-orders.view-own', 'work-orders.update-status', 'work-orders.manage-items']
    .some((permission) => hasPermission(permission)));
const canManageInventory = computed(() => hasPermission('inventory.manage'));
const canManageCustomers = computed(() => hasPermission('customers.manage'));
const canViewReports = computed(() => hasPermission('reports.view'));
const planAccess = computed(() => page.props.planAccess ?? {});
const aiReceptionEnabled = computed(() => planAccess.value?.ai_reception ?? false);
const calendarSchedulingEnabled = computed(() => planAccess.value?.calendar_scheduling ?? false);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const commercialReportsEnabled = computed(() => planAccess.value?.commercial_reports_enabled ?? false);
const calendarUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.calendar_scheduling ?? 'Mejora tu plan para acceder a esta función');
const aiReceptionUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.ai_reception ?? 'Mejora tu plan para acceder a esta función');

const getDismissed = () => {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [];
    }

    return JSON.parse(window.localStorage.getItem(dismissedKey) || '[]');
};

const recentActivities = ref(
    props.initialActivities.filter((activity) => {
        const uniqueId = `${activity.work_order_id}-${activity.new_status}`;
        return !getDismissed().includes(uniqueId);
    }),
);

const getStatusLabel = (status) => ({
    recepcion: 'Recepción',
    diagnostico: 'En diagnóstico',
    esperando_repuestos: 'Esperando repuestos',
    control_calidad: 'Control de calidad',
    listo: 'Listo',
}[status] ?? status);

const dismissActivity = (activity) => {
    const uniqueId = `${activity.work_order_id}-${activity.new_status || activity.status}`;

    recentActivities.value = recentActivities.value.filter(
        (value) => `${value.work_order_id}-${value.new_status || value.status}` !== uniqueId,
    );

    const dismissed = getDismissed();

    if (!dismissed.includes(uniqueId)) {
        dismissed.push(uniqueId);
        window.localStorage.setItem(dismissedKey, JSON.stringify(dismissed));
    }
};

const quickLinks = computed(() => ([
    {
        label: 'Nueva Recepción',
        route: 'receptions.create',
        visible: canManageAppointments.value,
        accent: 'bg-white',
        iconPath: 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M12 10a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
    },
    {
        label: 'Agenda',
        route: 'appointments.index',
        visible: canManageAppointments.value,
        accent: 'bg-white',
        iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z',
    },
    {
        label: 'Órdenes',
        route: 'work-orders.index',
        visible: canViewWorkOrders.value,
        accent: 'bg-white',
        iconPath: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2 M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2 M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2',
    },
    {
        label: 'Inventario',
        route: 'inventory.index',
        visible: canManageInventory.value,
        accent: 'bg-white',
        iconPath: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10',
    },
    {
        label: 'Servicios',
        route: 'services.index',
        visible: canManageInventory.value && commercialQuotesEnabled.value,
        accent: 'bg-white',
        iconPath: 'M9 12h6m-3-3v6m6 5H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.172a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 12.828 8H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2z',
    },
    {
        label: 'Clientes',
        route: 'clients.index',
        visible: canManageCustomers.value,
        accent: 'bg-white',
        iconPath: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0 .01 M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
    },
    {
        label: 'Reportes',
        route: 'reports.index',
        visible: canViewReports.value && commercialReportsEnabled.value,
        accent: 'bg-white',
        iconPath: 'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z',
    },
]).filter((item) => item.visible));

const notificationStatusClass = (status) => ({
    pending: 'bg-amber-100 text-amber-800',
    arrived: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-700');

onMounted(() => {
    if (!tenantId.value || !window.Echo?.private) {
        return;
    }

    window.Echo.private(`taller.${tenantId.value}`)
        .listen('.kanban.updated', (event) => {
            recentActivities.value.unshift(event);
        });
});

onUnmounted(() => {
    if (!tenantId.value || !window.Echo?.leave) {
        return;
    }

    window.Echo.leave(`taller.${tenantId.value}`);
});
</script>

<template>
    <Head title="Centro de Comando" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.3em] text-gray-400">Dashboard operativo</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-gray-900">Centro de comando del taller</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">
                        {{ new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-black uppercase tracking-[0.25em] text-gray-500">Sistema activo</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                <Link
                    v-for="item in quickLinks"
                    :key="item.label"
                    :href="route(item.route, tenantRouteParams)"
                    class="group rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-[#F9A826]/30 hover:shadow-[0_18px_36px_rgba(0,0,0,0.05)]"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#F9A826]/10 text-[#F9A826]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                        </svg>
                    </div>
                    <p class="mt-6 text-lg font-black uppercase tracking-tight text-gray-900">{{ item.label }}</p>
                </Link>
            </div>

            <div class="space-y-6 rounded-[2rem] border border-gray-100 bg-white/70 p-6 shadow-sm backdrop-blur-sm">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Agendamiento</p>
                        <h2 class="mt-1 text-2xl font-black text-gray-900">Agenda del taller</h2>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            v-if="canManageAppointments"
                            :href="route('appointments.index', tenantRouteParams)"
                            class="rounded-full bg-gray-900 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-white transition hover:bg-gray-800"
                        >
                            Ver agenda
                        </Link>
                        <span
                            class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.2em]"
                            :class="calendarSchedulingEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                        >
                            {{ calendarSchedulingEnabled ? 'Calendario activo' : 'Modo básico' }}
                        </span>
                    </div>
                </div>

                <PlanUpgradeBanner
                    v-if="!calendarSchedulingEnabled"
                    title="Calendario interactivo no disponible"
                    :message="`Mejora tu plan para acceder a esta función. ${calendarUpgradeMessage}`"
                />

                <div v-if="calendarSchedulingEnabled" class="space-y-6">
                    <AppointmentCalendar
                        :appointments="appointments"
                        :today="today"
                    />

                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
                        <div class="rounded-[2rem] border border-gray-100 bg-gray-50 p-5">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Hoy</p>
                                    <h3 class="mt-1 text-xl font-black text-gray-900">Citas del día</h3>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-gray-600">
                                    {{ todayAppointments.length }} cita{{ todayAppointments.length !== 1 ? 's' : '' }}
                                </span>
                            </div>

                            <AppointmentList
                                :appointments="todayAppointments"
                                empty-title="Sin citas para hoy"
                                empty-description="No hay ingresos programados para la jornada actual."
                                :show-date="false"
                            />
                        </div>

                        <div class="rounded-[2rem] border border-gray-100 bg-gray-50 p-5">
                            <div class="mb-4">
                                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Recepción inteligente</p>
                                <h3 class="mt-1 text-xl font-black text-gray-900">Estado del escáner</h3>
                            </div>

                            <PlanUpgradeBanner
                                v-if="!aiReceptionEnabled"
                                title="Escáner con IA no disponible"
                                :message="`Mejora tu plan para acceder a esta función. ${aiReceptionUpgradeMessage}`"
                                tone="slate"
                            />

                            <div v-else class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-black text-emerald-800">Recepción asistida habilitada</p>
                                <p class="mt-1 text-sm text-emerald-700">
                                    Puedes usar el escáner de patente desde Recepción y desde el módulo de Agendamiento.
                                </p>
                                <Link
                                    :href="route('receptions.create', tenantRouteParams)"
                                    class="mt-4 inline-flex rounded-full bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-white transition hover:bg-emerald-700"
                                >
                                    Abrir recepción
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
                    <div class="rounded-[2rem] border border-gray-100 bg-gray-50 p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Agenda básica</p>
                                <h3 class="mt-1 text-xl font-black text-gray-900">Listado de citas</h3>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-gray-600">
                                {{ appointments.length }} cita{{ appointments.length !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <AppointmentList
                            :appointments="appointments"
                            empty-title="Sin citas registradas"
                            empty-description="No hay citas agendadas en el periodo actual."
                        />
                    </div>

                    <div class="rounded-[2rem] border border-gray-100 bg-gray-50 p-5">
                        <div class="mb-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Notificaciones</p>
                            <h3 class="mt-1 text-xl font-black text-gray-900">Próximos ingresos</h3>
                        </div>

                        <div
                            v-if="appointmentNotifications.length === 0"
                            class="rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-8 text-center"
                        >
                            <p class="text-sm font-bold text-gray-500">Sin movimientos programados</p>
                            <p class="mt-1 text-sm text-gray-400">Aquí verás un resumen rápido de las próximas citas.</p>
                        </div>

                        <ul v-else class="flex flex-col gap-3">
                            <li
                                v-for="notification in appointmentNotifications"
                                :key="notification.id"
                                class="rounded-2xl border border-gray-100 bg-white p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-gray-900">{{ notification.title }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ notification.description }}</p>
                                    </div>
                                    <span
                                        :class="notificationStatusClass(notification.status)"
                                        class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide"
                                    >
                                        {{ notification.status }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
                <div class="rounded-[2rem] border border-gray-100 bg-white/70 p-6 shadow-sm backdrop-blur-sm">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Actividad en tiempo real</p>
                            <h2 class="mt-1 text-2xl font-black text-gray-900">Movimientos del tablero</h2>
                        </div>
                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Live</span>
                    </div>

                    <div
                        v-if="recentActivities.length === 0"
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-10 text-center"
                    >
                        <p class="text-sm font-bold text-gray-500">Esperando actualizaciones del tablero</p>
                        <p class="mt-1 text-sm text-gray-400">Los cambios de estado aparecerán aquí automáticamente.</p>
                    </div>

                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="activity in recentActivities"
                            :key="`${activity.work_order_id}-${activity.new_status || activity.status}`"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50 p-4"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-gray-900">
                                    {{ activity.vehicle }} <span class="font-medium text-gray-400">({{ activity.plate }})</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Estado actualizado a
                                    <span class="font-black uppercase text-gray-700">{{ getStatusLabel(activity.new_status || activity.status) }}</span>
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-full bg-white px-3 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 transition hover:text-orange-600"
                                @click="dismissActivity(activity)"
                            >
                                Ocultar
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white/70 p-6 shadow-sm backdrop-blur-sm">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Comercial</p>
                            <h2 class="mt-1 text-2xl font-black text-gray-900">Notificaciones de cotización</h2>
                        </div>
                        <Link
                            v-if="canViewReports && commercialReportsEnabled"
                            :href="route('reports.index', tenantRouteParams)"
                            class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-emerald-600"
                        >
                            Ver reportes
                        </Link>
                    </div>

                    <div
                        v-if="quoteNotifications.length === 0"
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-10 text-center"
                    >
                        <p class="text-sm font-bold text-gray-500">Sin eventos comerciales recientes</p>
                        <p class="mt-1 text-sm text-gray-400">Las respuestas y envíos de cotización aparecerán aquí.</p>
                    </div>

                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="notification in quoteNotifications"
                            :key="notification.id"
                            class="rounded-2xl border border-gray-100 bg-gray-50 p-4"
                        >
                            <p class="text-sm font-black text-gray-900">{{ notification.description }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ notification.client }} · {{ notification.plate }} · OT #{{ notification.work_order_id }}
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
