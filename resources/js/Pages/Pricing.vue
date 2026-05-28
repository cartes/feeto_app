<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    canLogin: { type: Boolean, default: false },
    seo: { type: Object, default: () => ({}) },
});

const billing = ref('monthly');

const toggleBilling = (val) => { billing.value = val; };

const formatCLP = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 0,
}).format(value);

const displayPrice = (plan) => {
    if (billing.value === 'annual') {
        return Math.round(plan.price_annual / 12);
    }
    return plan.has_discount ? plan.discounted_monthly_price : plan.price_monthly;
};

const annualSavings = (plan) => {
    const monthlyCost = plan.price_monthly * 12;
    const annualCost = plan.price_annual;
    return Math.round((1 - annualCost / monthlyCost) * 100);
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
                price: plan.price_monthly,
                priceCurrency: 'CLP',
                availability: 'https://schema.org/InStock',
                priceValidUntil: new Date(new Date().getFullYear() + 1, 11, 31).toISOString().split('T')[0],
            },
        },
    })),
}));

</script>

<template>
    <Head>
        <title>{{ seo.title ?? 'Planes y Precios · TallerFlow' }}</title>
    </Head>

    <!-- JSON-LD structured data -->
    <component :is="'script'" type="application/ld+json">{{ jsonLd }}</component>

    <PublicNav :can-login="canLogin" />

    <div class="min-h-screen bg-white font-sans antialiased">

        <div class="dark-pricing-header">

            <!-- ======== HERO ======== -->
            <section class="pricing-hero">
                <div class="pricing-hero-inner">
                    <span class="pricing-eyebrow">
                        <span class="dot"></span> Planes
                    </span>
                    <h1 class="pricing-headline">
                        Planes para cada etapa<br><em>de tu taller.</em>
                    </h1>
                    <p class="pricing-sub">
                        Sin contratos. Sin sorpresas. {{ plans[0]?.trial_days ?? 14 }} días de prueba gratis en todos los planes.
                    </p>

                    <!-- Billing toggle -->
                    <div class="billing-toggle-wrap">
                        <div class="billing-toggle">
                            <button
                                :class="['billing-btn', { 'billing-btn-active': billing === 'monthly' }]"
                                @click="toggleBilling('monthly')"
                            >Mensual</button>
                            <button
                                :class="['billing-btn', { 'billing-btn-active': billing === 'annual' }]"
                                @click="toggleBilling('annual')"
                            >
                                Anual
                                <span class="billing-save-badge">Ahorra hasta 17%</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ======== PLANS GRID ======== -->
        <section class="plans-section">
            <div class="plans-inner">
                <div class="plans-grid" :class="`plans-grid-${plans.length}`">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="['plan-card', { 'plan-card-popular': plan.is_popular }]"
                    >
                        <!-- Popular badge -->
                        <div v-if="plan.is_popular" class="popular-badge">Más popular</div>

                        <!-- Header -->
                        <div class="plan-header">
                            <h2 class="plan-name">{{ plan.name }}</h2>
                            <p class="plan-desc">{{ plan.description }}</p>
                        </div>

                        <!-- Price -->
                        <div class="plan-price-block">
                            <div class="plan-price-row">
                                <span class="plan-currency">$</span>
                                <span class="plan-amount">{{ new Intl.NumberFormat('es-CL').format(displayPrice(plan)) }}</span>
                                <span class="plan-period">/mes</span>
                            </div>
                            <p v-if="billing === 'annual'" class="plan-annual-note">
                                Cobrado anualmente · <strong>{{ formatCLP(plan.price_annual) }}/año</strong>
                            </p>
                            <p v-else-if="plan.has_discount" class="plan-discount-note">
                                <span class="line-through opacity-60">{{ formatCLP(plan.price_monthly) }}</span>
                                <span class="ml-1 text-emerald-600 font-bold">−{{ plan.discount_percent }}%</span>
                            </p>
                            <!-- Annual savings badge -->
                            <div v-if="billing === 'annual'" class="plan-savings-pill">
                                Ahorra {{ annualSavings(plan) }}%
                            </div>
                        </div>

                        <!-- CTA -->
                        <Link v-if="$page.props.auth?.user" :href="route('dashboard')" :class="['plan-cta', plan.is_popular ? 'plan-cta-popular' : 'plan-cta-default']">
                            Ir al Dashboard
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </Link>
                        <Link v-else :href="route('trial.create')" :class="['plan-cta', plan.is_popular ? 'plan-cta-popular' : 'plan-cta-default']">
                            Empezar prueba gratis
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </Link>

                        <p class="plan-cta-note">{{ plan.trial_days }} días gratis · Sin tarjeta de crédito</p>

                        <!-- Divider -->
                        <div class="plan-divider"></div>

                        <!-- Features -->
                        <ul class="plan-features">
                            <li v-for="feature in plan.features" :key="feature" class="plan-feature">
                                <span :class="['plan-feature-ico', plan.is_popular ? 'plan-feature-ico-popular' : '']">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </span>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <!-- Footer meta -->
                        <div class="plan-meta">
                            <span class="plan-meta-tag">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                Hasta {{ plan.max_users }} usuario{{ plan.max_users !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!plans.length" class="plans-empty">
                    <p class="text-gray-400 text-sm">No hay planes disponibles en este momento.</p>
                </div>
            </div>
        </section>

        <!-- ======== FAQ / GARANTÍAS ======== -->
        <section class="guarantee-section">
            <div class="guarantee-inner">
                <h2 class="guarantee-title">Preguntas frecuentes</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3 class="faq-q">¿Necesito tarjeta de crédito para la prueba?</h3>
                        <p class="faq-a">No. Activas tu prueba gratuita de 14 días sin ingresar datos de pago. Solo necesitas tu correo y el nombre de tu taller.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Puedo cancelar cuando quiera?</h3>
                        <p class="faq-a">Sí. No hay contratos de permanencia. Puedes cancelar tu suscripción en cualquier momento desde tu panel de configuración.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Qué pasa al terminar la prueba?</h3>
                        <p class="faq-a">Te contactamos antes de que venza el período. Si decides continuar, eliges un plan y activas el pago. Si no, tu acceso se suspende sin cargos.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Puedo cambiar de plan?</h3>
                        <p class="faq-a">Sí. Puedes actualizar o bajar de plan en cualquier momento desde la sección de suscripción en tu taller.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Los precios incluyen IVA?</h3>
                        <p class="faq-a">Los precios mostrados son en CLP y no incluyen IVA. El cobro final puede incluir impuestos según tu situación tributaria.</p>
                    </div>
                    <div class="faq-item">
                        <h3 class="faq-q">¿Qué métodos de pago aceptan?</h3>
                        <p class="faq-a">Aceptamos tarjetas de crédito y débito a través de Mercado Pago. Próximamente transferencia bancaria directa.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======== BOTTOM CTA ======== -->
        <section class="bottom-cta-section">
            <div class="bottom-cta-inner">
                <div class="bottom-cta-card">
                    <div>
                        <h2 class="bottom-cta-title">¿Listo para digitalizar tu taller?</h2>
                        <p class="bottom-cta-sub">14 días gratis. Sin tarjeta de crédito. Cancela cuando quieras.</p>
                    </div>
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="bottom-cta-btn">
                        Ir al Dashboard
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </Link>
                    <Link v-else :href="route('trial.create')" class="bottom-cta-btn">
                        Empezar ahora gratis
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ======== FOOTER ======== -->
        <footer class="pricing-footer">
            <div class="pricing-footer-inner">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-7 w-7 rounded-lg" />
                    <span class="font-black text-gray-900 text-base">Feeto</span>
                </div>
                <nav class="flex items-center gap-6 flex-wrap justify-center">
                    <Link :href="route('home')" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Inicio</Link>
                    <Link :href="route('pricing')" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Precios</Link>
                    <Link :href="route('trial.create')" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Prueba gratis</Link>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Términos</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Privacidad</a>
                </nav>
                <p class="text-xs text-gray-400 font-medium">&copy; {{ new Date().getFullYear() }} Feeto. Todos los derechos reservados.</p>
            </div>
        </footer>
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
    padding: 118px 18px 80px;
}
.pricing-hero-inner {
    max-width: 760px;
    margin: 0 auto;
    text-align: center;
}

.pricing-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border: 1px solid rgba(255,181,71,0.28);
    border-radius: 999px;
    background: rgba(255,181,71,0.10);
    color: #FF7A00;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 24px;
}
.pricing-eyebrow .dot {
    width: 6px; height: 6px;
    border-radius: 999px;
    background: #FF7A00;
    box-shadow: 0 0 10px #FF7A00;
}

