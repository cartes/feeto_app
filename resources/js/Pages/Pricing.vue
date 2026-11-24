<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import { computed, ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import ChristmasBanner from '@/Components/ChristmasBanner.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    canLogin: { type: Boolean, default: false },
    seo: { type: Object, default: () => ({}) },
    christmasOffer: {
        type: Object,
        default: () => ({
            active: true,
            offer_price: 19990,
            deadline_date: '2026-12-31T23:59:59',
            deadline_label: '31 de diciembre',
            duration_label: '1 año completo',
            target_plan_slug: 'empresa',
        }),
    },
});

const billing = ref('monthly');
const isChristmasOfferActive = ref(true);

const toggleBilling = (val) => { billing.value = val; };

const formatCLP = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 0,
}).format(value);

const isAllFeaturesPlan = (plan) => {
    return plan.slug === 'empresa' ||
        plan.name?.toLowerCase().includes('empresa') ||
        (plan.features?.length >= 5 && plan.price_monthly >= 39990);
};

const hasChristmasPrice = (plan) => {
    if (!isChristmasOfferActive.value) return false;
    return plan.slug === 'empresa' || plan.slug === 'profesional' || isAllFeaturesPlan(plan);
};

const displayPrice = (plan) => {
    if (isChristmasOfferActive.value && hasChristmasPrice(plan)) {
        return props.christmasOffer?.offer_price ?? 19990;
    }
    if (billing.value === 'annual') {
        return Math.round(plan.price_annual / 12);
    }
    return plan.has_discount ? plan.discounted_monthly_price : plan.price_monthly;
};

const displayAnnualCost = (plan) => {
    if (isChristmasOfferActive.value && hasChristmasPrice(plan)) {
        const promoMonthly = props.christmasOffer?.offer_price ?? 19990;
        return promoMonthly * 12;
    }
    return plan.price_annual;
};

const annualSavings = (plan) => {
    const monthlyCost = plan.price_monthly * 12;
    if (monthlyCost === 0) return 0;

    if (isChristmasOfferActive.value && hasChristmasPrice(plan)) {
        const promoAnnualCost = (props.christmasOffer?.offer_price ?? 19990) * 12;
        return Math.round((1 - promoAnnualCost / monthlyCost) * 100);
    }

    const annualCost = plan.price_annual;
    return Math.round((1 - annualCost / monthlyCost) * 100);
};

const monthlySavingsPercent = (plan) => {
    if (plan.price_monthly === 0) return 0;
    const promoMonthly = props.christmasOffer?.offer_price ?? 19990;
    return Math.round((1 - promoMonthly / plan.price_monthly) * 100);
};

const jsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: 'Planes TallerFlow',
    description: props.seo.description ?? '',
    itemListElement: props.plans.map((plan, i) => ({
        '@type': 'ListItem',
        position: i + 1,
        item: {
            '@type': 'Product',
            name: `TallerFlow Plan ${plan.name}`,
            description: plan.description,
            offers: {
                '@type': 'Offer',
                price: hasChristmasPrice(plan) ? (props.christmasOffer?.offer_price ?? 19990) : plan.price_monthly,
                priceCurrency: 'CLP',
                availability: 'https://schema.org/InStock',
                priceValidUntil: '2026-12-31',
            },
        },
    })),
}));

</script>

