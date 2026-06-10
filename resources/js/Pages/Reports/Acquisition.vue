<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PrintableReportShell from '@/Components/PrintableReportShell.vue';
import ReportsNavigation from '@/Components/ReportsNavigation.vue';
import ReportExportActions from '@/Components/ReportExportActions.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';

const { tenantRouteParams } = useTenantRouting();
const { formatDateTime } = useFormatting();

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    rangeOptions: {
        type: Array,
        required: true,
    },
    summary: {
        type: Object,
        required: true,
    },
    funnel: {
        type: Array,
        required: true,
    },
    dailySeries: {
        type: Array,
        required: true,
    },
    recentActivity: {
        type: Array,
        required: true,
    },
});

const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`;
</script>

<template>
    <Head title="Reporte de Adquisición" />

    <TallerLayout>
        <PrintableReportShell>
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Marketing</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reporte de Adquisición</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">
                        Funnel nativo del landing: visitas, intentos de contacto y citas del taller.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('reports.index', tenantRouteParams)"
                        class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Volver al centro de reportes
                    </Link>
                    <ReportExportActions report="acquisition" />
                </div>
            </div>

            <ReportsNavigation />

            <div class="flex flex-wrap gap-3">
                <Link
                    v-for="option in rangeOptions"
                    :key="option.value"
                    :href="route('reports.acquisition', { ...tenantRouteParams, range: option.value })"
                    class="inline-flex items-center rounded-full border px-4 py-2 text-xs font-black uppercase tracking-widest transition-colors"
                    :class="filters.range === option.value ? 'border-[#FF7A00] bg-[#FF7A00] text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                >
                    {{ option.label }}
                </Link>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-gray-500">
                    {{ filters.start }} al {{ filters.end }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Visitas Únicas</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.unique_visits }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Visitantes únicos del landing público.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">WhatsApp</p>
                    <p class="mt-3 text-4xl font-black text-emerald-600">{{ summary.whatsapp_leads }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Clicks al botón de WhatsApp registrados.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Formularios</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.form_leads }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Contacto general y solicitudes de cotización.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Citas Agendadas</p>
                    <p class="mt-3 text-4xl font-black text-[#FF7A00]">{{ summary.booked_appointments }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Reservas creadas durante el periodo.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Conv. Visita a Contacto</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatPercent(summary.visit_to_engaged_rate) }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">WhatsApp más formularios sobre visitas únicas.</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Conv. Visita a Cita</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatPercent(summary.visit_to_booking_rate) }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500">Citas agendadas sobre visitas únicas.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Funnel del Taller</h2>
                        <p class="mt-2 text-sm font-medium text-gray-500">Lectura rápida del rendimiento comercial del landing.</p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div
                            v-for="stage in funnel"
                            :key="stage.label"
                            class="rounded-2xl border border-gray-100 bg-gray-50/70 p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ stage.label }}</p>
                                    <p class="mt-2 text-3xl font-black text-gray-900">{{ stage.count }}</p>
                                    <p class="mt-2 text-sm font-medium text-gray-500">{{ stage.description }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">
                                    {{ formatPercent(stage.conversion_rate) }}
                                </span>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
                                <div
                                    class="h-full rounded-full bg-[#FF7A00]"
                                    :style="{ width: `${Math.min(stage.conversion_rate, 100)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Serie Diaria</h2>
                        <p class="mt-2 text-sm font-medium text-gray-500">Seguimiento por día de visitas, formularios, WhatsApp y citas.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">Fecha</th>
                                    <th class="px-4 py-4 text-right">Visitas</th>
                                    <th class="px-4 py-4 text-right">Únicas</th>
                                    <th class="px-4 py-4 text-right">WhatsApp</th>
                                    <th class="px-4 py-4 text-right">Formularios</th>
                                    <th class="px-4 py-4 text-right">Citas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="day in dailySeries" :key="day.date" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4 text-sm font-black text-gray-900">{{ day.label }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-700">{{ day.visits }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-700">{{ day.unique_visits }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-emerald-600">{{ day.whatsapp_leads }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-700">{{ day.form_leads }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-[#FF7A00]">{{ day.booked_appointments }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-8 py-6">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Actividad Reciente</h2>
                    <p class="mt-2 text-sm font-medium text-gray-500">Últimos eventos capturados desde el funnel público del taller.</p>
                </div>

                <div v-if="recentActivity.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                    No hay actividad capturada en este periodo.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <th class="px-8 py-4 text-left">Canal</th>
                                <th class="px-4 py-4 text-left">Nombre</th>
                                <th class="px-4 py-4 text-left">Contacto</th>
                                <th class="px-4 py-4 text-left">Detalle</th>
                                <th class="px-4 py-4 text-right">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in recentActivity" :key="`${item.source}-${item.occurred_at}-${item.detail}`" class="transition-colors hover:bg-gray-50/40">
                                <td class="px-8 py-4">
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-gray-600">
                                        {{ item.source_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ item.visitor_name }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-500">{{ item.contact }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-500">{{ item.detail }}</td>
                                <td class="px-4 py-4 text-right text-xs font-medium text-gray-500">{{ formatDateTime(item.occurred_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </PrintableReportShell>
    </TallerLayout>
</template>
