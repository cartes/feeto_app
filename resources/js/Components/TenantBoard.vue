<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const props = defineProps({
    board: { type: Object, default: null },
    appointments: { type: Array, default: () => [] },
    canManageAppointments: { type: Boolean, default: false },
});

const { tenantRouteParams } = useTenantRouting();

const workshop = computed(() => props.board?.workshop ?? null);
const lowStock = computed(() => props.board?.low_stock ?? null);
const STALE_DAYS = 3;
const vehicles = computed(() => [...(workshop.value?.vehicles ?? [])]
    .sort((a, b) => Number(b.needs_notice) - Number(a.needs_notice)));
const agenda = computed(() => props.appointments
    .filter((appointment) => appointment.status !== 'cancelled')
    .sort((a, b) => a.time.localeCompare(b.time)));

const STAGE_CONFIG = {
    recepcion: {
        badge: 'bg-sky-100 text-sky-900 border border-sky-300 dark:bg-[#071324] dark:text-sky-300 dark:border-sky-500/80',
        count: 'bg-sky-200/90 text-sky-950 dark:bg-sky-900/80 dark:text-sky-100',
    },
    diagnostico: {
        badge: 'bg-violet-100 text-violet-900 border border-violet-300 dark:bg-[#150a29] dark:text-violet-300 dark:border-violet-500/80',
        count: 'bg-violet-200/90 text-violet-950 dark:bg-violet-900/80 dark:text-violet-100',
    },
    esperando_repuestos: {
        badge: 'bg-amber-100 text-amber-950 border border-amber-300 dark:bg-[#1c0f05] dark:text-amber-300 dark:border-amber-500/80',
        count: 'bg-amber-200/90 text-amber-950 dark:bg-amber-900/80 dark:text-amber-100',
    },
    control_calidad: {
        badge: 'bg-cyan-100 text-cyan-900 border border-cyan-300 dark:bg-[#04151c] dark:text-cyan-300 dark:border-cyan-500/80',
        count: 'bg-cyan-200/90 text-cyan-950 dark:bg-cyan-900/80 dark:text-cyan-100',
    },
    listo: {
        badge: 'bg-emerald-100 text-emerald-900 border border-emerald-300 dark:bg-[#051a14] dark:text-emerald-300 dark:border-emerald-500/80',
        count: 'bg-emerald-200/90 text-emerald-950 dark:bg-emerald-900/80 dark:text-emerald-100',
    },
};

const DEFAULT_STAGE = {
    badge: 'bg-orange-100 text-orange-950 border border-orange-300 dark:bg-[#1a0c00] dark:text-orange-300 dark:border-orange-500/80',
    count: 'bg-orange-200/90 text-orange-950 dark:bg-orange-900/80 dark:text-orange-100',
};

const stageClass = (status) => (STAGE_CONFIG[status] ?? DEFAULT_STAGE).badge;
const stageCountClass = (status) => (STAGE_CONFIG[status] ?? DEFAULT_STAGE).count;

const appointmentStatus = (status) => ({
    pending: {
        label: 'Pendiente',
        class: 'bg-amber-100 text-amber-950 border border-amber-300 dark:bg-[#1c0f05] dark:text-amber-300 dark:border-amber-500/80',
    },
    arrived: {
        label: 'Llegó',
        class: 'bg-emerald-100 text-emerald-950 border border-emerald-300 dark:bg-[#051a14] dark:text-emerald-300 dark:border-emerald-500/80',
    },
}[status] ?? {
    label: status,
    class: 'bg-slate-100 text-slate-800 border border-slate-300 dark:bg-[#0c1322] dark:text-slate-200 dark:border-slate-700',
});

const daysSince = (iso) => Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);
const isStale = (vehicle) => !vehicle.needs_notice && daysSince(vehicle.since) >= STALE_DAYS;

const elapsed = (iso) => {
    const days = daysSince(iso);

    if (days < 1) {
        return 'Hoy';
    }

    return days === 1 ? '1 día' : `${days} días`;
};
</script>