<template>

    <SeoHead :seo="seo" :fallback-title="'Planes y Precios · TallerFlow'" />

    <!-- JSON-LD structured data -->
    <component :is="'script'" type="application/ld+json">{{ jsonLd }}</component>

    <PublicNav :can-login="canLogin" />

    <div class="min-h-screen bg-white font-sans antialiased">

        <div class="dark-pricing-header">

            <!-- ======== HERO ======== -->
            <section class="pricing-hero">
                <div class="pricing-hero-inner">
                    <span class="pricing-eyebrow">
                        <span class="dot"></span> 🎄 Especial de Navidad 2026
                    </span>
                    <h1 class="pricing-headline">
                        Planes para cada etapa<br><em>de tu taller.</em>
                    </h1>
                    <p class="pricing-sub">
                        Sin contratos forzosos. Sin sorpresas. 14 días de prueba gratis en todos los planes.
                    </p>

                    <!-- Modos de Oferta: Oferta Navideña vs Regular -->
                    <div class="flex flex-wrap items-center justify-center gap-2 mb-6">
                        <button
                            type="button"
                            @click="isChristmasOfferActive = true"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs md:text-sm font-bold transition-all',
                                isChristmasOfferActive
                                    ? 'bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white shadow-lg shadow-red-500/30 border border-red-400/60'
                                    : 'bg-white/10 text-white/70 hover:text-white border border-white/10'
                            ]"
                        >
                            <span>🎄 Ver Oferta Navideña ($19.990 Full)</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-300 text-slate-950 text-[10px] uppercase font-black">
                                Activa
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="isChristmasOfferActive = false"
                            :class="[
                                'inline-flex items-center px-4 py-2 rounded-full text-xs md:text-sm font-medium transition-all',
                                !isChristmasOfferActive
                                    ? 'bg-white text-slate-950 font-bold shadow-md'
                                    : 'bg-white/10 text-white/70 hover:text-white border border-white/10'
                            ]"
                        >
                            Precios Regulares
                        </button>
                    </div>

                    <!-- Billing toggle -->
                    <div class="billing-toggle-wrap">
                        <div class="billing-toggle">
                            <button :class="['billing-btn', { 'billing-btn-active': billing === 'monthly' }]"
                                @click="toggleBilling('monthly')">Mensual</button>
                            <button :class="['billing-btn', { 'billing-btn-active': billing === 'annual' }]"
                                @click="toggleBilling('annual')">
                                Anual
                                <span class="billing-save-badge">
                                    {{ isChristmasOfferActive ? 'Ahorro Navideño 75%' : 'Ahorra hasta 17%' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ======== PLANS SECTION CON BANNER NAVIDEÑO ======== -->
        <section class="plans-section">
            <div class="plans-inner">

                <!-- Banner Navideño Destacado -->
                <ChristmasBanner
                    v-if="isChristmasOfferActive"
                    :offer-price="christmasOffer.offer_price"
                    :deadline-label="christmasOffer.deadline_label"
                    :deadline-date="christmasOffer.deadline_date"
                    class="mb-12"
                />

                <!-- Grid de Planes -->
                <div id="planes-grid" class="plans-grid" :class="`plans-grid-${plans.length}`">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="[
                            'plan-card',
                            isChristmasOfferActive && isAllFeaturesPlan(plan)
                                ? 'plan-card-christmas'
                                : (plan.is_popular ? 'plan-card-popular' : '')
                        ]"
                    >
                        <!-- Badge Navideño o Popular -->
                        <div v-if="isChristmasOfferActive && isAllFeaturesPlan(plan)" class="christmas-card-badge">
                            <span>🎄</span> OFERTA NAVIDAD: TODAS LAS FUNCIONALIDADES
                        </div>
                        <div v-else-if="plan.is_popular" class="popular-badge">
                            Más popular
                        </div>

                        <!-- Header -->
                        <div class="plan-header">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="plan-name">{{ plan.name }}</h2>
                                <span
                                    v-if="isChristmasOfferActive && hasChristmasPrice(plan)"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700"
                                >
                                    <span>🎅</span> Precio Promo
                                </span>
                            </div>
                            <p class="plan-desc">{{ plan.description }}</p>
                        </div>

                        <!-- Price -->
                        <div class="plan-price-block">
                            <!-- Precio regular tachado en oferta navideña -->
                            <div v-if="isChristmasOfferActive && hasChristmasPrice(plan)" class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-gray-400 font-medium">Precio normal:</span>
                                <span class="christmas-price-strike">{{ formatCLP(plan.price_monthly) }}/mes</span>
                                <span class="christmas-savings-tag">
                                    -{{ monthlySavingsPercent(plan) }}% OFF
                                </span>
                            </div>

                            <div class="plan-price-row">
                                <span class="plan-currency">$</span>
                                <span class="plan-amount">{{ new Intl.NumberFormat('es-CL').format(displayPrice(plan)) }}</span>
                                <span class="plan-period">/mes</span>
                            </div>

                            <!-- Notas de período y ahorro -->
                            <template v-if="isChristmasOfferActive && hasChristmasPrice(plan)">
                                <p v-if="billing === 'annual'" class="plan-annual-note font-semibold text-emerald-700">
                                    Cobrado anualmente · <strong>{{ formatCLP(displayAnnualCost(plan)) }}/año</strong>
                                    <span class="block text-[11px] text-gray-500 font-normal">
                                        (Ahorras {{ formatCLP((plan.price_monthly * 12) - displayAnnualCost(plan)) }} frente al valor normal)
                                    </span>
                                </p>
                                <p v-else class="plan-annual-note text-red-700 font-medium flex items-center gap-1">
                                    <span>🎄</span> Precio congelado por 1 año contratando hasta el 31 de Dic.
                                </p>
                                <div class="plan-savings-pill bg-emerald-100 text-emerald-800">
                                    Ahorras {{ annualSavings(plan) }}% durante 12 meses
                                </div>
                            </template>

                            <template v-else>
                                <p v-if="billing === 'annual'" class="plan-annual-note">
                                    Cobrado anualmente · <strong>{{ formatCLP(plan.price_annual) }}/año</strong>
                                </p>
                                <p v-else-if="plan.has_discount" class="plan-discount-note">
                                    <span class="line-through opacity-60">{{ formatCLP(plan.price_monthly) }}</span>
                                    <span class="ml-1 text-emerald-600 font-bold">−{{ plan.discount_percent }}%</span>
                                </p>
                                <div v-if="billing === 'annual'" class="plan-savings-pill">
                                    Ahorra {{ annualSavings(plan) }}%
                                </div>
                            </template>

                            <!-- Tip para planes de menor escala en navidad -->
                            <p
                                v-if="isChristmasOfferActive && plan.slug === 'basico'"
                                class="mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200/60 rounded-lg p-2 font-medium"
                            >
                                💡 Tip Navideño: Por este mismo valor de {{ formatCLP(displayPrice(plan)) }}, ¡te llevas el Plan Empresa con todas las funciones liberadas!
                            </p>
                        </div>

                        <!-- CTA -->
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            :class="['plan-cta', plan.is_popular ? 'plan-cta-popular' : 'plan-cta-default']"
                        >
                            Ir al Dashboard
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>
                        <Link
                            v-else
                            :href="route('trial.create')"
                            :class="[
                                'plan-cta',
                                isChristmasOfferActive && isAllFeaturesPlan(plan)
                                    ? 'plan-cta-christmas'
                                    : (plan.is_popular ? 'plan-cta-popular' : 'plan-cta-default')
                            ]"
                        >
                            <span>{{ isChristmasOfferActive && hasChristmasPrice(plan) ? '🎁 Empezar con Oferta Navideña' : 'Empezar prueba gratis' }}</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>

                        <p class="plan-cta-note">
                            {{ plan.trial_days ?? 14 }} días gratis · Sin tarjeta de crédito
                        </p>

                        <!-- Divider -->
                        <div class="plan-divider"></div>

                        <!-- Features -->
                        <ul class="plan-features">
                            <li v-for="feature in plan.features" :key="feature" class="plan-feature">
                                <span
                                    :class="[
                                        'plan-feature-ico',
                                        isChristmasOfferActive && isAllFeaturesPlan(plan)
                                            ? 'plan-feature-ico-christmas'
                                            : (plan.is_popular ? 'plan-feature-ico-popular' : '')
                                    ]"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <!-- Footer meta -->
                        <div class="plan-meta">
                            <span class="plan-meta-tag">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
                                </svg>
                                Hasta {{ plan.max_users }} usuario{{ plan.max_users !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!plans.length" class="plans-empty">
                    <p class="text-gray-400 text-sm">No hay planes disponibles en este momento.</p>
                </div>

                <!-- Bloque aclaratorio de la Oferta Navideña -->
                <div class="mt-14 rounded-3xl bg-gradient-to-br from-red-950 via-slate-900 to-emerald-950 border border-red-500/30 p-8 md:p-10 text-white shadow-xl">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="space-y-2 max-w-2xl">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600/30 border border-red-400/40 text-amber-200 text-xs font-bold uppercase tracking-wider">
                                <span>🎄</span> Transparencia y Garantía Navideña
                            </span>
                            <h3 class="text-xl md:text-2xl font-black text-white">
                                ¿Cómo funciona la oferta de todas las funcionalidades a $19.990?
                            </h3>
                            <p class="text-sm text-gray-300 leading-relaxed">
                                Cualquier taller que contrate o comience su proceso de registro hasta las 23:59 del <strong>31 de diciembre de 2026</strong> accede al <strong>Plan Empresa completo</strong> (Recepción con IA de patentes, WhatsApp automático a clientes, Kanban, Agenda, Inventario masivo y usuarios extendidos) por un valor promocional de <strong>$19.990 mensuales congelados durante 12 meses continuos</strong>. Sin letra chica ni contratos forzosos.
                            </p>
                        </div>
                        <Link
                            :href="route('trial.create')"
                            class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm transition-all shadow-lg hover:shadow-amber-400/25 transform hover:-translate-y-0.5"
                        >
                            <span>Asegurar Oferta de Navidad</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>
                    </div>
                </div>

            </div>
        </section>

        <!-- ======== FAQ / GARANTÍAS ======== -->
        <section class="guarantee-section">
            <div class="guarantee-inner">
                <h2 class="guarantee-title">Preguntas frecuentes</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3 class="faq-q">¿Cómo se activa la Oferta Navideña de $19.990?</h3>
                        <p class="faq-a">Solo debes iniciar tu prueba gratuita de 14 días antes del 31 de diciembre. Al contratar, tu tarifa queda congelada a $19.990/mes por 12 meses con todas las funcionalidades liberadas.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Necesito tarjeta de crédito para la prueba?</h3>
                        <p class="faq-a">No. Activas tu prueba gratuita de 14 días sin ingresar datos de pago. Solo necesitas tu correo y el nombre de tu taller.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Puedo cancelar cuando quiera?</h3>
                        <p class="faq-a">Sí. No hay contratos de permanencia ni letras chicas. Puedes cancelar tu suscripción en cualquier momento desde tu panel de configuración.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Qué pasa después del año de promoción?</h3>
                        <p class="faq-a">Te avisaremos con anticipación antes de cumplir los 12 meses. Podrás mantener tu plan con tarifa preferencial de renovación o elegir el plan que mejor se adapte a tu taller.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Los precios incluyen IVA?</h3>
                        <p class="faq-a">Los precios mostrados son en CLP netos. El cobro final puede incluir impuestos según tu situación tributaria y emisión de factura o boleta.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Qué métodos de pago aceptan?</h3>
                        <p class="faq-a">Aceptamos tarjetas de crédito, débito y transferencias a través de Mercado Pago.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======== BOTTOM CTA ======== -->
        <section class="bottom-cta-section">
            <div class="bottom-cta-inner">
                <div class="bottom-cta-card">
                    <div>
                        <h2 class="bottom-cta-title">¿Listo para digitalizar tu taller esta Navidad?</h2>
                        <p class="bottom-cta-sub">14 días gratis. Todas las funcionalidades por 1 año a $19.990. Sin tarjeta de crédito.</p>
                    </div>
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="bottom-cta-btn">
                        Ir al Dashboard
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                    <Link v-else :href="route('trial.create')" class="bottom-cta-btn">
                        Empezar ahora gratis
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <PublicFooter />
    </div>

</template>

<style scoped>
/* ── Variables ───────────────────────────── */
:root {
    --accent: #FF7A00;
    --accent-ink: #1a0e00;
    --dark: #0a0a0b;
}

/* ── Dark header wrapper ─────────────────── */
.dark-pricing-header {
    background: #0a0a0b;
    position: relative;
}

/* ── Hero ─────────────────────────────────── */
.pricing-hero {
    padding: 130px 18px 80px;
}

.pricing-hero-inner {
    max-width: 780px;
    margin: 0 auto;
    text-align: center;
}

.pricing-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border: 1px solid rgba(239, 68, 68, 0.4);
    border-radius: 999px;
    background: rgba(220, 38, 38, 0.15);
    color: #fca5a5;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 24px;
}

.pricing-eyebrow .dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #ef4444;
    box-shadow: 0 0 10px #ef4444;
}

