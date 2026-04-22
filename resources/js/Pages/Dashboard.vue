<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
});

const page = usePage();
const DISMISSED_KEY = 'dismissed_activities';

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
const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const commercialReportsEnabled = computed(() => planAccess.value?.commercial_reports_enabled ?? false);

const getDismissed = () => {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [];
    }

    return JSON.parse(window.localStorage.getItem(DISMISSED_KEY) || '[]');
};

const recentActivities = ref(
    props.initialActivities.filter((activity) => {
        const uniqueId = `${activity.work_order_id}-${activity.new_status}`;
        return !getDismissed().includes(uniqueId);
    }),
);

const getStatusLabel = (status) => {
    const labels = {
        recepcion: 'Recepción',
        diagnostico: 'En Diagnóstico',
        esperando_repuestos: 'Faltan Repuestos',
        control_calidad: 'Control de Calidad',
        listo: 'Listo',
    };

    return labels[status] || status;
};

const dismissActivity = (activity) => {
    const uniqueId = `${activity.work_order_id}-${activity.new_status || activity.status}`;

    recentActivities.value = recentActivities.value.filter(
        (value) => `${value.work_order_id}-${value.new_status || value.status}` !== uniqueId,
    );

    const dismissed = getDismissed();
    if (!dismissed.includes(uniqueId)) {
        dismissed.push(uniqueId);
        window.localStorage.setItem(DISMISSED_KEY, JSON.stringify(dismissed));
    }
};

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
        <div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="flex flex-col justify-between gap-6 px-2 md:flex-row md:items-end">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard Operativo</h1>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500">
                            Sistema Activo <span class="mx-2 text-gray-300">|</span> Santiago Hub
                        </p>
                    </div>
                </div>
                <div class="hidden text-right md:block">
                    <p class="mb-1 text-[10px] font-extrabold uppercase tracking-[0.2em] text-gray-400">Cronología</p>
                    <p class="text-sm font-semibold tabular-nums text-gray-600">
                        {{ new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <Link
                    v-if="canManageAppointments"
                    :href="route('receptions.create', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                            <circle cx="12" cy="13" r="3" class="stroke-tech-orange" stroke-width="2" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Nueva<br>Recepción</span>
                </Link>

                <Link
                    v-if="canViewWorkOrders"
                    :href="route('work-orders.index', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                            <path d="M9 3v18" class="stroke-tech-orange" stroke-width="2" />
                            <path d="M15 3v18" class="opacity-20" />
                            <path d="M5 8h2m-2 4h2m-2 4h2" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Tablero<br>de Órdenes</span>
                </Link>

                <Link
                    v-if="canManageInventory"
                    :href="route('inventory.index', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z" />
                            <path d="M3 10h18" class="stroke-tech-orange" stroke-width="2" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Inventario<br>de Stock</span>
                </Link>

                <Link
                    v-if="canManageInventory && commercialQuotesEnabled"
                    :href="route('services.index', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12h6m-3-3v6m6 5H6a2 2 0 01-2-2V8a2 2 0 012-2h3.172a2 2 0 011.414.586l.828.828A2 2 0 0012.828 8H18a2 2 0 012 2v8a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Catálogo<br>de Servicios</span>
                </Link>

                <Link
                    v-if="canManageCustomers"
                    :href="route('clients.index', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="3" class="stroke-tech-orange" stroke-width="2" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" class="opacity-20" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Gestión<br>de Clientes</span>
                </Link>

                <Link
                    v-if="canViewReports && commercialReportsEnabled"
                    :href="route('reports.index', tenantRouteParams)"
                    class="group relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:border-tech-orange/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.04)] active:scale-[0.98]"
                >
                    <div class="flex h-8 w-8 items-center justify-center">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-gray-800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-xl font-black uppercase leading-tight tracking-tight text-gray-900">Reportes<br>Comerciales</span>
                </Link>

                <div
                    v-if="canManageInventory && !commercialQuotesEnabled"
                    class="relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-dashed border-orange-200 bg-orange-50/70 p-6 shadow-sm"
                >
                    <div class="flex h-8 w-8 items-center justify-center text-orange-500">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-current" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12h6m-3-3v6m6 5H6a2 2 0 01-2-2V8a2 2 0 012-2h3.172a2 2 0 011.414.586l.828.828A2 2 0 0012.828 8H18a2 2 0 012 2v8a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="mb-2 inline-flex rounded-full bg-white px-2 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                        <p class="text-lg font-black uppercase leading-tight tracking-tight text-gray-900">Cotizaciones<br>Comerciales</p>
                        <p class="mt-2 text-xs font-medium text-gray-500">{{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}</p>
                    </div>
                </div>

                <div
                    v-if="canViewReports && commercialQuotesEnabled && !commercialReportsEnabled"
                    class="relative flex min-h-[120px] flex-col justify-between rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/70 p-6 shadow-sm"
                >
                    <div class="flex h-8 w-8 items-center justify-center text-emerald-500">
                        <svg viewBox="0 0 24 24" fill="none" class="h-full w-full stroke-current" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="mb-2 inline-flex rounded-full bg-white px-2 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-600">Upgrade</span>
                        <p class="text-lg font-black uppercase leading-tight tracking-tight text-gray-900">Reportes<br>Avanzados</p>
                        <p class="mt-2 text-xs font-medium text-gray-500">{{ planAccess?.upgrade_messages?.commercial_reports_enabled }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
                <div v-if="commercialReportsEnabled" class="rounded-[2rem] border border-gray-100 bg-white/40 p-8 shadow-sm backdrop-blur-sm">
                    <div class="mb-8 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Actividad en Tiempo Real</h3>
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
                            </span>
                        </div>
                        <span class="rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-bold text-orange-500">En Vivo</span>
                    </div>

                    <div v-if="recentActivities.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50">
                            <svg class="h-6 w-6 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-tight text-gray-400">Esperando actualizaciones del tablero...</p>
                    </div>

                    <TransitionGroup
                        tag="ul"
                        class="relative flex flex-col gap-3"
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 -translate-x-4"
                        leave-active-class="absolute w-full transition-all duration-300 ease-in"
                        leave-to-class="scale-95 opacity-0"
                    >
                        <li
                            v-for="activity in recentActivities"
                            :key="activity.work_order_id || activity.id"
                            class="flex items-center justify-between rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-all duration-500"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-50">
                                    <svg class="h-5 w-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ activity.vehicle }} <span class="font-normal text-gray-400">({{ activity.plate }})</span></p>
                                    <p class="mt-0.5 text-xs text-gray-500">Estado actualizado a: <span class="font-bold uppercase text-gray-800">{{ getStatusLabel(activity.new_status || activity.status) }}</span></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-[10px] font-bold text-gray-400">AHORA</span>
                                <button
                                    type="button"
                                    class="text-gray-300 transition-colors duration-200 hover:text-orange-500"
                                    title="Descartar"
                                    @click="dismissActivity(activity)"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </li>
                    </TransitionGroup>
                </div>

                <div :class="commercialReportsEnabled ? '' : 'xl:col-span-2'" class="rounded-[2rem] border border-gray-100 bg-white/40 p-8 shadow-sm backdrop-blur-sm">
                    <div class="mb-8 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notificaciones de Cotización</h3>
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                        </div>
                        <Link
                            v-if="canViewReports"
                            :href="route('reports.index', tenantRouteParams)"
                            class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600"
                        >
                            Ver Reportes
                        </Link>
                    </div>

                    <div v-if="quoteNotifications.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50">
                            <svg class="h-6 w-6 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-tight text-gray-400">Sin eventos comerciales recientes</p>
                    </div>

                    <ul v-else class="flex flex-col gap-3">
                        <li v-for="notification in quoteNotifications" :key="notification.id" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ notification.description }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ notification.client }} · {{ notification.plate }} · OT #{{ notification.work_order_id }}</p>
                                </div>
                                <span class="whitespace-nowrap rounded-md bg-gray-100 px-2 py-1 text-[10px] font-bold text-gray-400">
                                    {{ new Date(notification.timestamp).toLocaleDateString('es-CL') }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
