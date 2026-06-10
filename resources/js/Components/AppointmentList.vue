<script setup>
import { computed } from 'vue';
import { useStatusConfig } from '@/composables/useStatusConfig';

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
    canDelete: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['delete']);

const normalizedAppointments = computed(() => props.appointments ?? []);
const { resolveAppointmentStatus } = useStatusConfig();
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
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-[#FF7A00]/30"
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
                            <div class="flex items-center gap-2">
                                <span
                                    :class="resolveAppointmentStatus(appointment.status).classes"
                                    class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide"
                                >
                                    {{ resolveAppointmentStatus(appointment.status).label }}
                                </span>
                                <button
                                    v-if="canDelete"
                                    type="button"
                                    class="text-gray-300 transition-colors hover:text-red-500"
                                    title="Eliminar cita"
                                    @click.stop="emit('delete', appointment)"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
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
