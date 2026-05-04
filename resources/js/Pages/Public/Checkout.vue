<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PlanCard from '@/Components/PlanCard.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    plans: { type: Array, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const billingPeriod = ref('monthly');
const form = useForm({ plan_id: null, billing_period: 'monthly' });

const subscribe = (plan) => {
    form.plan_id = plan.id;
    form.billing_period = billingPeriod.value;
    form.post(route('checkout.preference', { tenantBySlug: props.tenant.slug }));
};
</script>

<template>
    <Head :title="`Planes para ${tenant.name} — Feeto`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/30 to-slate-100 font-sans">

        <!-- Header -->
        <header class="bg-white/80 backdrop-blur border-b border-white/60 shadow-sm">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <span class="text-lg font-extrabold text-slate-800 leading-none">Feeto</span>
                    <span class="text-slate-400 mx-2">·</span>
                    <span class="text-slate-600 font-medium text-sm">{{ tenant.name }}</span>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-12 space-y-10">

            <!-- Hero -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-extrabold text-slate-800">
                    Elige el plan perfecto para <span class="text-orange-500">{{ tenant.name }}</span>
                </h1>
                <p class="text-slate-500 text-base max-w-lg mx-auto">
                    Accede a todas las herramientas que necesita tu taller. Sin contratos a largo plazo.
                </p>
            </div>

            <!-- Flash messages -->
            <div v-if="flash.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-5 py-4 text-sm font-medium text-center">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl px-5 py-4 text-sm font-medium text-center">
                {{ flash.error }}
            </div>
            <div v-if="flash.info" class="bg-blue-50 border border-blue-200 text-blue-800 rounded-2xl px-5 py-4 text-sm font-medium text-center">
                {{ flash.info }}
            </div>

            <!-- Toggle mensual / anual -->
            <div class="flex justify-center">
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <PlanCard
                    v-for="plan in plans"
                    :key="plan.id"
                    :plan="plan"
                    :billing-period="billingPeriod"
                    :is-current="false"
                    :loading="form.processing && form.plan_id === plan.id"
                    @subscribe="subscribe"
                />
            </div>

            <!-- Footer note -->
            <p class="text-center text-xs text-slate-400 pb-6">
                Pagos procesados de forma segura con Mercado Pago.
                Al suscribirte aceptas los <a href="#" class="underline">términos de servicio</a>.
            </p>
        </main>
    </div>
</template>
