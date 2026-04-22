<script setup>
import { computed } from 'vue';

const props = defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
    emptyTitle: {
        type: String,
        default: 'Sin citas registradas',
    },
    emptyDescription: {
        type: String,
        default: 'No hay citas para mostrar en este rango.',
    },
    showDate: {
        type: Boolean,
        default: true,
    },
});

const statusLabels = {
    pending: 'Pendiente',
    arrived: 'Llegó',
    cancelled: 'Cancelado',
};

const statusColors = {
    pending: 'bg-amber-100 text-amber-800',
    arrived: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
};

const normalizedAppointments = computed(() => props.appointments ?? []);

const statusLabel = (status) => statusLabels[status] ?? status;
const statusColor = (status) => statusColors[status] ?? 'bg-gray-100 text-gray-700';
const formatDate = (date) => new Date(`${date}T12:00:00`).toLocaleDateString('es-CL', { day: '2-digit', month: 'short' });
</script>

<template>
    <div>
        <div
            v-if="normalizedAppointments.length === 0"
            class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-10 text-center"
        >
            <p class="text-sm font-bold text-gray-500">{{ emptyTitle }}</p>
            <p class="mt-1 text-sm text-gray-400">{{ emptyDescription }}</p>
        </div>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="appointment in normalizedAppointments"
                :key="appointment.id"
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-[#F9A826]/30"
            >
                <div class="flex items-start gap-4">
                    <div class="w-16 flex-shrink-0 text-center">
                        <p class="text-lg font-black tabular-nums text-gray-900">{{ appointment.time }}</p>
                        <p v-if="showDate" class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            {{ formatDate(appointment.date) }}
                        </p>
                    </div>

                    <div class="min-h-full w-px bg-gray-100"></div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-black uppercase tracking-wide text-gray-900">
                                {{ appointment.plate }}
                            </p>
                            <span
                                :class="statusColor(appointment.status)"
                                class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide"
                            >
                                {{ statusLabel(appointment.status) }}
                            </span>
                        </div>

                        <p class="mt-1 truncate text-sm font-medium text-gray-700">
                            {{ appointment.client?.name || 'Cliente sin registrar' }}
                        </p>

                        <p v-if="appointment.vehicle" class="mt-1 truncate text-xs text-gray-400">
                            {{ appointment.vehicle.brand }} {{ appointment.vehicle.model }}
                            <span v-if="appointment.vehicle.color">· {{ appointment.vehicle.color }}</span>
                        </p>

                        <p v-if="appointment.notes" class="mt-1 truncate text-xs italic text-gray-400">
                            "{{ appointment.notes }}"
                        </p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
