<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppointmentCalendar from '@/Components/AppointmentCalendar.vue';
import AppointmentList from '@/Components/AppointmentList.vue';
import PlanUpgradeBanner from '@/Components/PlanUpgradeBanner.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
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
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const planAccess = computed(() => page.props.planAccess ?? {});
const calendarSchedulingEnabled = computed(() => planAccess.value?.calendar_scheduling ?? false);
const aiReceptionEnabled = computed(() => planAccess.value?.ai_reception ?? false);
const calendarUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.calendar_scheduling ?? 'Mejora tu plan para acceder a esta función');
const aiReceptionUpgradeMessage = computed(() => planAccess.value?.upgrade_messages?.ai_reception ?? 'Mejora tu plan para acceder a esta función');

const cameraInputRef = ref(null);
const isScanning = ref(false);
const scanError = ref(null);
const scanResult = ref(null);

const openCamera = () => {
    scanResult.value = null;
    scanError.value = null;
    cameraInputRef.value?.click();
};

const processPlateImage = async (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    event.target.value = '';

    isScanning.value = true;
    scanResult.value = null;
    scanError.value = null;

    const formData = new FormData();
    formData.append('image', file);

    try {
        const { data } = await axios.post(route('api.appointments.scan-plate', tenantRouteParams.value), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        scanResult.value = data;
    } catch (error) {
        scanError.value = error.response?.data?.message ?? 'Error al procesar la imagen. Intenta nuevamente.';
    } finally {
        isScanning.value = false;
    }
};

const goToKanban = () => {
    router.visit(route('work-orders.index', tenantRouteParams.value));
};

const hasNotifications = computed(() => props.appointmentNotifications.length > 0);

const notificationStatusClass = (status) => ({
    pending: 'bg-amber-100 text-amber-800',
    arrived: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
}[status] ?? 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="Agendamiento y Recepción" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.3em] text-gray-400">Agendamiento</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-gray-900">Agenda y Recepción</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">
                        Seguimiento diario de citas y recepción asistida por patente.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-gray-500 shadow-sm">
                        Hoy {{ today }}
                    </span>
                    <Link
                        :href="route('receptions.create', tenantRouteParams)"
                        class="rounded-full bg-[#F9A826] px-5 py-3 text-sm font-black uppercase tracking-wide text-white shadow-[0_12px_24px_rgba(249,168,38,0.25)] transition hover:bg-[#E59A22]"
                    >
                        Ir a Recepción
                    </Link>
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

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
                    <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Resumen del día</p>
                                <h2 class="mt-1 text-xl font-black text-gray-900">Citas de hoy</h2>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">
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

                    <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Alertas</p>
                            <h2 class="mt-1 text-xl font-black text-gray-900">Próximas citas</h2>
                        </div>

                        <div
                            v-if="!hasNotifications"
                            class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
                        >
                            <p class="text-sm font-bold text-gray-500">Sin alertas de agenda</p>
                            <p class="mt-1 text-sm text-gray-400">Cuando existan nuevas citas o movimientos aparecerán aquí.</p>
                        </div>

                        <ul v-else class="flex flex-col gap-3">
                            <li
                                v-for="notification in appointmentNotifications"
                                :key="notification.id"
                                class="rounded-2xl border border-gray-100 bg-gray-50 p-4"
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

            <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Agenda básica</p>
                            <h2 class="mt-1 text-xl font-black text-gray-900">Listado de citas</h2>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600">
                            {{ appointments.length }} cita{{ appointments.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <AppointmentList
                        :appointments="appointments"
                        empty-title="Sin citas en el mes actual"
                        empty-description="Aún no hay citas registradas en este periodo."
                    />
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Notificaciones</p>
                        <h2 class="mt-1 text-xl font-black text-gray-900">Próximos ingresos</h2>
                    </div>

                    <div
                        v-if="!hasNotifications"
                        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
                    >
                        <p class="text-sm font-bold text-gray-500">Sin movimientos programados</p>
                        <p class="mt-1 text-sm text-gray-400">Aquí verás un resumen rápido de las próximas citas.</p>
                    </div>

                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="notification in appointmentNotifications"
                            :key="notification.id"
                            class="rounded-2xl border border-gray-100 bg-gray-50 p-4"
                        >
                            <p class="text-sm font-black text-gray-900">{{ notification.title }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ notification.description }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Recepción inteligente</p>
                        <h2 class="mt-1 text-xl font-black text-gray-900">Escaneo por patente</h2>
                        <p class="mt-2 text-sm text-gray-500">
                            Captura la patente con la cámara y cruza la información contra las citas del día.
                        </p>
                    </div>

                    <PlanUpgradeBanner
                        v-if="!aiReceptionEnabled"
                        title="Escáner con IA no disponible"
                        :message="`Mejora tu plan para acceder a esta función. ${aiReceptionUpgradeMessage}`"
                    />

                    <div v-else class="space-y-4">
                        <input
                            ref="cameraInputRef"
                            type="file"
                            accept="image/*"
                            capture="camera"
                            class="hidden"
                            @change="processPlateImage"
                        />

                        <button
                            :disabled="isScanning"
                            class="relative flex w-full flex-col items-center justify-center gap-4 rounded-[2rem] bg-gradient-to-br from-[#F9A826] to-[#E8920D] px-6 py-14 text-white shadow-[0_18px_36px_rgba(249,168,38,0.28)] transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-70"
                            @click="openCamera"
                        >
                            <span class="absolute inset-0 rounded-[2rem] ring-4 ring-[#F9A826]/20"></span>
                            <svg v-if="!isScanning" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else class="h-12 w-12 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span class="text-xl font-black tracking-wide">
                                {{ isScanning ? 'Analizando patente...' : 'Escanear patente de ingreso' }}
                            </span>
                            <span class="text-sm font-medium text-white/80">
                                {{ isScanning ? 'Esperando respuesta del motor de IA' : 'Toca para abrir la cámara' }}
                            </span>
                        </button>

                        <div
                            v-if="scanError"
                            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                        >
                            {{ scanError }}
                        </div>

                        <div
                            v-if="scanResult"
                            class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white"
                        >
                            <div class="flex items-center gap-4 border-b border-gray-100 bg-gray-50 p-4">
                                <div class="rounded-xl bg-gray-900 px-4 py-2 font-mono text-xl font-black tracking-widest text-white">
                                    {{ scanResult.plate || '------' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">Patente detectada</p>
                                    <p v-if="scanResult.vehicle?.brand" class="truncate text-sm text-gray-600">
                                        {{ scanResult.vehicle.brand }} {{ scanResult.vehicle.model }}
                                        <span v-if="scanResult.vehicle.color">· {{ scanResult.vehicle.color }}</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="scanResult.appointment" class="space-y-3 bg-emerald-50 p-4">
                                <p class="text-sm font-bold text-emerald-800">
                                    Cita encontrada para {{ scanResult.appointment.client?.name || 'cliente sin registrar' }}
                                    a las {{ scanResult.appointment.time }} hrs
                                </p>
                                <p v-if="scanResult.appointment.notes" class="text-xs italic text-emerald-700">
                                    "{{ scanResult.appointment.notes }}"
                                </p>
                                <button
                                    type="button"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-emerald-700"
                                    @click="goToKanban"
                                >
                                    Ingresar al Kanban
                                </button>
                            </div>

                            <div v-else class="bg-amber-50 p-4">
                                <p class="text-sm font-bold text-amber-800">Sin cita agendada para hoy</p>
                                <p class="mt-1 text-xs text-amber-700">
                                    La patente {{ scanResult.plate }} no tiene una cita registrada en la jornada actual.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Resumen rápido</p>
                        <h2 class="mt-1 text-xl font-black text-gray-900">Citas de hoy</h2>
                    </div>

                    <AppointmentList
                        :appointments="todayAppointments"
                        empty-title="Jornada sin citas"
                        empty-description="No hay recepciones agendadas para el día de hoy."
                        :show-date="false"
                    />
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
