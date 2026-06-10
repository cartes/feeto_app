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
    canDelete: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['delete']);

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
        days.push({ key: `empty-${index}`, empty: true, column: index });
    }

    for (let day = 1; day <= lastDayOfMonth.getDate(); day += 1) {
        const date = new Date(year, month, day);
        const isoDate = date.toISOString().slice(0, 10);
        const overallIndex = firstWeekday + day - 1;

        days.push({
            key: isoDate,
            empty: false,
            date: isoDate,
            day,
            isToday: isoDate === props.today,
            isSelected: isoDate === selectedDate.value,
            count: appointmentsByDate.value[isoDate]?.length ?? 0,
            column: overallIndex % 7,
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
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-[#FF7A00] hover:text-[#FF7A00]"
                        @click="previousMonth"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-[#FF7A00] hover:text-[#FF7A00]"
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
                        :class="[
                            day.isSelected ? 'border-[#FF7A00] bg-[#FF7A00] text-white shadow-[0_12px_24px_rgba(249,168,38,0.22)]' : day.isToday ? 'border-[#FF7A00]/30 bg-amber-50 text-gray-900' : 'border-gray-100 bg-gray-50 text-gray-700 hover:border-[#FF7A00]/30 hover:bg-amber-50/60',
                            'relative group'
                        ]"
                        class="aspect-square rounded-2xl border p-2 text-left transition"
                        @click="selectDate(day.date)"
                    >
                        <div class="flex h-full flex-col justify-between">
                            <span class="text-sm font-black">{{ day.day }}</span>
                            <span
                                v-if="day.count"
                                :class="day.isSelected ? 'bg-white/20 text-white' : 'bg-white text-[#FF7A00]'"
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

                        <!-- Tooltip interactivo -->
                        <div
                            class="absolute z-50 bottom-full mb-3 w-64 bg-slate-900/95 backdrop-blur-md border border-slate-800 text-white rounded-2xl p-3 shadow-2xl opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-200 transform translate-y-1 group-hover:translate-y-0"
                            :class="{
                                'left-0 translate-x-0': day.column <= 1,
                                'right-0 left-auto translate-x-0': day.column >= 5,
                                'left-1/2 -translate-x-1/2': day.column > 1 && day.column < 5
                            }"
                        >
                            <!-- Caso con citas -->
                            <div v-if="day.count && appointmentsByDate[day.date]">
                                <div class="text-xs font-black uppercase tracking-widest text-[#FF7A00] mb-2 flex items-center justify-between">
                                    <span>Agendados</span>
                                    <span class="bg-white/10 text-white rounded-full px-1.5 py-0.5 text-[10px]">{{ day.count }}</span>
                                </div>
                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                    <div
                                        v-for="app in appointmentsByDate[day.date].slice(0, 4)"
                                        :key="app.id"
                                        class="border-t border-slate-800/60 pt-2 first:border-0 first:pt-0"
                                    >
                                        <div class="flex items-start justify-between gap-1">
                                            <span class="font-black text-[#FF7A00] text-[11px] shrink-0 mt-0.5">{{ app.time }}</span>
                                            <span class="font-bold text-slate-100 text-[11px] truncate flex-1 text-left">{{ app.client?.name || 'Sin registrar' }}</span>
                                        </div>
                                        <span class="block text-[10px] text-slate-400 text-left truncate mt-0.5 pl-9">
                                            {{ app.plate }} <span v-if="app.vehicle?.brand">· {{ app.vehicle.brand }} {{ app.vehicle.model }}</span>
                                        </span>
                                    </div>
                                    <div v-if="day.count > 4" class="text-[10px] text-slate-400 font-bold text-center pt-1 border-t border-slate-800/60">
                                        + {{ day.count - 4 }} más...
                                    </div>
                                </div>
                            </div>

                            <!-- Caso sin citas -->
                            <div v-else class="text-center py-1">
                                <p class="text-xs font-bold text-slate-300">Sin citas programadas</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">Jornada libre</p>
                            </div>

                            <!-- Flecha indicadora -->
                            <div
                                class="absolute top-full w-3 h-3 bg-slate-900/95 border-b border-r border-slate-800 rotate-45 -mt-1.5"
                                :class="{
                                    'left-6 -translate-x-0': day.column <= 1,
                                    'right-6 left-auto -translate-x-0': day.column >= 5,
                                    'left-1/2 -translate-x-1/2': day.column > 1 && day.column < 5
                                }"
                            ></div>
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
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-lg font-black tabular-nums text-gray-900">{{ appointment.time }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-gray-400">
                                    {{ statusLabel(appointment.status) }}
                                </p>
                            </div>
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
