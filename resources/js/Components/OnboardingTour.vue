<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        required: true,
    },
});

const page = usePage();
const isVisible = ref(false);
const isSubmitting = ref(false);
const currentStepIndex = ref(0);
const activeSteps = ref([]);
const spotlightRect = ref(null);
const cardStyle = ref({});
const cardRef = ref(null);
const viewportMode = ref('desktop');
const vw = ref(0);
const vh = ref(0);


const TOUR_VARIANTS = {
    tenant: {
        desktop: [
            {
                selectors: ['[data-tour="tenant-navigation"]'],
                title: 'Navegacion principal',
                description: 'Desde aqui te mueves entre dashboard, ordenes, inventario, clientes y reportes sin salir del taller.',
            },
            {
                selectors: ['[data-tour="tenant-search"]'],
                title: 'Busqueda global',
                description: 'Usa este buscador para encontrar rapidamente patentes, ordenes y reportes.',
            },
            {
                selectors: ['[data-tour="tenant-notifications"]'],
                title: 'Notificaciones',
                description: 'Aqui veras avisos del taller, actividad reciente y recordatorios importantes.',
            },
            {
                selectors: ['[data-tour="tenant-settings"]'],
                title: 'Configuracion del taller',
                description: 'Administra usuarios, sucursales, roles y ajustes operativos desde esta seccion.',
            },
        ],
        mobile: [
            {
                selectors: ['[data-tour="tenant-mobile-search"]'],
                title: 'Busqueda movil',
                description: 'Busca patentes, ordenes o clientes desde cualquier pantalla del taller.',
            },
            {
                selectors: ['[data-tour="tenant-notifications"]'],
                title: 'Alertas a mano',
                description: 'Las notificaciones quedan arriba para que no se te pasen cambios o tareas pendientes.',
            },
            {
                selectors: ['[data-tour="tenant-mobile-navigation"]'],
                title: 'Accesos rapidos',
                description: 'Esta barra fija te deja cambiar de modulo sin perder el contexto actual.',
            },
            {
                selectors: ['[data-tour="tenant-mobile-settings"]'],
                title: 'Configuracion',
                description: 'Desde aqui entras a usuarios, sucursales y ajustes generales del taller.',
            },
        ],
    },
    dashboard: {
        common: [
            {
                selectors: ['[data-tour="dashboard-summary"]'],
                title: 'Resumen del día',
                description: 'Aqui ves de un vistazo las citas, movimientos del tablero y facturas atrasadas del taller.',
            },
            {
                selectors: ['[data-tour="dashboard-quicklinks"]'],
                title: 'Accesos rápidos',
                description: 'Desde aca saltas directo a recepcion, ordenes, inventario, servicios, clientes y reportes.',
            },
            {
                selectors: ['[data-tour="dashboard-agenda"]'],
                title: 'Agenda del taller',
                description: 'Aqui revisas y agendas las citas del taller para no perderte ninguna.',
            },
        ],
    },
    reception: {
        common: [
            {
                selectors: ['[data-tour="reception-new-entry"]'],
                title: 'Nueva recepción',
                description: 'Toca aqui para iniciar un nuevo ingreso registrando la patente del vehiculo.',
            },
        ],
    },
    'work-orders': {
        common: [
            {
                selectors: ['[data-tour="work-orders-view-switch"]'],
                title: 'Tablero o listado',
                description: 'Cambia entre vista de tablero Kanban y listado segun como prefieras trabajar.',
            },
            {
                selectors: ['[data-tour="work-orders-search"]'],
                title: 'Búsqueda rápida',
                description: 'Busca rapido una orden por patente, numero de OT o cliente.',
            },
        ],
    },
    quotes: {
        common: [
            {
                selectors: ['[data-tour="quotes-new"]'],
                title: 'Nueva cotización',
                description: 'Crea una cotizacion para un cliente sin necesidad de una cita ni una orden.',
            },
            {
                selectors: ['[data-tour="quotes-search"]'],
                title: 'Búsqueda de cotizaciones',
                description: 'Busca cotizaciones existentes por cliente, RUT o patente.',
            },
        ],
    },
    inventory: {
        common: [
            {
                selectors: ['[data-tour="inventory-add"]'],
                title: 'Agregar repuesto',
                description: 'Agrega un nuevo repuesto o insumo a tu inventario.',
            },
            {
                selectors: ['[data-tour="inventory-search"]'],
                title: 'Búsqueda de repuestos',
                description: 'Busca repuestos rapido por nombre o SKU.',
            },
        ],
    },
    services: {
        common: [
            {
                selectors: ['[data-tour="services-add"]'],
                title: 'Nuevo servicio',
                description: 'Crea un nuevo servicio para ofrecer en tus cotizaciones y ordenes.',
            },
        ],
    },
    clients: {
        common: [
            {
                selectors: ['[data-tour="clients-add"]'],
                title: 'Nuevo cliente',
                description: 'Registra un nuevo cliente en tu base de datos.',
            },
            {
                selectors: ['[data-tour="clients-table"]'],
                title: 'Historial y CRM',
                description: 'Aqui ves el historial y las señales CRM de cada cliente.',
            },
        ],
    },
    reports: {
        common: [
            {
                selectors: ['[data-tour="reports-summary"]'],
                title: 'Resumen de actividad',
                description: 'Aqui ves un resumen rapido de la actividad comercial y operativa del taller.',
            },
            {
                selectors: ['[data-tour="reports-grid"]'],
                title: 'Reportes disponibles',
                description: 'Elige un reporte para profundizar en ventas, stock, clientes o cobranza.',
            },
        ],
    },
    'subscription-plans': {
        common: [
            {
                selectors: ['[data-tour="subscription-plans-toggle"]'],
                title: 'Mensual o anual',
                description: 'Cambia entre pago mensual o anual para ver el ahorro.',
            },
            {
                selectors: ['[data-tour="subscription-plans-grid"]'],
                title: 'Compara planes',
                description: 'Compara los planes disponibles y elige el que mejor se ajuste a tu taller.',
            },
        ],
    },
    'subscription-billing': {
        common: [
            {
                selectors: ['[data-tour="billing-summary"]'],
                title: 'Resumen de pagos',
                description: 'Aqui ves cuanto has pagado en total y cuando se renueva tu plan.',
            },
            {
                selectors: ['[data-tour="billing-transactions"]'],
                title: 'Historial de transacciones',
                description: 'Revisa el historial completo de tus pagos y su estado.',
            },
        ],
    },
    settings: {
        common: [
            {
                selectors: ['[data-tour="settings-tabs"]'],
                title: 'Secciones de configuración',
                description: 'Desde aca cambias entre usuarios, sucursales y ajustes comerciales del taller.',
            },
        ],
    },
};

