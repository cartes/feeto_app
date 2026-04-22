<script setup>
import { computed, ref, watch } from 'vue';

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

const currentMonth = ref(props.today ? new Date(`${props.today}T12:00:00`) : new Date());
const selectedDate = ref(props.today || new Date().toISOString().slice(0, 10));

watch(() => props.today, (value) => {
    if (!value) {
        return;
    }

    currentMonth.value = new Date(`${value}T12:00:00`);
    selectedDate.value = value;
}, { immediate: true });

const appointmentsByDate = computed(() => {
    return (props.appointments ?? []).reduce((carry, appointment) => {
        const items = carry[appointment.date] ?? [];
        items.push(appointment);
        carry[appointment.date] = items;

        return carry;
    }, {});
});

const monthLabel = computed(() => currentMonth.value.toLocaleDateString('es-CL', {
    month: 'long',
    year: 'numeric',
}));

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);
    const firstWeekday = (firstDayOfMonth.getDay() + 6) % 7;
    const days = [];

    for (let index = 0; index < firstWeekday; index += 1) {
        days.push({ key: `empty-${index}`, empty: true });
    }

    for (let day = 1; day <= lastDayOfMonth.getDate(); day += 1) {
        const date = new Date(year, month, day);
        const isoDate = date.toISOString().slice(0, 10);

        days.push({
            key: isoDate,
            empty: false,
            date: isoDate,
            day,
            isToday: isoDate === props.today,
            isSelected: isoDate === selectedDate.value,
            count: appointmentsByDate.value[isoDate]?.length ?? 0,
        });
    }

    return days;
});

const selectedAppointments = computed(() => appointmentsByDate.value[selectedDate.value] ?? []);

const weekDays = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];

const previousMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
    selectedDate.value = currentMonth.value.toISOString().slice(0, 10);
};

const nextMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
    selectedDate.value = currentMonth.value.toISOString().slice(0, 10);
};

const selectDate = (date) => {
    selectedDate.value = date;
};

const statusLabel = (status) => ({
    pending: 'Pendiente',
    arrived: 'Llegó',
    cancelled: 'Cancelado',
}[status] ?? status);

const formattedSelectedDate = computed(() => new Date(`${selectedDate.value}T12:00:00`).toLocaleDateString('es-CL', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
}));
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)]">
        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Calendario interactivo</p>
                    <h3 class="mt-1 text-2xl font-black capitalize text-gray-900">{{ monthLabel }}</h3>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-[#F9A826] hover:text-[#F9A826]"
                        @click="previousMonth"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-[#F9A826] hover:text-[#F9A826]"
                        @click="nextMonth"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-2">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="pb-2 text-center text-[11px] font-black uppercase tracking-[0.2em] text-gray-400"
                >
                    {{ day }}
                </div>

                <template v-for="day in calendarDays" :key="day.key">
                    <div
                        v-if="day.empty"
                        class="aspect-square rounded-2xl border border-transparent"
                    ></div>

                    <button
                        v-else
                        type="button"
                        :class="day.isSelected ? 'border-[#F9A826] bg-[#F9A826] text-white shadow-[0_12px_24px_rgba(249,168,38,0.22)]' : day.isToday ? 'border-[#F9A826]/30 bg-amber-50 text-gray-900' : 'border-gray-100 bg-gray-50 text-gray-700 hover:border-[#F9A826]/30 hover:bg-amber-50/60'"
                        class="aspect-square rounded-2xl border p-2 text-left transition"
                        @click="selectDate(day.date)"
                    >
                        <div class="flex h-full flex-col justify-between">
                            <span class="text-sm font-black">{{ day.day }}</span>
                            <span
                                v-if="day.count"
                                :class="day.isSelected ? 'bg-white/20 text-white' : 'bg-white text-[#F9A826]'"
                                class="inline-flex w-fit rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm"
                            >
                                {{ day.count }} cita{{ day.count !== 1 ? 's' : '' }}
                            </span>
                            <span
                                v-else
                                :class="day.isSelected ? 'bg-white/15 text-white/70' : 'bg-white text-gray-300'"
                                class="inline-flex h-2 w-2 rounded-full"
                            ></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <div class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Detalle del día</p>
                <h3 class="mt-1 text-lg font-black capitalize text-gray-900">{{ formattedSelectedDate }}</h3>
            </div>

            <div
                v-if="selectedAppointments.length === 0"
                class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
            >
                <p class="text-sm font-bold text-gray-500">No hay citas en la fecha seleccionada</p>
                <p class="mt-1 text-sm text-gray-400">Selecciona otro día del calendario para revisar la agenda.</p>
            </div>

            <ul v-else class="flex flex-col gap-3">
                <li
                    v-for="appointment in selectedAppointments"
                    :key="appointment.id"
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-black uppercase tracking-wide text-gray-900">{{ appointment.plate }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-700">{{ appointment.client?.name || 'Cliente sin registrar' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black tabular-nums text-gray-900">{{ appointment.time }}</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">
                                {{ statusLabel(appointment.status) }}
                            </p>
                        </div>
                    </div>

                    <p v-if="appointment.vehicle" class="mt-2 text-xs text-gray-400">
                        {{ appointment.vehicle.brand }} {{ appointment.vehicle.model }}
                        <span v-if="appointment.vehicle.color">· {{ appointment.vehicle.color }}</span>
                    </p>

                    <p v-if="appointment.notes" class="mt-2 text-xs italic text-gray-400">
                        "{{ appointment.notes }}"
                    </p>
                </li>
            </ul>
        </div>
    </div>
</template>
