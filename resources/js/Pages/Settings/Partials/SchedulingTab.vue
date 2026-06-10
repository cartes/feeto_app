<script setup>
import { ref, computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

const props = defineProps({
    tenant: Object,
});

// ── HORARIOS ──────────────────────────────────────────────
const DAY_LABELS = {
    monday: 'Lunes',
    tuesday: 'Martes',
    wednesday: 'Miércoles',
    thursday: 'Jueves',
    friday: 'Viernes',
    saturday: 'Sábado',
    sunday: 'Domingo',
};
const DAY_KEYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const SLOT_DURATIONS = [
    { value: 15, label: '15 minutos' },
    { value: 30, label: '30 minutos' },
    { value: 45, label: '45 minutos' },
    { value: 60, label: '1 hora' },
    { value: 90, label: '1 hora 30 min' },
    { value: 120, label: '2 horas' },
];

const defaultSchedulingConfig = () => ({
    slot_duration: 60,
    days: {
        monday:    { enabled: true,  open: '09:00', close: '18:00' },
        tuesday:   { enabled: true,  open: '09:00', close: '18:00' },
        wednesday: { enabled: true,  open: '09:00', close: '18:00' },
        thursday:  { enabled: true,  open: '09:00', close: '18:00' },
        friday:    { enabled: true,  open: '09:00', close: '18:00' },
        saturday:  { enabled: false, open: '09:00', close: '14:00' },
        sunday:    { enabled: false, open: '09:00', close: '14:00' },
    },
    blocked_slots: [],
});

const rawConfig = props.tenant?.scheduling_config ?? defaultSchedulingConfig();

const schedulingForm = useForm({
    slot_duration: rawConfig.slot_duration ?? 60,
    days: { ...rawConfig.days },
    blocked_slots: (rawConfig.blocked_slots ?? []).map(s => ({ ...s })),
    blocked_dates: (rawConfig.blocked_dates ?? []).map(d => ({ ...d })),
});

const todayStr = computed(() => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

const DAYS_ES = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
const MONTHS_LONG_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const formatBlockedDate = (dateStr) => {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return `${DAYS_ES[dt.getDay()]} ${d} de ${MONTHS_LONG_ES[m - 1]} ${y}`;
};

const futureBlockedSlots = computed(() =>
    schedulingForm.blocked_slots
        .filter(s => s.date && s.date >= todayStr.value)
        .slice()
        .sort((a, b) => `${a.date}${a.start}`.localeCompare(`${b.date}${b.start}`))
);

const showAddSlotForm = ref(false);
const newSlot = reactive({ date: '', start: '09:00', end: '10:00', reason: '' });

const saveNewSlot = () => {
    if (!newSlot.date || !newSlot.start || !newSlot.end) return;
    schedulingForm.blocked_slots.push({
        id: `slot_${Date.now()}`,
        date: newSlot.date,
        start: newSlot.start,
        end: newSlot.end,
        reason: newSlot.reason,
    });
    Object.assign(newSlot, { date: '', start: '09:00', end: '10:00', reason: '' });
    showAddSlotForm.value = false;
};

const removeBlockedSlot = (id) => {
    const idx = schedulingForm.blocked_slots.findIndex(s => s.id === id);
    if (idx !== -1) schedulingForm.blocked_slots.splice(idx, 1);
};

const submitScheduling = () => {
    schedulingForm.patch(route('taller.settings.scheduling.update', tenantRouteParams.value), {
        preserveScroll: true,
    });
};

// ── FERIADOS ──────────────────────────────────────────────
const MONTHS_ES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

const formatDateLabel = (dateStr) => {
    const [y, m, d] = dateStr.split('-');
    return `${parseInt(d)} ${MONTHS_ES[parseInt(m) - 1]} ${y}`;
};

const holidayYear = ref(new Date().getFullYear());
const loadedHolidays = ref([]);
const loadingHolidays = ref(false);
const holidayError = ref('');
const showCustomDateForm = ref(false);
const customDate = ref('');
const customDateLabel = ref('');

const blockedDateSet = computed(() => new Set(schedulingForm.blocked_dates.map(d => d.date)));

const loadHolidays = async () => {
    loadingHolidays.value = true;
    holidayError.value = '';
    loadedHolidays.value = [];

    try {
        const url = route('taller.settings.scheduling.feriados', {
            ...tenantRouteParams.value,
            year: holidayYear.value,
        });
        const res = await fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error('Error al obtener feriados.');
        const data = await res.json();
        if (!data.length) {
            holidayError.value = 'No se encontraron feriados para ese año.';
            return;
        }
        loadedHolidays.value = data.map(h => ({
            ...h,
            selected: !blockedDateSet.value.has(h.date),
        }));
    } catch {
        holidayError.value = 'No se pudo conectar con el servicio de feriados. Intenta de nuevo.';
    } finally {
        loadingHolidays.value = false;
    }
};

const addSelectedHolidays = () => {
    const toAdd = loadedHolidays.value.filter(h => h.selected && !blockedDateSet.value.has(h.date));
    schedulingForm.blocked_dates.push(...toAdd.map(h => ({
        date: h.date,
        label: h.label,
        type: 'official',
    })));
    schedulingForm.blocked_dates.sort((a, b) => a.date.localeCompare(b.date));
    loadedHolidays.value = [];
};

const addCustomDate = () => {
    if (!customDate.value || !customDateLabel.value.trim()) return;
    if (blockedDateSet.value.has(customDate.value)) return;
    schedulingForm.blocked_dates.push({
        date: customDate.value,
        label: customDateLabel.value.trim(),
        type: 'custom',
    });
    schedulingForm.blocked_dates.sort((a, b) => a.date.localeCompare(b.date));
    customDate.value = '';
    customDateLabel.value = '';
    showCustomDateForm.value = false;
};

const removeBlockedDate = (index) => {
    schedulingForm.blocked_dates.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-300">

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)]">

            <!-- Formulario principal -->
            <form @submit.prevent="submitScheduling" class="space-y-6">

                <!-- Horario por día -->
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                    <div>
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Días de atención</p>
                        <p class="text-xs text-gray-400 mt-1 font-medium">Define qué días y en qué horario recibe el taller.</p>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="day in DAY_KEYS"
                            :key="day"
                            class="flex items-center gap-4 rounded-2xl border px-4 py-3 transition-colors"
                            :class="schedulingForm.days[day].enabled ? 'border-[#FF7A00]/20 bg-orange-50/40' : 'border-gray-100 bg-gray-50/50'"
                        >
                            <!-- Toggle -->
                            <button
                                type="button"
                                @click="schedulingForm.days[day].enabled = !schedulingForm.days[day].enabled"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200"
                                :class="schedulingForm.days[day].enabled ? 'bg-[#FF7A00]' : 'bg-gray-200'"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                    :class="schedulingForm.days[day].enabled ? 'translate-x-6' : 'translate-x-1'"
                                />
                            </button>

                            <!-- Nombre del día -->
                            <span class="w-24 text-sm font-black" :class="schedulingForm.days[day].enabled ? 'text-gray-800' : 'text-gray-400'">
                                {{ DAY_LABELS[day] }}
                            </span>

                            <!-- Horario -->
                            <template v-if="schedulingForm.days[day].enabled">
                                <div class="flex items-center gap-2 flex-1">
                                    <input
                                        v-model="schedulingForm.days[day].open"
                                        type="time"
                                        class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] w-32"
                                    />
                                    <span class="text-xs text-gray-400 font-bold">hasta</span>
                                    <input
                                        v-model="schedulingForm.days[day].close"
                                        type="time"
                                        class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] w-32"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                <span class="text-xs text-gray-400 font-semibold italic">Cerrado</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Duración de turnos -->
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Duración de turnos</p>
                        <p class="text-xs text-gray-400 mt-1 font-medium">Intervalo mínimo entre citas agendadas.</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button
                            v-for="opt in SLOT_DURATIONS"
                            :key="opt.value"
                            type="button"
                            @click="schedulingForm.slot_duration = opt.value"
                            class="py-3 rounded-2xl text-sm font-bold border transition-all"
                            :class="schedulingForm.slot_duration === opt.value
                                ? 'bg-[#FF7A00] text-white border-[#FF7A00] shadow-sm'
                                : 'bg-gray-50 text-gray-500 border-gray-200 hover:border-[#FF7A00]/40 hover:text-gray-700'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Horarios bloqueados por fecha específica -->
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-black uppercase tracking-widest text-gray-500">Horarios bloqueados</p>
                            <p class="text-xs text-gray-400 mt-1 font-medium">Fechas y franjas horarias específicas en que NO se reciben citas.</p>
                        </div>
                        <button
                            type="button"
                            @click="showAddSlotForm = !showAddSlotForm"
                            class="flex items-center gap-1.5 px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-colors shrink-0"
                        >
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showAddSlotForm ? 'rotate-45' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>

                    <!-- Formulario agregar nuevo bloque -->
                    <div v-if="showAddSlotForm" class="rounded-2xl border border-orange-200 bg-orange-50/50 p-4 space-y-3 animate-in slide-in-from-top-1 duration-200">
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Nuevo horario bloqueado</p>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fecha</label>
                                <input
                                    v-model="newSlot.date"
                                    type="date"
                                    :min="todayStr"
                                    class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Desde</label>
                                <input
                                    v-model="newSlot.start"
                                    type="time"
                                    step="60"
                                    class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] w-28"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Hasta</label>
                                <input
                                    v-model="newSlot.end"
                                    type="time"
                                    step="60"
                                    class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] w-28"
                                />
                            </div>
                            <div class="flex-1 space-y-1 min-w-[150px]">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Motivo (opcional)</label>
                                <input
                                    v-model="newSlot.reason"
                                    type="text"
                                    placeholder="Ej: Elecciones, reunión, visita técnica..."
                                    maxlength="100"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                />
                            </div>
                            <div class="flex gap-2 pb-0.5">
                                <button type="button" @click="showAddSlotForm = false"
                                    class="px-3 py-2 bg-white border border-gray-200 text-gray-500 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="saveNewSlot"
                                    :disabled="!newSlot.date || !newSlot.start || !newSlot.end"
                                    class="px-4 py-2 bg-[#FF7A00] text-white rounded-xl text-xs font-black hover:bg-[#CC6200] transition-colors disabled:opacity-40"
                                >
                                    Guardar bloque
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista vacía -->
                    <div v-if="futureBlockedSlots.length === 0 && !showAddSlotForm" class="rounded-2xl border border-dashed border-gray-200 py-8 text-center">
                        <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400 font-medium">No hay horarios bloqueados próximos.</p>
                        <p class="text-xs text-gray-300 mt-1">Agrega fechas específicas en que no recibirás citas.</p>
                    </div>

                    <!-- Lista de horarios bloqueados (hoy en adelante, ordenados) -->
                    <div v-if="futureBlockedSlots.length > 0" class="space-y-2">
                        <div
                            v-for="slot in futureBlockedSlots"
                            :key="slot.id"
                            class="flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50/30 px-4 py-3"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-gray-800">{{ formatBlockedDate(slot.date) }}</p>
                                <p class="text-xs text-red-500 font-bold mt-0.5">
                                    {{ slot.start }} — {{ slot.end }}
                                    <span v-if="slot.reason" class="text-gray-400 font-normal"> · {{ slot.reason }}</span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="removeBlockedSlot(slot.id)"
                                class="p-2 rounded-xl text-gray-300 hover:text-red-500 hover:bg-red-100 transition-colors shrink-0"
                                title="Eliminar bloque"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Nota de pasados ocultos -->
                    <p v-if="schedulingForm.blocked_slots.filter(s => s.date && s.date < todayStr).length > 0"
                        class="text-[10px] text-gray-400 font-medium text-center pt-1">
                        {{ schedulingForm.blocked_slots.filter(s => s.date && s.date < todayStr).length }} bloque(s) pasado(s) oculto(s). Se conservan al guardar.
                    </p>
                </div>

                <!-- Feriados y fechas bloqueadas -->
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                    <div>
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Feriados y fechas bloqueadas</p>
                        <p class="text-xs text-gray-400 mt-1 font-medium">Días en que el taller no recibe citas (feriados oficiales, elecciones, vacaciones, etc.).</p>
                    </div>

                    <!-- Importar feriados de Chile -->
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-black uppercase tracking-widest text-blue-600">Feriados oficiales de Chile</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex rounded-xl overflow-hidden border border-blue-200 bg-white">
                                <button
                                    v-for="y in [new Date().getFullYear(), new Date().getFullYear() + 1]"
                                    :key="y"
                                    type="button"
                                    @click="holidayYear = y; loadedHolidays = []"
                                    class="px-4 py-2 text-xs font-black transition-colors"
                                    :class="holidayYear === y ? 'bg-blue-500 text-white' : 'text-blue-400 hover:text-blue-600'"
                                >
                                    {{ y }}
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="loadHolidays"
                                :disabled="loadingHolidays"
                                class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-xl text-xs font-black hover:bg-blue-600 transition-colors disabled:opacity-50"
                            >
                                <svg v-if="loadingHolidays" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                {{ loadingHolidays ? 'Cargando...' : `Cargar feriados ${holidayYear}` }}
                            </button>
                        </div>

                        <p v-if="holidayError" class="text-red-500 text-xs font-semibold">{{ holidayError }}</p>

                        <!-- Lista de feriados cargados -->
                        <div v-if="loadedHolidays.length > 0" class="space-y-3">
                            <div class="max-h-56 overflow-y-auto space-y-1.5 pr-1">
                                <label
                                    v-for="(h, i) in loadedHolidays"
                                    :key="h.date"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 cursor-pointer transition-colors"
                                    :class="h.selected ? 'bg-blue-100/60' : 'bg-white/60 hover:bg-blue-50/40'"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="loadedHolidays[i].selected"
                                        :disabled="blockedDateSet.has(h.date)"
                                        class="w-4 h-4 rounded text-blue-500 focus:ring-blue-400 accent-blue-500"
                                    />
                                    <span class="flex-1 text-xs font-semibold text-gray-700">{{ h.label }}</span>
                                    <span class="text-[10px] font-mono text-gray-400">{{ formatDateLabel(h.date) }}</span>
                                    <span v-if="h.irrenunciable" class="text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full">Irrenunciable</span>
                                    <span v-if="blockedDateSet.has(h.date)" class="text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded-full">Ya agregado</span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-xs text-gray-400 font-medium">
                                    {{ loadedHolidays.filter(h => h.selected).length }} seleccionados
                                </span>
                                <div class="flex gap-2">
                                    <button type="button" @click="loadedHolidays = []"
                                        class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors">
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        @click="addSelectedHolidays"
                                        :disabled="!loadedHolidays.some(h => h.selected && !blockedDateSet.has(h.date))"
                                        class="px-4 py-1.5 bg-blue-500 text-white rounded-xl text-xs font-black hover:bg-blue-600 transition-colors disabled:opacity-40"
                                    >
                                        Agregar seleccionados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de fechas bloqueadas guardadas -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                                Fechas bloqueadas ({{ schedulingForm.blocked_dates.length }})
                            </p>
                            <button
                                type="button"
                                @click="showCustomDateForm = !showCustomDateForm"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 rounded-xl text-xs font-black hover:bg-gray-200 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Fecha personalizada
                            </button>
                        </div>

                        <!-- Formulario fecha personalizada -->
                        <div v-if="showCustomDateForm" class="flex flex-wrap items-end gap-3 rounded-2xl bg-gray-50 border border-gray-200 px-4 py-3 animate-in slide-in-from-top-1 duration-200">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fecha</label>
                                <input
                                    v-model="customDate"
                                    type="date"
                                    class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                />
                            </div>
                            <div class="flex-1 space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Descripción</label>
                                <input
                                    v-model="customDateLabel"
                                    type="text"
                                    placeholder="Ej: Elecciones, Vacaciones taller..."
                                    maxlength="120"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                />
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="showCustomDateForm = false; customDate = ''; customDateLabel = ''"
                                    class="px-3 py-2 bg-gray-100 text-gray-500 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="addCustomDate"
                                    :disabled="!customDate || !customDateLabel.trim()"
                                    class="px-4 py-2 bg-[#FF7A00] text-white rounded-xl text-xs font-black hover:bg-[#CC6200] transition-colors disabled:opacity-40"
                                >
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <!-- Lista vacía -->
                        <div v-if="schedulingForm.blocked_dates.length === 0" class="rounded-2xl border border-dashed border-gray-200 py-6 text-center">
                            <p class="text-sm text-gray-400 font-medium">No hay fechas bloqueadas.</p>
                            <p class="text-xs text-gray-300 mt-1">Importa feriados oficiales o agrega fechas personalizadas.</p>
                        </div>

                        <!-- Fechas bloqueadas -->
                        <div v-else class="space-y-1.5">
                            <div
                                v-for="(d, index) in schedulingForm.blocked_dates"
                                :key="d.date"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 border"
                                :class="d.type === 'official' ? 'border-blue-100 bg-blue-50/40' : 'border-purple-100 bg-purple-50/40'"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ d.label }}</p>
                                    <p class="text-[11px] text-gray-400 font-mono">{{ formatDateLabel(d.date) }}</p>
                                </div>
                                <span
                                    class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full shrink-0"
                                    :class="d.type === 'official' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'"
                                >
                                    {{ d.type === 'official' ? 'Oficial' : 'Personalizado' }}
                                </span>
                                <button
                                    type="button"
                                    @click="removeBlockedDate(index)"
                                    class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="schedulingForm.processing"
                        class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50"
                    >
                        {{ schedulingForm.processing ? 'Guardando...' : 'Guardar Horarios' }}
                    </button>
                </div>
            </form>

            <!-- Panel derecho: resumen -->
            <div class="space-y-4">
                <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-4 sticky top-6">
                    <p class="text-sm font-black uppercase tracking-widest text-gray-500">Resumen</p>

                    <div class="space-y-2">
                        <div
                            v-for="day in DAY_KEYS"
                            :key="day"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="font-semibold" :class="schedulingForm.days[day].enabled ? 'text-gray-800' : 'text-gray-300'">
                                {{ DAY_LABELS[day] }}
                            </span>
                            <span v-if="schedulingForm.days[day].enabled" class="text-gray-600 font-medium text-xs">
                                {{ schedulingForm.days[day].open }} – {{ schedulingForm.days[day].close }}
                            </span>
                            <span v-else class="text-gray-300 text-xs font-semibold">Cerrado</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Turnos</p>
                        <p class="text-sm font-bold text-gray-700">
                            {{ SLOT_DURATIONS.find(s => s.value === schedulingForm.slot_duration)?.label ?? '–' }}
                        </p>
                    </div>

                    <div v-if="futureBlockedSlots.length > 0" class="border-t border-gray-100 pt-3 space-y-1.5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-red-400 mb-2">
                            Bloqueados próximos ({{ futureBlockedSlots.length }})
                        </p>
                        <div
                            v-for="slot in futureBlockedSlots.slice(0, 4)"
                            :key="slot.id"
                            class="text-xs text-gray-500"
                        >
                            <span class="font-bold text-gray-700 block">{{ formatDateLabel(slot.date) }}</span>
                            <span class="text-red-500 font-medium">{{ slot.start }} – {{ slot.end }}</span>
                            <span v-if="slot.reason" class="text-gray-400"> · {{ slot.reason }}</span>
                        </div>
                        <p v-if="futureBlockedSlots.length > 4" class="text-[10px] text-gray-400">
                            y {{ futureBlockedSlots.length - 4 }} más...
                        </p>
                    </div>

                    <div v-if="schedulingForm.blocked_dates.length > 0" class="border-t border-gray-100 pt-3 space-y-1">
                        <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-2">
                            Fechas bloqueadas ({{ schedulingForm.blocked_dates.length }})
                        </p>
                        <div
                            v-for="d in schedulingForm.blocked_dates.slice(0, 5)"
                            :key="d.date"
                            class="text-xs text-gray-500 font-medium flex items-center gap-1.5"
                        >
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="d.type === 'official' ? 'bg-blue-400' : 'bg-purple-400'"></span>
                            <span>{{ formatDateLabel(d.date) }}</span>
                            <span class="text-gray-400 truncate">· {{ d.label }}</span>
                        </div>
                        <p v-if="schedulingForm.blocked_dates.length > 5" class="text-[10px] text-gray-400 font-medium">
                            y {{ schedulingForm.blocked_dates.length - 5 }} más...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
