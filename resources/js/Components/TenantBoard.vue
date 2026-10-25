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

const elapsed = (iso) => {
    const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);

    if (days < 1) {
        return 'Hoy';
    }

    return days === 1 ? '1 día' : `${days} días`;
};
</script>

<template>
    <div class="rounded-[2rem] border-[10px] border-amber-200 bg-white p-5 text-slate-800 shadow-xl dark:border-amber-600/90 dark:bg-black dark:text-slate-100 dark:ring-1 dark:ring-amber-500/30 dark:shadow-[0_25px_60px_rgba(0,0,0,0.9)] sm:p-7"
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
                    <li v-for="vehicle in workshop.vehicles" :key="vehicle.id">
                        <Link :href="vehicle.url"
                            class="flex h-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-400 dark:border-[#1e293b] dark:bg-[#0c1322] dark:hover:border-slate-500 dark:hover:bg-[#111a2e]">
                            <div class="min-w-0">
                                <p class="font-mono text-base font-black tracking-widest text-slate-900 dark:text-white">{{ vehicle.plate }}</p>
                                <p class="truncate text-xs font-medium text-slate-600 dark:text-slate-300">{{ vehicle.vehicle || 'Vehículo sin datos' }}</p>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Ingresó: <span class="font-bold text-slate-700 dark:text-slate-200">{{ elapsed(vehicle.since) }}</span></p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm"
                                :class="stageClass(vehicle.status)">
                                {{ vehicle.status_label }}
                            </span>
                        </Link>
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