.pricing-headline {
    margin: 0 0 16px;
    color: #fff;
    font-size: clamp(36px, 6vw, 64px);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.03em;
}

.pricing-headline em {
    color: #FF7A00;
    font-style: normal;
}

.pricing-sub {
    color: rgba(255, 255, 255, 0.65);
    font-size: 17px;
    line-height: 1.6;
    margin: 0 0 32px;
}

/* ── Billing toggle ──────────────────────── */
.billing-toggle-wrap {
    display: flex;
    justify-content: center;
}

.billing-toggle {
    display: inline-flex;
    padding: 4px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    gap: 2px;
}

.billing-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: rgba(255, 255, 255, 0.65);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.billing-btn-active {
    background: #fff;
    color: #1a0e00;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.billing-save-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    background: #dc2626;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.billing-btn-active .billing-save-badge {
    background: #dc2626;
    color: #fff;
}

/* ── Plans section ───────────────────────── */
.plans-section {
    background: #f8f9fb;
    padding: 60px 18px 80px;
}

.plans-inner {
    max-width: 1240px;
    margin: 0 auto;
}

.plans-grid {
    display: grid;
    gap: 24px;
    align-items: start;
}

.plans-grid-1 {
    grid-template-columns: 1fr;
    max-width: 400px;
    margin: 0 auto;
}

