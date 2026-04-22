<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    payments: Object,
    monthly_summary: Array,
    totals: Object,
});

const STATUS_CONFIG = {
    approved:  { label: 'Aprobado',  classes: 'bg-emerald-100 text-emerald-800' },
    pending:   { label: 'Pendiente', classes: 'bg-amber-100 text-amber-800' },
    rejected:  { label: 'Rechazado', classes: 'bg-rose-100 text-rose-800' },
    cancelled: { label: 'Cancelado', classes: 'bg-slate-100 text-slate-600' },
    refunded:  { label: 'Reembolsado', classes: 'bg-purple-100 text-purple-800' },
};

const PAYMENT_TYPE_LABEL = {
    credit_card:    'Tarjeta Crédito',
    debit_card:     'Tarjeta Débito',
    account_money:  'Billetera MP',
    ticket:         'Cupón/Ticket',
    bank_transfer:  'Transferencia',
};

function clp(amount) {
    if (amount == null) return '—';
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);
}

function pct(part, total) {
    if (!total) return '0%';
    return ((part / total) * 100).toFixed(1) + '%';
}

function localDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('es-CL', { dateStyle: 'short', timeStyle: 'short' });
}

function monthLabel(ym) {
    if (!ym) return ym;
    const [y, m] = ym.split('-');
    return new Date(+y, +m - 1).toLocaleString('es-CL', { month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head title="Historial de Pagos" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Historial de Pagos</h1>
                <p class="mt-1 text-sm text-slate-500">Desglose de ingresos, comisiones MP e IVA.</p>
            </div>
        </div>

        <!-- Totales globales -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ingresos Brutos</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ clp(totals?.gross) }}</p>
                <p class="text-xs text-slate-400">{{ totals?.transactions ?? 0 }} transacciones</p>
            </div>
            <div class="rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Comisión MP (neto)</p>
                <p class="mt-1 text-xl font-bold text-rose-600">{{ clp(totals?.fees_net) }}</p>
                <p class="text-xs text-slate-400">{{ pct(totals?.fees_net, totals?.gross) }} del bruto</p>
            </div>
            <div class="rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">IVA Comisión (19%)</p>
                <p class="mt-1 text-xl font-bold text-orange-600">{{ clp(totals?.fees_vat) }}</p>
                <p class="text-xs text-slate-400">{{ pct(totals?.fees_vat, totals?.gross) }} del bruto</p>
            </div>
            <div class="rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Neto Recibido</p>
                <p class="mt-1 text-xl font-bold text-emerald-600">{{ clp(totals?.net) }}</p>
                <p class="text-xs text-slate-400">{{ pct(totals?.net, totals?.gross) }} del bruto</p>
            </div>
        </div>

        <!-- Resumen mensual -->
        <div v-if="monthly_summary?.length" class="mb-8 rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Resumen Mensual (últimos 6 meses)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Mes</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500">Transac.</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500">Bruto</th>
                            <th class="px-4 py-3 text-right font-medium text-rose-600">Comisión MP</th>
                            <th class="px-4 py-3 text-right font-medium text-orange-600">IVA comisión</th>
                            <th class="px-4 py-3 text-right font-medium text-emerald-700">Neto Recibido</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-400">% Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in monthly_summary" :key="row.month" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800 capitalize">{{ monthLabel(row.month) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ row.transactions }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ clp(row.gross) }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ clp(row.fees_net) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600">{{ clp(row.fees_vat) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ clp(row.net) }}</td>
                            <td class="px-4 py-3 text-right text-slate-400 text-xs">
                                {{ pct((+row.fees_net) + (+row.fees_vat), row.gross) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla detallada -->
        <div class="rounded-xl bg-white ring-1 ring-slate-900/5 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Detalle de Transacciones</h2>
            </div>

            <div v-if="!payments.data.length" class="px-6 py-12 text-center text-slate-400 text-sm">
                No hay pagos registrados aún.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Taller</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Plan</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-500">Medio</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500">Bruto</th>
                            <th class="px-4 py-3 text-right font-medium text-rose-500">
                                <span class="block">Comisión</span>
                                <span class="block text-xs font-normal text-slate-400">sin IVA</span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium text-orange-500">
                                <span class="block">IVA</span>
                                <span class="block text-xs font-normal text-slate-400">19%</span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium text-slate-500">
                                <span class="block">Total costo</span>
                                <span class="block text-xs font-normal text-slate-400">comisión+IVA</span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium text-emerald-600">Neto</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs">
                                {{ p.paid_at ? localDate(p.paid_at) : localDate(p.created_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ p.tenant ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.plan ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                <span>{{ PAYMENT_TYPE_LABEL[p.mp_payment_type] ?? p.mp_payment_type ?? '—' }}</span>
                                <span v-if="p.mp_installments" class="ml-1 text-slate-400">({{ p.mp_installments }}x)</span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-900">{{ clp(p.amount) }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">
                                {{ p.status === 'approved' ? clp(p.mp_fee_net) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-orange-600">
                                {{ p.status === 'approved' ? clp(p.mp_fee_vat) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                <span v-if="p.status === 'approved'">
                                    {{ clp(p.mp_fee_total) }}
                                    <span class="block text-xs text-slate-400">{{ pct(p.mp_fee_total, p.amount) }}</span>
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-700">
                                {{ p.status === 'approved' ? clp(p.net_amount) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="(STATUS_CONFIG[p.status] ?? STATUS_CONFIG.pending).classes"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >
                                    {{ (STATUS_CONFIG[p.status] ?? STATUS_CONFIG.pending).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="payments.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-slate-100 text-sm text-slate-500">
                <span>Mostrando {{ payments.from }}–{{ payments.to }} de {{ payments.total }}</span>
                <div class="flex gap-1">
                    <Link
                        v-for="link in payments.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            link.active ? 'bg-amber-500 text-white font-semibold' : 'hover:bg-slate-100 text-slate-600',
                            !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            'px-3 py-1 rounded-md transition-colors'
                        ]"
                        v-html="link.label"
                        :as="link.url ? 'a' : 'span'"
                    />
                </div>
            </div>
        </div>

        <!-- Nota metodológica -->
        <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-500">
            <strong class="text-slate-700">Metodología de cálculo:</strong>
            La comisión de Mercado Pago varía según el medio de pago (ej. 2,99% + IVA en tarjeta de crédito con cuotas). 
            Los montos aquí reflejan el valor informado en <code>fee_details</code> de la API de MP al momento del cobro.
            El IVA (19%) sobre la comisión es calculado como <code>fee_total / 1.19 = fee_neto; fee_total - fee_neto = IVA</code>.
        </div>
    </AdminLayout>
</template>
