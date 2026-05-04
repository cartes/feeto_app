<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { STATUS_CONFIG, PAYMENT_TYPE_LABEL } from '@/constants/payment';

const props = defineProps({
    payments: { type: Object, required: true },
    summary:  { type: Object, required: true },
});

const page = usePage();
const tenantContext = computed(() => page.props.tenantContext ?? {});
const tenantSlug    = computed(() => page.props.tenant?.slug);
const flash         = computed(() => page.props.flash ?? {});

const subscriptionEndsAt = computed(() => tenantContext.value.subscription_ends_at ?? null);
const isActive           = computed(() => tenantContext.value.is_active ?? false);

const isExpired = computed(() =>
    subscriptionEndsAt.value && new Date(subscriptionEndsAt.value) < new Date()
);

const daysLeft = computed(() => {
    if (! subscriptionEndsAt.value) return null;
    const diff = new Date(subscriptionEndsAt.value) - new Date();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const renewalDate = computed(() => {
    if (! subscriptionEndsAt.value) return null;
    return new Intl.DateTimeFormat('es-CL', {
        day: 'numeric', month: 'long', year: 'numeric',
    }).format(new Date(subscriptionEndsAt.value));
});

function clp(amount) {
    if (amount == null) return '—';
    return new Intl.NumberFormat('es-CL', {
        style: 'currency', currency: 'CLP', maximumFractionDigits: 0,
    }).format(amount);
}

function formatDate(iso) {
    if (! iso) return '—';
    return new Intl.DateTimeFormat('es-CL', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    }).format(new Date(iso));
}

const BILLING_PERIOD_LABEL = { monthly: 'Mensual', annual: 'Anual' };
</script>

<template>
    <Head title="Facturación" />

    <TallerLayout>
        <!-- Flash -->
        <div v-if="flash.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-5 py-4 text-sm font-medium">
            {{ flash.success }}
        </div>

        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Facturación</h1>
            <p class="text-slate-500 mt-1 text-sm">Historial de pagos y estado de tu suscripción.</p>
        </div>

        <!-- Banner de estado de suscripción -->
        <div
            class="flex items-center justify-between gap-4 rounded-2xl px-5 py-4 text-sm font-medium flex-wrap"
            :class="isActive && !isExpired
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-800'"
        >
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        :d="isActive && !isExpired
                            ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                            : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'"
                    />
                </svg>
                <span v-if="isActive && !isExpired && daysLeft !== null">
                    Suscripción activa — vence el <strong>{{ renewalDate }}</strong>
                    <span class="ml-1 opacity-70">({{ daysLeft }} día{{ daysLeft !== 1 ? 's' : '' }} restante{{ daysLeft !== 1 ? 's' : '' }})</span>
                </span>
                <span v-else-if="subscriptionEndsAt">
                    Suscripción <strong>vencida el {{ renewalDate }}</strong>. Renueva para continuar.
                </span>
                <span v-else>
                    No tienes una suscripción activa.
                </span>
            </div>
            <Link
                :href="route('subscription.plans', { tenantBySlug: tenantSlug })"
                class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold transition-colors whitespace-nowrap"
                :class="isActive && !isExpired
                    ? 'bg-emerald-700 text-white hover:bg-emerald-800'
                    : 'bg-rose-600 text-white hover:bg-rose-700'"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Renovar plan
            </Link>
        </div>

        <!-- Cards de resumen -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-white shadow-sm p-5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Total pagado</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ clp(summary.total_paid) }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ summary.count_approved }} pago{{ summary.count_approved !== 1 ? 's' : '' }} aprobado{{ summary.count_approved !== 1 ? 's' : '' }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-white shadow-sm p-5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Próxima renovación</p>
                <p class="text-2xl font-extrabold text-slate-800">{{ renewalDate ?? 'Sin fecha' }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ subscriptionEndsAt ? 'Fecha de vencimiento del plan actual' : 'Sin plan activo programado' }}
                </p>
            </div>
        </div>

        <!-- Tabla de transacciones -->
        <div class="bg-white rounded-2xl shadow-sm border border-white overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-800">Historial de transacciones</h2>
            </div>

            <!-- Estado vacío -->
            <div v-if="payments.data.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-slate-700 font-semibold">Aún no tienes pagos registrados</p>
                <p class="text-slate-400 text-sm mt-1 max-w-xs">
                    Cuando actives tu primer plan, tus transacciones aparecerán aquí.
                </p>
                <Link
                    :href="route('subscription.plans', { tenantBySlug: tenantSlug })"
                    class="mt-5 inline-flex items-center gap-2 bg-orange-500 text-white text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-orange-600 transition-colors"
                >
                    Ver planes disponibles
                </Link>
            </div>

            <!-- Tabla -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-6 py-3 text-left">Fecha</th>
                            <th class="px-6 py-3 text-left">Plan</th>
                            <th class="px-6 py-3 text-left">Período</th>
                            <th class="px-6 py-3 text-left">Medio de pago</th>
                            <th class="px-6 py-3 text-right">Monto</th>
                            <th class="px-6 py-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                {{ formatDate(p.paid_at ?? p.created_at) }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">{{ p.plan_name }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ BILLING_PERIOD_LABEL[p.billing_period] ?? p.billing_period }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ PAYMENT_TYPE_LABEL[p.mp_payment_type] ?? (p.mp_payment_type ? p.mp_payment_type : '—') }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800 tabular-nums whitespace-nowrap">
                                {{ clp(p.amount) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="(STATUS_CONFIG[p.status] ?? STATUS_CONFIG.pending).classes"
                                >
                                    {{ (STATUS_CONFIG[p.status] ?? STATUS_CONFIG.pending).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div
                v-if="payments.data.length > 0 && payments.last_page > 1"
                class="flex items-center justify-between px-6 py-4 border-t border-slate-100 text-sm text-slate-500"
            >
                <span>
                    Mostrando {{ payments.from }}–{{ payments.to }} de {{ payments.total }} transacciones
                </span>
                <div class="flex items-center gap-1">
                    <Link
                        v-for="link in payments.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                            link.active
                                ? 'bg-orange-500 text-white'
                                : link.url
                                    ? 'text-slate-600 hover:bg-slate-100'
                                    : 'text-slate-300 cursor-not-allowed pointer-events-none',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
