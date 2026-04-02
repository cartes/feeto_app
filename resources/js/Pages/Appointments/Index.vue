<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import axios from 'axios';

const props = defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
    today: {
        type: String,
        default: '',
    },
});

// ─── State ─────────────────────────────────────────────────────────────────

const cameraInputRef    = ref(null);
const isScanning        = ref(false);
const scanError         = ref(null);
const scanResult        = ref(null);   // { plate, confidence, vehicle, appointment }

// ─── Status helpers ─────────────────────────────────────────────────────────

const STATUS_LABELS = {
    pending:   'Pendiente',
    arrived:   'Llegó',
    cancelled: 'Cancelado',
};

const STATUS_COLORS = {
    pending:   'bg-amber-100 text-amber-800',
    arrived:   'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => STATUS_LABELS[status] ?? status;
const statusColor = (status) => STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-700';

// ─── Scan logic ─────────────────────────────────────────────────────────────

const openCamera = () => {
    scanResult.value = null;
    scanError.value  = null;
    cameraInputRef.value?.click();
};

const processPlateImage = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Reset input so the same file can be re-selected
    event.target.value = '';

    isScanning.value  = true;
    scanResult.value  = null;
    scanError.value   = null;

    const formData = new FormData();
    formData.append('image', file);

    try {
        const { data } = await axios.post(route('api.appointments.scan-plate'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        scanResult.value = data;
    } catch (err) {
        const msg = err.response?.data?.message ?? 'Error al procesar la imagen. Intenta nuevamente.';
        scanError.value = msg;
    } finally {
        isScanning.value = false;
    }
};

const goToKanban = () => {
    router.visit(route('work-orders.index'));
};
</script>

<template>
    <Head title="Agendamiento y Recepción" />

    <TallerLayout>

        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4 px-1">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">
                    Agendamiento y Recepción
                </h1>
                <p class="text-sm font-medium text-gray-500 mt-1">
                    Agenda del día · {{ today }}
                </p>
            </div>
        </div>

        <!-- Two-panel layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ═══════════════════════════════════════════════════════════
                 LEFT PANEL – Today's Agenda
                 ═══════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 flex flex-col gap-4">

                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight">Agenda de Hoy</h2>
                    <span
                        class="text-xs font-semibold bg-gray-100 text-gray-600 px-3 py-1 rounded-full"
                    >
                        {{ appointments.length }} cita{{ appointments.length !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Empty state -->
                <div
                    v-if="appointments.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-400">Sin citas programadas para hoy</p>
                </div>

                <!-- Appointment list -->
                <ul v-else class="flex flex-col gap-3">
                    <li
                        v-for="appt in appointments"
                        :key="appt.id"
                        class="flex items-start gap-4 p-4 rounded-2xl border border-gray-100 hover:border-[#F9A826]/40 hover:bg-amber-50/30 transition-all"
                    >
                        <!-- Time -->
                        <div class="flex-shrink-0 w-14 text-center">
                            <span class="text-xl font-black text-gray-900 tabular-nums">
                                {{ appt.appointment_date }}
                            </span>
                            <p class="text-[10px] font-medium text-gray-400 mt-0.5">hrs</p>
                        </div>

                        <!-- Divider -->
                        <div class="w-px self-stretch bg-gray-100"></div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-gray-900 truncate">
                                    {{ appt.plate }}
                                </span>
                                <span
                                    :class="statusColor(appt.status)"
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                >
                                    {{ statusLabel(appt.status) }}
                                </span>
                            </div>

                            <p v-if="appt.client" class="text-sm text-gray-600 mt-0.5 truncate">
                                {{ appt.client.name }}
                            </p>

                            <p v-if="appt.vehicle" class="text-xs text-gray-400 mt-0.5 truncate">
                                {{ appt.vehicle.brand }} {{ appt.vehicle.model }}
                                <span v-if="appt.vehicle.color">· {{ appt.vehicle.color }}</span>
                            </p>

                            <p v-if="appt.notes" class="text-xs text-gray-400 mt-1 italic truncate">
                                "{{ appt.notes }}"
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 RIGHT PANEL – Smart Reception
                 ═══════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 flex flex-col gap-6">

                <div>
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight">Recepción Inteligente</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Captura la patente con la cámara y cruzamos con la agenda en segundos.
                    </p>
                </div>

                <!-- Hidden camera input -->
                <input
                    ref="cameraInputRef"
                    type="file"
                    accept="image/*"
                    capture="camera"
                    class="hidden"
                    @change="processPlateImage"
                />

                <!-- ── Scan button ── -->
                <button
                    :disabled="isScanning"
                    class="relative w-full flex flex-col items-center justify-center gap-4 rounded-3xl py-14 px-6
                           bg-gradient-to-br from-[#F9A826] to-[#e8920d] text-white
                           shadow-lg shadow-[#F9A826]/40 hover:shadow-xl hover:shadow-[#F9A826]/50
                           active:scale-[0.98] transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed"
                    @click="openCamera"
                >
                    <!-- Glow ring -->
                    <span class="absolute inset-0 rounded-3xl ring-4 ring-[#F9A826]/20 animate-pulse"></span>

                    <!-- Icon -->
                    <span v-if="!isScanning">
                        <svg class="w-16 h-16 drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span v-else>
                        <svg class="w-12 h-12 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </span>

                    <span class="text-xl font-black tracking-wide drop-shadow-sm">
                        {{ isScanning ? 'Analizando patente con IA…' : 'Escanear Patente de Ingreso' }}
                    </span>

                    <span v-if="!isScanning" class="text-sm font-medium text-white/80">
                        Toca para abrir la cámara
                    </span>
                </button>

                <!-- ── Error state ── -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="scanError"
                        class="flex items-start gap-3 rounded-2xl bg-red-50 border border-red-200 p-4"
                    >
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm font-medium text-red-700">{{ scanError }}</p>
                    </div>
                </Transition>

                <!-- ── Success card ── -->
                <Transition
                    enter-active-class="transition duration-400 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div
                        v-if="scanResult"
                        class="rounded-2xl border border-gray-100 overflow-hidden shadow-sm"
                    >
                        <!-- Plate detected -->
                        <div class="flex items-center gap-4 bg-gray-50 p-4 border-b border-gray-100">
                            <div
                                class="flex-shrink-0 bg-gray-900 text-white rounded-xl px-4 py-2 font-black text-xl tracking-widest font-mono shadow"
                            >
                                {{ scanResult.plate || '——' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-widest">
                                    ✓ Patente detectada
                                </p>
                                <p
                                    v-if="scanResult.vehicle?.brand"
                                    class="text-sm text-gray-600 mt-0.5 truncate"
                                >
                                    {{ scanResult.vehicle.brand }} {{ scanResult.vehicle.model }}
                                    <span v-if="scanResult.vehicle.color">· {{ scanResult.vehicle.color }}</span>
                                </p>
                            </div>
                            <div
                                v-if="scanResult.confidence != null"
                                class="flex-shrink-0 text-right"
                            >
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Confianza</p>
                                <p class="text-sm font-bold text-gray-700">
                                    {{ Math.round(scanResult.confidence * 100) }}%
                                </p>
                            </div>
                        </div>

                        <!-- Appointment found -->
                        <div v-if="scanResult.appointment" class="p-4 bg-emerald-50">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-emerald-800">
                                        Cita encontrada para
                                        <span v-if="scanResult.appointment.client">
                                            {{ scanResult.appointment.client.name }}
                                        </span>
                                        <span v-else>cliente sin registrar</span>
                                        a las {{ scanResult.appointment.time }} hrs
                                    </p>
                                    <p v-if="scanResult.appointment.client?.phone" class="text-xs text-emerald-700 mt-0.5">
                                        📞 {{ scanResult.appointment.client.phone }}
                                    </p>
                                    <p v-if="scanResult.appointment.notes" class="text-xs text-emerald-700 mt-0.5 italic">
                                        "{{ scanResult.appointment.notes }}"
                                    </p>
                                </div>
                            </div>

                            <button
                                class="mt-4 w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                                       bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white
                                       text-sm font-bold tracking-wide transition-all shadow-sm"
                                @click="goToKanban"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                Ingresar al Kanban
                            </button>
                        </div>

                        <!-- No appointment found -->
                        <div v-else class="p-4 bg-amber-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-amber-800">Sin cita agendada para hoy</p>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        La patente <strong>{{ scanResult.plate }}</strong> no tiene cita registrada hoy.
                                        Puedes crear una orden de trabajo directamente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

            </div>
            <!-- end right panel -->

        </div>
        <!-- end grid -->

    </TallerLayout>
</template>