.pricing-headline {
    margin: 0 0 16px;
    color: #fff;
    font-size: clamp(36px, 6vw, 64px);
    font-weight: 700;
    line-height: 1.05;
    letter-spacing: -0.03em;
}
.pricing-headline em { color: #FF7A00; font-style: normal; }

.pricing-sub {
    color: rgba(255,255,255,0.56);
    font-size: 17px;
    line-height: 1.6;
    margin: 0 0 40px;
}

/* ── Billing toggle ──────────────────────── */
.billing-toggle-wrap {
    display: flex;
    justify-content: center;
}
.billing-toggle {
    display: inline-flex;
    padding: 4px;
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
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
    color: rgba(255,255,255,0.65);
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.billing-save-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    background: #FF7A00;
    color: #1a0e00;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.billing-btn-active .billing-save-badge {
    background: #FF7A00;
    color: #fff;
}

/* ── Plans section ───────────────────────── */
.plans-section {
    background: #f8f9fb;
    padding: 60px 18px 80px;
}
.plans-inner {
    max-width: 1200px;
    margin: 0 auto;
}

.plans-grid {
    display: grid;
    gap: 24px;
    align-items: start;
}
.plans-grid-1 { grid-template-columns: 1fr; max-width: 400px; margin: 0 auto; }
.plans-grid-2 { grid-template-columns: repeat(2, 1fr); }
.plans-grid-3 { grid-template-columns: repeat(3, 1fr); }

.plan-card {
    position: relative;
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 24px;
    padding: 32px;
    display: flex;
    flex-direction: column;
    gap: 0;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.plan-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.10);
    transform: translateY(-2px);
}

