<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    offerPrice: { type: Number, default: 19990 },
    deadlineDate: { type: String, default: '2026-12-31T23:59:59' },
    deadlineLabel: { type: String, default: '31 de diciembre' },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['scrollToPlans']);

// Formato chileno de moneda
const formatCLP = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(value);

// Cuenta regresiva dinámica
const timeLeft = ref({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
    expired: false,
});

let timer = null;

const calculateTimeLeft = () => {
    const target = new Date(props.deadlineDate).getTime();
    const now = new Date().getTime();
    const difference = target - now;

    if (difference <= 0) {
        timeLeft.value = { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true };
        if (timer) clearInterval(timer);
        return;
    }

    timeLeft.value = {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60),
        expired: false,
    };
};

onMounted(() => {
    calculateTimeLeft();
    timer = setInterval(calculateTimeLeft, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const scrollToCards = () => {
    emit('scrollToPlans');
    const el = document.getElementById('planes-grid') || document.querySelector('.plans-section');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <div
        id="oferta-navidena"
        class="christmas-banner-wrapper relative overflow-hidden rounded-3xl border border-red-500/40 shadow-2xl transition-all duration-300"
    >
        <!-- Luces y brillo ambiental navideño -->
        <div class="pointer-events-none absolute -top-24 -left-24 w-80 h-80 rounded-full bg-red-600/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-emerald-600/25 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full bg-amber-500/10 blur-3xl"></div>

        <!-- Copos de nieve decorativos en SVG -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-30 select-none">
            <span class="snowflake s-1">❄</span>
            <span class="snowflake s-2">❅</span>
            <span class="snowflake s-3">❆</span>
            <span class="snowflake s-4">❄</span>
            <span class="snowflake s-5">❅</span>
            <span class="snowflake s-6">❆</span>
            <span class="snowflake s-7">❄</span>
        </div>

        <div class="relative z-10 p-6 md:p-10 lg:p-12 text-white">
            <!-- Header del Banner -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-red-600/40 to-amber-500/30 border border-amber-400/40 text-amber-200 text-xs font-bold uppercase tracking-wider shadow-inner">
                    <span class="animate-pulse">🎄</span>
                    <span>Oferta Navideña TallerFlow</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span class="text-white/80 font-normal">Hasta el {{ deadlineLabel }}</span>
                </div>

                <!-- Tag de ahorro masivo -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">
                    <span>🎁</span>
                    <span>Ahorra hasta $60.000 al mes (75% OFF)</span>
                </div>
            </div>

            <!-- Contenido Principal en dos columnas -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Columna Izquierda: Mensaje y propuesta de valor -->
                <div class="lg:col-span-7 space-y-4">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight">
                        Todas las funcionalidades por 1 año a
                        <span class="bg-gradient-to-r from-amber-300 via-amber-200 to-amber-400 bg-clip-text text-transparent">
                            {{ formatCLP(offerPrice) }}
                        </span>
                        <span class="text-lg md:text-2xl font-bold text-white/80">/mes</span>
                    </h2>

                    <p class="text-sm md:text-base text-gray-200 leading-relaxed">
                        Celebra esta Navidad equipando tu taller mecánico con la plataforma más avanzada.
                        Todos los talleres que contraten hasta el <strong>31 de diciembre</strong> tendrán acceso completo
                        al <strong>Plan Empresa</strong> (Recepción con IA de patentes, WhatsApp automático, agenda, inventario y usuarios extendidos)
                        por un valor único y congelado de <strong>{{ formatCLP(offerPrice) }} al mes durante 1 año</strong>.
                    </p>

                    <!-- Beneficios clave en badges -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <span class="text-xl">🚀</span>
                            <div class="text-xs">
                                <div class="font-bold text-white">100% Funcionalidades</div>
                                <div class="text-white/70">Plan Empresa desbloqueado</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <span class="text-xl">🔒</span>
                            <div class="text-xs">
                                <div class="font-bold text-white">Precio Congelado</div>
                                <div class="text-white/70">12 meses a {{ formatCLP(offerPrice) }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                            <span class="text-xl">✨</span>
                            <div class="text-xs">
                                <div class="font-bold text-white">14 Días Gratis</div>
                                <div class="text-white/70">Sin tarjeta de crédito</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Countdown y CTA box -->
                <div class="lg:col-span-5 flex flex-col items-center lg:items-end">
                    <div class="w-full max-w-md rounded-2xl bg-black/40 border border-white/15 p-6 backdrop-blur-md shadow-xl text-center space-y-5">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-300 flex items-center justify-center gap-1.5">
                                <span>⏳</span> Tiempo restante de la oferta
                            </span>
                            <p class="text-[11px] text-white/60 mt-0.5">Válido para registros hasta el 31 de Diciembre</p>
                        </div>

                        <!-- Reloj dinámico -->
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="p-2.5 rounded-xl bg-white/10 border border-white/10">
                                <div class="text-2xl md:text-3xl font-black text-amber-300 font-mono">
                                    {{ String(timeLeft.days).padStart(2, '0') }}
                                </div>
                                <div class="text-[10px] font-bold text-white/70 uppercase tracking-wider mt-0.5">Días</div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white/10 border border-white/10">
                                <div class="text-2xl md:text-3xl font-black text-white font-mono">
                                    {{ String(timeLeft.hours).padStart(2, '0') }}
                                </div>
                                <div class="text-[10px] font-bold text-white/70 uppercase tracking-wider mt-0.5">Horas</div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white/10 border border-white/10">
                                <div class="text-2xl md:text-3xl font-black text-white font-mono">
                                    {{ String(timeLeft.minutes).padStart(2, '0') }}
                                </div>
                                <div class="text-[10px] font-bold text-white/70 uppercase tracking-wider mt-0.5">Min</div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white/10 border border-white/10">
                                <div class="text-2xl md:text-3xl font-black text-amber-400 font-mono animate-pulse">
                                    {{ String(timeLeft.seconds).padStart(2, '0') }}
                                </div>
                                <div class="text-[10px] font-bold text-white/70 uppercase tracking-wider mt-0.5">Seg</div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="space-y-2.5 pt-1">
                            <Link
                                :href="route('trial.create')"
                                class="w-full inline-flex items-center justify-center gap-2 py-3 px-6 rounded-xl bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm tracking-wide transition-all shadow-lg hover:shadow-amber-500/25 transform hover:-translate-y-0.5"
                            >
                                <span>🎁 Aprovechar Oferta Navideña</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>

                            <button
                                type="button"
                                @click="scrollToCards"
                                class="w-full py-2 px-4 rounded-xl text-white/80 hover:text-white text-xs font-semibold hover:bg-white/5 transition-colors flex items-center justify-center gap-1.5"
                            >
                                <span>Ver detalles en las tarjetas de planes</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </div>

                        <p class="text-[11px] text-white/60">
                            Sin compromisos. 14 días gratis sin ingresar tarjeta de crédito.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.christmas-banner-wrapper {
    background: radial-gradient(120% 120% at 20% 10%, #7f1d1d 0%, #450a0a 45%, #052e16 95%);
}

/* Copos de nieve decorativos */
.snowflake {
    position: absolute;
    color: rgba(255, 255, 255, 0.4);
    font-size: 1.25rem;
    animation-name: snowfall;
    animation-iteration-count: infinite;
    animation-timing-function: linear;
}

.s-1 { left: 8%; top: -10px; font-size: 1.4rem; animation-duration: 9s; animation-delay: 0s; }
.s-2 { left: 22%; top: -10px; font-size: 0.9rem; animation-duration: 12s; animation-delay: 2s; }
.s-3 { left: 45%; top: -10px; font-size: 1.6rem; animation-duration: 8s; animation-delay: 1s; }
.s-4 { left: 63%; top: -10px; font-size: 1.1rem; animation-duration: 10s; animation-delay: 3s; }
.s-5 { left: 78%; top: -10px; font-size: 1.5rem; animation-duration: 13s; animation-delay: 1.5s; }
.s-6 { left: 90%; top: -10px; font-size: 0.8rem; animation-duration: 11s; animation-delay: 4s; }
.s-7 { left: 35%; top: -10px; font-size: 1.2rem; animation-duration: 14s; animation-delay: 2.5s; }

@keyframes snowfall {
    0% {
        transform: translateY(-10px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 0.6;
    }
    90% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(320px) rotate(360deg);
        opacity: 0;
    }
}
</style>