.plans-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.plans-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

.plans-grid-4 {
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 1120px) {
    .plans-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .plans-grid-4 {
        grid-template-columns: 1fr;
    }
}

.plan-card {
    position: relative;
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 24px;
    padding: 32px 28px;
    display: flex;
    flex-direction: column;
    gap: 0;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.plan-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.10);
    transform: translateY(-2px);
}

.plan-card-popular {
    border-color: #FF7A00;
    border-width: 2px;
    background: #fff;
    box-shadow: 0 8px 32px rgba(255, 181, 71, 0.18), 0 2px 8px rgba(0, 0, 0, 0.06);
}

.plan-card-popular:hover {
    box-shadow: 0 16px 48px rgba(255, 181, 71, 0.24), 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* Tarjeta Destacada Navideña */
.plan-card-christmas {
    border-color: #dc2626;
    border-width: 2px;
    background: #ffffff;
    box-shadow: 0 12px 36px rgba(220, 38, 38, 0.20), 0 2px 10px rgba(0, 0, 0, 0.06);
}

.plan-card-christmas:hover {
    box-shadow: 0 18px 50px rgba(220, 38, 38, 0.28), 0 4px 18px rgba(0, 0, 0, 0.08);
}

.christmas-card-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    padding: 6px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, #b91c1c, #991b1b);
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    box-shadow: 0 4px 14px rgba(185, 28, 28, 0.40);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.christmas-price-strike {
    color: #9ca3af;
    text-decoration: line-through;
    font-size: 14px;
    font-weight: 600;
}

.christmas-savings-tag {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 11px;
    font-weight: 800;
}

.popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    padding: 5px 16px;
    border-radius: 999px;
    background: #FF7A00;
    color: #1a0e00;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(255, 181, 71, 0.40);
}

