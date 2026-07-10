<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PlanCard from '@/Components/PlanCard.vue';

const props = defineProps({
    plans: { type: Array, required: true },
    currentPlanId: { type: Number, default: null },
    subscriptionEndsAt: { type: String, default: null },
    isActive: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const tenantSlug = computed(() => page.props.tenant?.slug);

const billingPeriod = ref('monthly');

const form = useForm({ plan_id: null, billing_period: 'monthly' });

const subscribe = (plan) => {
    form.plan_id = plan.id;
    form.billing_period = billingPeriod.value;
    form.post(route('subscription.preference', { tenantBySlug: tenantSlug.value }));
};

const subscriptionDate = computed(() => {
    if (! props.subscriptionEndsAt) return null;
    return new Intl.DateTimeFormat('es-CL', {
        day: 'numeric', month: 'long', year: 'numeric',
    }).format(new Date(props.subscriptionEndsAt));
});

const isExpired = computed(() => {
    if (! props.subscriptionEndsAt) return false;
    return new Date(props.subscriptionEndsAt) < new Date();
});
</script>

<template>
    <Head title="Planes de Suscripción" />

    <TallerLayout>
        <!-- Flash messages -->
        <div v-if="flash.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-5 py-4 text-sm font-medium">
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl px-5 py-4 text-sm font-medium">
            {{ flash.error }}
        </div>
        <div v-if="flash.info" class="bg-blue-50 border border-blue-200 text-blue-800 rounded-2xl px-5 py-4 text-sm font-medium">
            {{ flash.info }}
        </div>

        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Planes de suscripción</h1>
            <p class="text-slate-500 mt-1 text-sm">Elige el plan que mejor se adapte a tu taller.</p>
        </div>

        <!-- Banner de estado de suscripción -->
        <div
            v-if="subscriptionEndsAt"
            class="flex items-center gap-3 rounded-2xl px-5 py-4 text-sm font-medium"
            :class="isActive && !isExpired
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-800'"
        >
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    :d="isActive && !isExpired
                        ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                        : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'"
                />
            </svg>
            <span v-if="isActive && !isExpired">
                Suscripción activa — vence el <strong>{{ subscriptionDate }}</strong>
            </span>
            <span v-else>
                Tu suscripción <strong>venció el {{ subscriptionDate }}</strong>. Renueva para seguir usando el sistema.
            </span>
        </div>
        <div
            v-else
            class="flex items-center gap-3 rounded-2xl px-5 py-4 text-sm font-medium bg-amber-50 border border-amber-200 text-amber-800"
        >
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            No tienes un plan activo. Elige uno para empezar.
        </div>

        <!-- Toggle mensual / anual -->
        <div class="flex items-center justify-center" data-tour="subscription-plans-toggle">
            <div class="inline-flex items-center bg-white rounded-full p-1 shadow-sm border border-gray-100">
                <button
                    class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-200"
                    :class="billingPeriod === 'monthly'
                        ? 'bg-orange-500 text-white shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'"
                    @click="billingPeriod = 'monthly'"
                >
                    Mensual
                </button>
                <button
                    class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-200"
                    :class="billingPeriod === 'annual'
                        ? 'bg-orange-500 text-white shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'"
                    @click="billingPeriod = 'annual'"
                >
                    Anual
                    <span class="ml-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full">
                        Ahorra
                    </span>
                </button>
            </div>
        </div>

        <!-- Grid de planes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 pt-2" data-tour="subscription-plans-grid">
            <PlanCard
                v-for="plan in plans"
                :key="plan.id"
                :plan="plan"
                :billing-period="billingPeriod"
                :is-current="plan.id === currentPlanId"
                :loading="form.processing && form.plan_id === plan.id"
                @subscribe="subscribe"
            />
        </div>

        <p class="text-center text-xs text-slate-400">
            Los pagos se procesan de forma segura a través de Mercado Pago. IVA incluido en las comisiones.
        </p>
    </TallerLayout>
</template>