<template>
    <div class="rounded-[1.5rem] border-[6px] border-amber-200 bg-white p-4 text-slate-800 shadow-xl dark:border-amber-600/90 dark:bg-black dark:text-slate-100 dark:ring-1 dark:ring-amber-500/30 dark:shadow-[0_25px_60px_rgba(0,0,0,0.9)] sm:rounded-[2rem] sm:border-[10px] sm:p-7"
        data-tour="dashboard-board" role="region" aria-label="Pizarrón del taller">
        <div v-if="!board" class="animate-pulse space-y-4" aria-busy="true" aria-label="Cargando pizarrón">
            <div class="h-6 w-1/3 rounded-full bg-slate-200 dark:bg-[#1e293b]"></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="n in 3" :key="n" class="h-20 rounded-2xl bg-slate-100 dark:bg-[#0c1322]"></div>
            </div>
            <div class="grid gap-3 xl:grid-cols-2">
                <div class="h-32 rounded-2xl bg-slate-100 dark:bg-[#0c1322]"></div>
                <div class="h-32 rounded-2xl bg-slate-100 dark:bg-[#0c1322]"></div>
            </div>
        </div>

        <div v-else class="grid gap-6 xl:grid-cols-2">
            <section v-if="workshop" class="space-y-4 xl:col-span-2" data-tour="dashboard-board-workshop">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">En taller</p>
                        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                            {{ workshop.total }} vehículo{{ workshop.total !== 1 ? 's' : '' }} en el taller
                        </h2>
                    </div>
                    <Link :href="route('work-orders.index', tenantRouteParams)"
                        class="rounded-full border border-slate-300 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-[#0c1322] dark:text-slate-200 dark:hover:border-slate-500 dark:hover:bg-[#162036] dark:hover:text-white">
                        Ver tablero de órdenes
                    </Link>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span v-for="stage in workshop.stages" :key="stage.status"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black shadow-sm transition-all"
                        :class="stageClass(stage.status)">
                        {{ stage.label }}
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-black"
                            :class="stageCountClass(stage.status)">
                            {{ stage.count }}
                        </span>
                    </span>
                </div>

                <p v-if="workshop.vehicles.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-800 dark:bg-[#060911]/80 dark:text-slate-300">
                    No hay vehículos en el taller por ahora.
                </p>

                <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="vehicle in vehicles" :key="vehicle.id"
                        class="flex flex-col gap-3 rounded-2xl border p-4 transition dark:bg-[#0c1322]"
                        :class="vehicle.needs_notice
                            ? 'border-emerald-300 bg-emerald-50/70 dark:border-emerald-500/70'
                            : 'border-slate-200 bg-slate-50 dark:border-[#1e293b]'"
                        data-board-vehicle>
                        <Link :href="vehicle.url" class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-lg font-black tracking-widest text-slate-900 dark:text-white">{{ vehicle.plate }}</p>
                                <p class="truncate text-xs font-medium text-slate-600 dark:text-slate-300">{{ vehicle.vehicle || 'Vehículo sin datos' }}</p>
                                <p v-if="vehicle.client_name" class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ vehicle.client_name }}</p>
                                <p class="mt-1 text-[11px]" :class="isStale(vehicle) ? 'font-bold text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'">
                                    Ingresó: <span class="font-bold" :class="isStale(vehicle) ? '' : 'text-slate-700 dark:text-slate-200'">{{ elapsed(vehicle.since) }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm"
                                :class="stageClass(vehicle.status)">
                                {{ vehicle.status_label }}
                            </span>
                        </Link>

                        <div class="flex items-stretch gap-2" data-board-actions>
                            <template v-if="vehicle.whatsapp_url">
                                <a :href="vehicle.whatsapp_url" target="_blank" rel="noopener noreferrer"
                                    :aria-label="vehicle.needs_notice ? 'Avisar al cliente por WhatsApp' : 'Escribir al cliente por WhatsApp'"
                                    class="flex min-h-[2.75rem] flex-1 items-center justify-center gap-2 rounded-xl px-3 text-sm font-black transition"
                                    :class="vehicle.needs_notice
                                        ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                        : 'border border-emerald-300 bg-white text-emerald-800 hover:bg-emerald-50 dark:border-emerald-500/60 dark:bg-transparent dark:text-emerald-300'"
                                    data-board-whatsapp>
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.83L3 20l1.3-3.9A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    {{ vehicle.needs_notice ? 'Avisar' : 'Escribir' }}
                                </a>
                                <a :href="vehicle.phone_url" aria-label="Llamar al cliente"
                                    class="flex min-h-[2.75rem] w-12 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-transparent dark:text-slate-200"
                                    data-board-call>
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.5a1 1 0 01-.5 1.2l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.2-.5l4.5 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.72 21 3 14.28 3 6V5z" /></svg>
                                </a>
                            </template>
                            <p v-else class="flex min-h-[2.75rem] flex-1 items-center rounded-xl border border-dashed border-slate-300 px-3 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-400" data-board-no-phone>
                                Cliente sin teléfono registrado
                            </p>
                            <Link :href="vehicle.url" aria-label="Ver orden"
                                class="flex min-h-[2.75rem] w-12 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-transparent dark:text-slate-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </li>
                </ul>
            </section>

            <section v-if="canManageAppointments" class="space-y-4" data-tour="dashboard-board-agenda">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">Agenda de hoy</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ agenda.length }} cita{{ agenda.length !== 1 ? 's' : '' }} programada{{ agenda.length !== 1 ? 's' : '' }}</h2>
                    </div>
                    <Link :href="route('appointments.index', tenantRouteParams)"
                        class="text-xs font-black uppercase tracking-[0.2em] text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                        Ver agenda →
                    </Link>
                </div>

                <p v-if="agenda.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-800 dark:bg-[#060911]/80 dark:text-slate-300">
                    No hay citas para hoy.
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="appointment in agenda" :key="appointment.id"
                        class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-[#1e293b] dark:bg-[#0c1322]">
                        <span class="w-14 shrink-0 text-lg font-black tabular-nums text-orange-600 dark:text-orange-400">{{ appointment.time }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ appointment.client.name }}</p>
                            <p class="truncate text-xs text-slate-600 dark:text-slate-300">
                                <span class="font-mono font-bold tracking-widest text-slate-800 dark:text-slate-200">{{ appointment.plate }}</span>
                                <template v-if="appointment.notes"> · <span class="text-slate-500 dark:text-slate-400">{{ appointment.notes }}</span></template>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm"
                            :class="appointmentStatus(appointment.status).class">
                            {{ appointmentStatus(appointment.status).label }}
                        </span>
                    </li>
                </ul>
            </section>

            <section v-if="lowStock" class="space-y-4" data-tour="dashboard-board-stock">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">Inventario</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">
                            {{ lowStock.total === 0 ? 'Stock al día' : `${lowStock.total} producto${lowStock.total !== 1 ? 's' : ''} bajo stock` }}
                        </h2>
                    </div>
                    <Link :href="route('inventory.index', tenantRouteParams)"
                        class="text-xs font-black uppercase tracking-[0.2em] text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                        Ver inventario →
                    </Link>
                </div>

                <p v-if="lowStock.items.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-800 dark:bg-[#060911]/80 dark:text-slate-300">
                    Ningún producto está bajo su stock mínimo.
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="item in lowStock.items" :key="item.id">
                        <Link :href="item.url"
                            class="flex items-center justify-between gap-3 rounded-2xl border p-3 transition hover:border-slate-400 dark:hover:border-slate-500"
                            :class="item.depleted
                                ? 'border-red-300 bg-red-50/60 dark:border-red-500/70 dark:bg-red-950/40'
                                : 'border-slate-200 bg-slate-50 dark:border-[#1e293b] dark:bg-[#0c1322]'">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ item.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">SKU <span class="font-mono text-slate-700 dark:text-slate-300">{{ item.sku }}</span></p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-black" :class="item.depleted ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'">
                                    {{ item.depleted ? 'Sin stock' : `${item.physical_stock} u.` }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Mínimo <span class="font-semibold text-slate-700 dark:text-slate-300">{{ item.min_stock }}</span></p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
