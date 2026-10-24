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

const stageClass = (status) => ({
    recepcion: 'bg-sky-300/90 text-slate-900',
    diagnostico: 'bg-violet-300/90 text-slate-900',
    esperando_repuestos: 'bg-amber-300/90 text-slate-900',
    control_calidad: 'bg-cyan-300/90 text-slate-900',
    listo: 'bg-emerald-300/90 text-slate-900',
}[status] ?? 'bg-orange-300/90 text-slate-900');

const appointmentStatus = (status) => ({
    pending: { label: 'Pendiente', class: 'bg-amber-300/90 text-slate-900' },
    arrived: { label: 'Llegó', class: 'bg-emerald-300/90 text-slate-900' },
}[status] ?? { label: status, class: 'bg-slate-300/90 text-slate-900' });

const elapsed = (iso) => {
    const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);

    if (days < 1) {
        return 'Hoy';
    }

    return days === 1 ? '1 día' : `${days} días`;
};
</script>

<template>
    <div class="rounded-[2rem] border-[10px] border-amber-200 bg-white p-5 text-slate-800 shadow-xl dark:border-amber-900/80 dark:bg-slate-800 dark:text-slate-100 sm:p-7"
        data-tour="dashboard-board" role="region" aria-label="Pizarrón del taller">
        <div v-if="!board" class="animate-pulse space-y-4" aria-busy="true" aria-label="Cargando pizarrón">
            <div class="h-6 w-1/3 rounded-full bg-slate-200 dark:bg-slate-600"></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="n in 3" :key="n" class="h-20 rounded-2xl bg-slate-100 dark:bg-slate-700/60"></div>
            </div>
            <div class="grid gap-3 xl:grid-cols-2">
                <div class="h-32 rounded-2xl bg-slate-100 dark:bg-slate-700/60"></div>
                <div class="h-32 rounded-2xl bg-slate-100 dark:bg-slate-700/60"></div>
            </div>
        </div>

        <div v-else class="grid gap-6 xl:grid-cols-2">
            <section v-if="workshop" class="space-y-4 xl:col-span-2" data-tour="dashboard-board-workshop">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">En taller</p>
                        <h2 class="mt-1 text-2xl font-black tracking-tight">
                            {{ workshop.total }} vehículo{{ workshop.total !== 1 ? 's' : '' }} en el taller
                        </h2>
                    </div>
                    <Link :href="route('work-orders.index', tenantRouteParams)"
                        class="rounded-full border border-slate-300 dark:border-slate-500 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700">
                        Ver tablero de órdenes
                    </Link>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span v-for="stage in workshop.stages" :key="stage.status"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black"
                        :class="stageClass(stage.status)">
                        {{ stage.label }}
                        <span class="rounded-full bg-slate-900/20 px-2 py-0.5">{{ stage.count }}</span>
                    </span>
                </div>

                <p v-if="workshop.vehicles.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-500 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                    No hay vehículos en el taller por ahora.
                </p>

                <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="vehicle in workshop.vehicles" :key="vehicle.id">
                        <Link :href="vehicle.url"
                            class="flex h-full items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/60 p-4 transition hover:border-slate-400">
                            <div class="min-w-0">
                                <p class="font-mono text-base font-black tracking-widest">{{ vehicle.plate }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ vehicle.vehicle || 'Vehículo sin datos' }}</p>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Ingresó: {{ elapsed(vehicle.since) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide"
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
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Agenda de hoy</p>
                        <h2 class="mt-1 text-xl font-black">{{ agenda.length }} cita{{ agenda.length !== 1 ? 's' : '' }} programada{{ agenda.length !== 1 ? 's' : '' }}</h2>
                    </div>
                    <Link :href="route('appointments.index', tenantRouteParams)"
                        class="text-xs font-black uppercase tracking-[0.2em] text-orange-600 hover:text-orange-700 dark:text-orange-300 dark:hover:text-orange-200">
                        Ver agenda →
                    </Link>
                </div>

                <p v-if="agenda.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-500 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                    No hay citas para hoy.
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="appointment in agenda" :key="appointment.id"
                        class="flex items-center gap-4 rounded-2xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/60 p-3">
                        <span class="w-14 shrink-0 text-lg font-black tabular-nums text-orange-600 dark:text-orange-300">{{ appointment.time }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ appointment.client.name }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-mono tracking-widest">{{ appointment.plate }}</span>
                                <template v-if="appointment.notes"> · {{ appointment.notes }}</template>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide"
                            :class="appointmentStatus(appointment.status).class">
                            {{ appointmentStatus(appointment.status).label }}
                        </span>
                    </li>
                </ul>
            </section>

            <section v-if="lowStock" class="space-y-4" data-tour="dashboard-board-stock">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Inventario</p>
                        <h2 class="mt-1 text-xl font-black">
                            {{ lowStock.total === 0 ? 'Stock al día' : `${lowStock.total} producto${lowStock.total !== 1 ? 's' : ''} bajo stock` }}
                        </h2>
                    </div>
                    <Link :href="route('inventory.index', tenantRouteParams)"
                        class="text-xs font-black uppercase tracking-[0.2em] text-orange-600 hover:text-orange-700 dark:text-orange-300 dark:hover:text-orange-200">
                        Ver inventario →
                    </Link>
                </div>

                <p v-if="lowStock.items.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-500 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                    Ningún producto está bajo su stock mínimo.
                </p>

                <ul v-else class="space-y-2">
                    <li v-for="item in lowStock.items" :key="item.id">
                        <Link :href="item.url"
                            class="flex items-center justify-between gap-3 rounded-2xl border bg-slate-50 dark:bg-slate-700/60 p-3 transition hover:border-slate-400"
                            :class="item.depleted ? 'border-red-300 dark:border-red-400/70' : 'border-slate-200 dark:border-slate-600'">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">{{ item.name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">SKU {{ item.sku }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-black" :class="item.depleted ? 'text-red-600 dark:text-red-300' : 'text-amber-600 dark:text-amber-300'">
                                    {{ item.depleted ? 'Sin stock' : `${item.physical_stock} u.` }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Mínimo {{ item.min_stock }}</p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