.plan-header {
    margin-bottom: 20px;
}

.plan-name {
    font-size: 22px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
    margin: 0;
}

.plan-desc {
    font-size: 14px;
    color: #6b7280;
    margin: 6px 0 0;
    line-height: 1.5;
}

.plan-price-block {
    margin-bottom: 24px;
    min-height: 95px;
}

.plan-price-row {
    display: flex;
    align-items: baseline;
    gap: 2px;
}

.plan-currency {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin-top: 6px;
}

.plan-amount {
    font-size: 46px;
    font-weight: 900;
    color: #111827;
    letter-spacing: -0.04em;
    line-height: 1;
}

.plan-period {
    font-size: 16px;
    color: #9ca3af;
    font-weight: 500;
    margin-left: 4px;
}

.plan-annual-note {
    margin: 6px 0 0;
    font-size: 12px;
    color: #6b7280;
}

.plan-discount-note {
    margin: 6px 0 0;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.plan-savings-pill {
    display: inline-block;
    margin-top: 8px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #dcfce7;
    color: #166534;
    font-size: 12px;
    font-weight: 700;
}

.plan-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    margin-bottom: 8px;
}

.plan-cta-popular {
    background: #FF7A00;
    color: #1a0e00;
    box-shadow: 0 4px 16px rgba(255, 181, 71, 0.35);
}