const isGlobalVariant = computed(() => props.variant === 'tenant');

const currentStep = computed(() => activeSteps.value[currentStepIndex.value] ?? null);
const shouldShowTour = computed(() => {
    if (isGlobalVariant.value) {
        return Boolean(page.props.onboarding?.show_tour);
    }

    // Los tours de sección esperan a que termine (o no aplique) el tour general
    // para no mostrar dos overlays superpuestos en la primera visita.
    if (page.props.onboarding?.show_tour) {
        return false;
    }

    const completedSections = page.props.onboarding?.completed_sections ?? [];

    return !completedSections.includes(props.variant);
});
const isLastStep = computed(() => currentStepIndex.value === activeSteps.value.length - 1);

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const updateViewportMode = () => {
    viewportMode.value = window.innerWidth < 1024 ? 'mobile' : 'desktop';
    vw.value = window.innerWidth;
    vh.value = window.innerHeight;
};

const isElementVisible = (element) => {
    if (!element) {
        return false;
    }

    const rect = element.getBoundingClientRect();

    return rect.width > 0 && rect.height > 0;
};

const resolveElement = (step) => {
    if (!step?.selectors?.length) {
        return null;
    }

    for (const selector of step.selectors) {
        const candidate = document.querySelector(selector);

        if (isElementVisible(candidate)) {
            return candidate;
        }
    }

    return null;
};