.plan-card-popular {
    border-color: #FF7A00;
    border-width: 2px;
    background: #fff;
    box-shadow: 0 8px 32px rgba(255,181,71,0.18), 0 2px 8px rgba(0,0,0,0.06);
}
.plan-card-popular:hover {
    box-shadow: 0 16px 48px rgba(255,181,71,0.24), 0 4px 16px rgba(0,0,0,0.08);
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
    box-shadow: 0 4px 12px rgba(255,181,71,0.40);
}

.plan-header { margin-bottom: 24px; }
.plan-name {
    font-size: 22px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
    margin: 0 0 8px;
}
.plan-desc {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.plan-price-block { margin-bottom: 24px; min-height: 80px; }
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
    font-size: 52px;
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
    box-shadow: 0 4px 16px rgba(255,181,71,0.35);
}
.plan-cta-popular:hover {
    background: #CC6200;
    box-shadow: 0 6px 20px rgba(255,181,71,0.45);
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
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.plans-empty {
    text-align: center;
    padding: 60px 0;
}

/* ── FAQ ─────────────────────────────────── */
.guarantee-section {
    background: #fff;
    padding: 72px 18px;
    border-top: 1px solid #f3f4f6;
}
.guarantee-inner {
    max-width: 960px;
    margin: 0 auto;
}
.guarantee-title {
    font-size: 28px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
    margin: 0 0 48px;
    text-align: center;
}
.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px 48px;
}
.faq-q {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px;
    line-height: 1.3;
}
.faq-a {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
}

/* ── Bottom CTA ──────────────────────────── */
.bottom-cta-section {
    background: #f8f9fb;
    padding: 60px 18px;
}
.bottom-cta-inner { max-width: 1200px; margin: 0 auto; }
.bottom-cta-card {
    background: #111827;
    border-radius: 28px;
    padding: 48px 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}
.bottom-cta-title {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 8px;
    line-height: 1.2;
}
.bottom-cta-sub {
    font-size: 14px;
    color: #9ca3af;
    margin: 0;
}
.bottom-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    padding: 16px 28px;
    border-radius: 16px;
    background: #FF7A00;
    color: #1a0e00;
    font-size: 15px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(255,181,71,0.35);
    transition: all 0.2s ease;
    white-space: nowrap;
}
.bottom-cta-btn:hover {
    background: #CC6200;
    box-shadow: 0 6px 24px rgba(255,181,71,0.45);
    transform: translateY(-1px);
}

/* ── Footer ──────────────────────────────── */
.pricing-footer {
    background: #fff;
    border-top: 1px solid #f3f4f6;
    padding: 32px 18px;
}
.pricing-footer-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

/* ── Responsive ──────────────────────────── */
@media (max-width: 900px) {
    .pricing-nav-links { display: none; }
    .btn-ghost-dark, .btn-accent-dark { display: none; }
    .mobile-toggle { display: flex; }
    .pricing-nav-wrap { padding: 12px 12px 0; }

    .plans-grid-2,
    .plans-grid-3 { grid-template-columns: 1fr; }

    .faq-grid { grid-template-columns: 1fr; gap: 24px; }

    .bottom-cta-card {
        padding: 32px 24px;
        flex-direction: column;
        text-align: center;
    }
    .bottom-cta-btn { width: 100%; justify-content: center; }

    .plan-amount { font-size: 42px; }
}

@media (max-width: 640px) {
    .pricing-hero { padding: 60px 0 60px; }
    .pricing-headline { font-size: 36px; }
    .pricing-footer-inner { flex-direction: column; text-align: center; }
}
</style>