.plan-cta-popular:hover {
    background: #CC6200;
    box-shadow: 0 6px 20px rgba(255, 181, 71, 0.45);
    transform: translateY(-1px);
}

.plan-cta-christmas {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35);
}

.plan-cta-christmas:hover {
    background: linear-gradient(135deg, #b91c1c, #991b1b);
    box-shadow: 0 6px 22px rgba(220, 38, 38, 0.45);
    transform: translateY(-1px);
}

.plan-cta-default {
    background: #111827;
    color: #fff;
}

.plan-cta-default:hover {
    background: #1f2937;
    transform: translateY(-1px);
}

.plan-cta-note {
    text-align: center;
    font-size: 12px;
    color: #9ca3af;
    margin: 0 0 24px;
}

.plan-divider {
    height: 1px;
    background: #f3f4f6;
    margin: 0 0 24px;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

.plan-feature {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    color: #374151;
    line-height: 1.4;
}

.plan-feature-ico {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    background: #f0fdf4;
    color: #16a34a;
    margin-top: 1px;
}

.plan-feature-ico-popular {
    background: #fffbeb;
    color: #CC6200;
}

.plan-feature-ico-christmas {
    background: #fef2f2;
    color: #dc2626;
}

.plan-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.plan-meta-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #4b5563;
    font-size: 12px;
    font-weight: 500;
}

.plans-empty {
    text-align: center;
    padding: 60px 0;
}

/* ── FAQ ─────────────────────────────────── */
.guarantee-section {
    background: #fff;
    padding: 80px 18px;
    border-top: 1px solid #e8eaed;
}

.guarantee-inner {
    max-width: 900px;
    margin: 0 auto;
}

.guarantee-title {
    font-size: 32px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
    text-align: center;
    margin: 0 0 48px;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px 48px;
}

.faq-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.faq-q {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.faq-a {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

/* ── Bottom CTA ──────────────────────────── */
.bottom-cta-section {
    background: #f8f9fb;
    padding: 0 18px 80px;
}

.bottom-cta-inner {
    max-width: 900px;
    margin: 0 auto;
}

.bottom-cta-card {
    background: #0a0a0b;
    border-radius: 28px;
    padding: 56px 48px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
}

.bottom-cta-title {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    margin: 0 0 8px;
}

.bottom-cta-sub {
    font-size: 15px;
    color: rgba(255, 255, 255, 0.56);
    margin: 0;
}

.bottom-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 28px;
    border-radius: 999px;
    background: #FF7A00;
    color: #1a0e00;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.bottom-cta-btn:hover {
    background: #CC6200;
    transform: translateY(-1px);
}

/* ── Responsive ──────────────────────────── */
@media (max-width: 900px) {
    .pricing-hero {
        padding: 120px 18px 60px;
    }

    .plans-grid-2,
    .plans-grid-3 {
        grid-template-columns: 1fr;
    }

    .faq-grid {
        grid-template-columns: 1fr;
    }

    .bottom-cta-card {
        flex-direction: column;
        align-items: flex-start;
        padding: 40px 28px;
    }
}
</style>
