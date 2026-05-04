<script setup>
const props = defineProps({
    plan: { type: Object, required: true },
    billingPeriod: { type: String, required: true }, // 'monthly' | 'annual'
    isCurrent: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['subscribe']);

const clp = (amount) =>
    new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        maximumFractionDigits: 0,
    }).format(amount);

const displayPrice = () => {
    if (props.billingPeriod === 'annual') return clp(props.plan.price_annual);
    return clp(props.plan.discounted_monthly_price ?? props.plan.price_monthly);
};

const hasDiscount = () =>
    props.billingPeriod === 'monthly' && props.plan.has_discount;
</script>

<template>
    <div
        class="relative flex flex-col rounded-3xl border-2 p-6 transition-all duration-300"
        :class="[
            isCurrent
                ? 'border-orange-400 bg-orange-50 shadow-[0_8px_24px_rgba(249,168,38,0.15)]'
                : 'border-white bg-white shadow-md hover:shadow-lg',
        ]"
    >
        <!-- Badge Popular -->
        <div v-if="plan.is_popular" class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-orange-500 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow">
                Más popular
            </span>
        </div>

        <!-- Badge Plan actual -->
        <div v-if="isCurrent" class="absolute -top-3 right-4">
            <span class="bg-emerald-500 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow">
                Plan actual
            </span>
        </div>

        <!-- Nombre y precio -->
        <div class="mb-4">
            <h3 class="text-xl font-bold text-slate-800">{{ plan.name }}</h3>
            <p v-if="plan.description" class="text-sm text-slate-500 mt-1">{{ plan.description }}</p>
        </div>

        <div class="mb-4">
            <div class="flex items-end gap-1">
                <span class="text-3xl font-extrabold text-slate-800">{{ displayPrice() }}</span>
                <span class="text-slate-500 text-sm mb-1">
                    {{ billingPeriod === 'annual' ? '/año' : '/mes' }}
                </span>
            </div>

            <!-- Precio original tachado si hay descuento -->
            <div v-if="hasDiscount()" class="flex items-center gap-2 mt-1">
                <span class="text-sm text-slate-400 line-through">{{ clp(plan.price_monthly) }}</span>
                <span class="bg-orange-100 text-orange-600 text-xs font-bold px-2 py-0.5 rounded-full">
                    -{{ plan.discount_percent }}%
                </span>
            </div>

            <p v-if="billingPeriod === 'annual'" class="text-xs text-emerald-600 font-medium mt-1">
                Equivalente a {{ clp(Math.round(plan.price_annual / 12)) }}/mes
            </p>
        </div>

        <!-- Features -->
        <ul class="flex-1 space-y-2 mb-6">
            <li class="flex items-center gap-2 text-sm text-slate-600">
                <svg class="h-4 w-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Hasta {{ plan.max_users }} usuario{{ plan.max_users > 1 ? 's' : '' }}
            </li>
            <li
                v-for="(feature, i) in plan.features"
                :key="i"
                class="flex items-center gap-2 text-sm text-slate-600"
            >
                <svg class="h-4 w-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ feature }}
            </li>
        </ul>

        <!-- CTA -->
        <button
            v-if="!isCurrent"
            :disabled="loading"
            class="w-full py-3 rounded-2xl font-bold text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2"
            :class="
                loading
                    ? 'bg-orange-300 text-white cursor-not-allowed'
                    : 'bg-orange-500 text-white hover:bg-orange-600 shadow-sm hover:shadow-md active:scale-[0.98]'
            "
            @click="emit('subscribe', plan)"
        >
            <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Procesando...
            </span>
            <span v-else>Suscribirse</span>
        </button>

        <div
            v-else
            class="w-full py-3 rounded-2xl font-bold text-sm text-center bg-slate-100 text-slate-400 cursor-default"
        >
            Plan activo
        </div>
    </div>
</template>