const updateSpotlight = async () => {
    const step = currentStep.value;
    const element = resolveElement(step);

    if (!step || !element) {
        spotlightRect.value = null;
        cardStyle.value = {};

        return;
    }

    await nextTick();

    vw.value = window.innerWidth;
    vh.value = window.innerHeight;

    const rect = element.getBoundingClientRect();
    const padding = 12;

    spotlightRect.value = {
        x: Math.max(rect.left - padding, 0),
        y: Math.max(rect.top - padding, 0),
        w: rect.width + padding * 2,
        h: rect.height + padding * 2,
        rx: 14,
    };

    const CARD_WIDTH = 340;
    const cardHeight = cardRef.value?.offsetHeight ?? 200;
    const MOBILE_BREAKPOINT = 1024;
    const isMobile = window.innerWidth < MOBILE_BREAKPOINT;

    if (isMobile) {
        // En mobile: siempre bottom-sheet fijo, nunca encima del spotlight
        cardStyle.value = {
            position: 'fixed',
            left: '12px',
            right: '12px',
            bottom: '16px',
        };

        return;
    }

    // Desktop: posicionar fuera del spotlight (abajo → arriba → derecha → izquierda)
    const spotBottom = rect.bottom + padding;
    const spotTop = rect.top - padding;
    const spotLeft = rect.left - padding;
    const spotRight = rect.right + padding;
    const cardW = CARD_WIDTH;
    const gap = 20;

    let top, left;

    if (spotBottom + cardHeight + gap < window.innerHeight - 16) {
        // Debajo del elemento
        top = spotBottom + gap;
        left = clamp(rect.left + rect.width / 2 - cardW / 2, 16, window.innerWidth - cardW - 16);
    } else if (spotTop - cardHeight - gap > 16) {
        // Arriba del elemento
        top = spotTop - cardHeight - gap;
        left = clamp(rect.left + rect.width / 2 - cardW / 2, 16, window.innerWidth - cardW - 16);
    } else if (spotRight + cardW + gap < window.innerWidth - 16) {
        // A la derecha
        top = clamp(rect.top + rect.height / 2 - cardHeight / 2, 16, window.innerHeight - cardHeight - 16);
        left = spotRight + gap;
    } else {
        // A la izquierda
        top = clamp(rect.top + rect.height / 2 - cardHeight / 2, 16, window.innerHeight - cardHeight - 16);
        left = Math.max(spotLeft - cardW - gap, 16);
    }

    cardStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${cardW}px`,
    };
};

const rebuildSteps = async () => {
    const variantConfig = TOUR_VARIANTS[props.variant] ?? {};
    const steps = variantConfig[viewportMode.value] ?? variantConfig.common ?? [];

    activeSteps.value = steps.filter((step) => resolveElement(step) !== null);

    if (!activeSteps.value.length) {
        isVisible.value = false;

        return;
    }

    currentStepIndex.value = clamp(currentStepIndex.value, 0, activeSteps.value.length - 1);
    await updateSpotlight();
};

const finishTour = async () => {
    if (isSubmitting.value) {
        return;
    }

    isVisible.value = false;
    isSubmitting.value = true;

    try {
        await window.axios.post(
            route('onboarding-tour.complete'),
            isGlobalVariant.value ? {} : { section: props.variant },
        );
    } catch (error) {
        console.error('Unable to persist onboarding tour state.', error);
    } finally {
        isSubmitting.value = false;
    }
};

const nextStep = async () => {
    if (isLastStep.value) {
        await finishTour();

        return;
    }

    currentStepIndex.value += 1;
    await updateSpotlight();
};

const previousStep = async () => {
    if (currentStepIndex.value === 0) {
        return;
    }

    currentStepIndex.value -= 1;
    await updateSpotlight();
};

const handleLayoutChange = async () => {
    updateViewportMode();

    if (!isVisible.value) {
        return;
    }

    await rebuildSteps();
};



onMounted(async () => {
    updateViewportMode();

    if (!shouldShowTour.value) {
        return;
    }

    currentStepIndex.value = 0;
    isVisible.value = true;

    await rebuildSteps();

    window.addEventListener('resize', handleLayoutChange);
    window.addEventListener('scroll', updateSpotlight, { passive: true, capture: true });
});

watch(shouldShowTour, async (value) => {
    if (value) {
        return;
    }

    isVisible.value = false;
});



watch(currentStep, async () => {
    if (!isVisible.value) {
        return;
    }

    await updateSpotlight();
});

watch(() => page.url, (newUrl, oldUrl) => {
    if (newUrl !== oldUrl && isVisible.value) {
        finishTour();
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleLayoutChange);
    window.removeEventListener('scroll', updateSpotlight, { capture: true });
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isVisible && currentStep" class="pointer-events-none fixed inset-0 z-[120]">

            <!-- Overlay SVG con recorte limpio del spotlight -->
            <svg
                class="absolute inset-0 h-full w-full"
                :viewBox="`0 0 ${vw} ${vh}`"
                preserveAspectRatio="none"
            >
                <defs>
                    <mask id="tour-spotlight-mask">
                        <!-- Fondo blanco = visible (área oscura del overlay) -->
                        <rect width="100%" height="100%" fill="white" />
                        <!-- Recorte negro = transparente (zona iluminada) -->
                        <rect
                            v-if="spotlightRect"
                            :x="spotlightRect.x"
                            :y="spotlightRect.y"
                            :width="spotlightRect.w"
                            :height="spotlightRect.h"
                            :rx="spotlightRect.rx"
                            fill="black"
                        />
                    </mask>
                </defs>
                <!-- Overlay oscuro con el recorte aplicado -->
                <rect
                    width="100%"
                    height="100%"
                    fill="rgba(2,6,23,0.72)"
                    mask="url(#tour-spotlight-mask)"
                />
            </svg>

            <!-- Borde naranja alrededor del elemento destacado -->
            <div
                v-if="spotlightRect"
                class="pointer-events-none absolute rounded-2xl ring-2 ring-[#FF7A00] ring-offset-0 transition-all duration-300"
                :style="{
                    top: spotlightRect.y + 'px',
                    left: spotlightRect.x + 'px',
                    width: spotlightRect.w + 'px',
                    height: spotlightRect.h + 'px',
                }"
            />

            <!-- Card: siempre encima, nunca se mezcla con el overlay -->
            <div
                ref="cardRef"
                class="pointer-events-auto absolute z-10 rounded-2xl bg-white shadow-[0_20px_60px_rgba(2,6,23,0.30)] transition-all duration-300"
                :style="cardStyle"
            >
                <!-- Cabecera -->
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span
                            v-for="(_, index) in activeSteps"
                            :key="index"
                            class="block rounded-full transition-all duration-300"
                            :class="index === currentStepIndex
                                ? 'h-2 w-5 bg-[#FF7A00]'
                                : 'h-2 w-2 bg-slate-200'"
                        />
                    </div>

                    <button
                        type="button"
                        class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                        @click="finishTour"
                    >
                        Omitir tour
                    </button>
                </div>

                <!-- Cuerpo -->
                <div class="px-6 py-5">
                    <div class="mb-1 text-[11px] font-bold uppercase tracking-widest text-[#FF7A00]">
                        Paso {{ currentStepIndex + 1 }} de {{ activeSteps.length }}
                    </div>

                    <h3 class="text-lg font-black tracking-tight text-slate-900">
                        {{ currentStep.title }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        {{ currentStep.description }}
                    </p>
                </div>

                <!-- Acciones -->
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-500 transition-colors hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="currentStepIndex === 0"
                        @click="previousStep"
                    >
                        ← Anterior
                    </button>

                    <button
                        type="button"
                        class="rounded-xl bg-[#FF7A00] px-6 py-2.5 text-sm font-black text-white shadow-[0_6px_20px_rgba(255,122,0,0.35)] transition-all hover:-translate-y-0.5 hover:bg-orange-500 hover:shadow-[0_10px_28px_rgba(255,122,0,0.42)] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isSubmitting"
                        @click="nextStep"
                    >
                        {{ isLastStep ? '¡Listo! →' : 'Siguiente →' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
