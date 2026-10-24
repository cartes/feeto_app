<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { TOUR_VARIANTS } from '@/Components/onboardingTourSteps';
import { ONBOARDING_TOUR_REPLAY_EVENT } from '@/Components/onboardingTourEvents';

const props = defineProps({
    variant: {
        type: String,
        required: true,
    },
});

const page = usePage();
const isVisible = ref(false);
const currentStepIndex = ref(0);
const activeSteps = ref([]);
const spotlightRect = ref(null);
const cardStyle = ref({});
const cardRef = ref(null);
const nextButtonRef = ref(null);
const viewportMode = ref('desktop');
const vw = ref(0);
const vh = ref(0);

const CARD_WIDTH = 340;
const MOBILE_BREAKPOINT = 1024;
const MAX_START_ATTEMPTS = 5;
const START_RETRY_MS = 400;
const maskId = `tour-spotlight-mask-${props.variant}`;

let hasPersisted = false;

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
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const updateViewportMode = () => {
    viewportMode.value = window.innerWidth < MOBILE_BREAKPOINT ? 'mobile' : 'desktop';
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

const isStepAvailable = (step) => !step.selectors?.length || resolveElement(step) !== null;

const scrollIntoViewIfNeeded = (element) => {
    const rect = element.getBoundingClientRect();

    if (rect.top >= 0 && rect.bottom <= window.innerHeight) {
        return;
    }

    element.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'auto' });
};

const centerCard = () => {
    spotlightRect.value = null;
    cardStyle.value = {
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        width: `min(${CARD_WIDTH}px, calc(100vw - 24px))`,
    };
};

const updateSpotlight = async () => {
    const step = currentStep.value;

    if (!step) {
        spotlightRect.value = null;
        cardStyle.value = {};

        return;
    }

    if (!step.selectors?.length) {
        centerCard();

        return;
    }

    const element = resolveElement(step);

    if (!element) {
        spotlightRect.value = null;
        cardStyle.value = {};

        return;
    }

    scrollIntoViewIfNeeded(element);
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

    const cardHeight = cardRef.value?.offsetHeight ?? 200;

    if (window.innerWidth < MOBILE_BREAKPOINT) {
        // En mobile: bottom-sheet; si el elemento está abajo (barra de navegación), la tarjeta sube.
        const isTargetInLowerHalf = rect.top + rect.height / 2 > window.innerHeight / 2;

        cardStyle.value = {
            position: 'fixed',
            left: '12px',
            right: '12px',
            ...(isTargetInLowerHalf ? { top: '16px' } : { bottom: '16px' }),
        };

        return;
    }

    // Desktop: posicionar fuera del spotlight (abajo → arriba → derecha → izquierda)
    const spotBottom = rect.bottom + padding;
    const spotTop = rect.top - padding;
    const spotLeft = rect.left - padding;
    const spotRight = rect.right + padding;
    const gap = 20;

    let top;
    let left;

    if (spotBottom + cardHeight + gap < window.innerHeight - 16) {
        top = spotBottom + gap;
        left = clamp(rect.left + rect.width / 2 - CARD_WIDTH / 2, 16, window.innerWidth - CARD_WIDTH - 16);
    } else if (spotTop - cardHeight - gap > 16) {
        top = spotTop - cardHeight - gap;
        left = clamp(rect.left + rect.width / 2 - CARD_WIDTH / 2, 16, window.innerWidth - CARD_WIDTH - 16);
    } else if (spotRight + CARD_WIDTH + gap < window.innerWidth - 16) {
        top = clamp(rect.top + rect.height / 2 - cardHeight / 2, 16, window.innerHeight - cardHeight - 16);
        left = spotRight + gap;
    } else {
        top = clamp(rect.top + rect.height / 2 - cardHeight / 2, 16, window.innerHeight - cardHeight - 16);
        left = Math.max(spotLeft - CARD_WIDTH - gap, 16);
    }

    cardStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${CARD_WIDTH}px`,
    };
};

const buildSteps = () => {
    const variantConfig = TOUR_VARIANTS[props.variant] ?? {};
    const steps = variantConfig[viewportMode.value] ?? variantConfig.common ?? [];

    activeSteps.value = steps.filter(isStepAvailable);

    return activeSteps.value.length > 0;
};

const rebuildSteps = async () => {
    if (!buildSteps()) {
        isVisible.value = false;

        return;
    }

    currentStepIndex.value = clamp(currentStepIndex.value, 0, activeSteps.value.length - 1);
    await updateSpotlight();
};

/**
 * Se registra al mostrarse (no al terminar): quien vio u omitió el tour, aunque recargue
 * o cierre la pestaña a mitad de camino, no lo vuelve a ver.
 */
const persistSeen = async () => {
    if (hasPersisted) {
        return;
    }

    hasPersisted = true;

    try {
        await window.axios.post(
            route('onboarding-tour.complete'),
            isGlobalVariant.value ? {} : { section: props.variant },
        );
    } catch (error) {
        hasPersisted = false;
        console.error('Unable to persist onboarding tour state.', error);
    }
};

const focusNextButton = async () => {
    await nextTick();
    nextButtonRef.value?.focus({ preventScroll: true });
};

const startTour = async () => {
    for (let attempt = 0; attempt < MAX_START_ATTEMPTS; attempt++) {
        if (buildSteps()) {
            currentStepIndex.value = 0;
            isVisible.value = true;
            await updateSpotlight();
            persistSeen();
            focusNextButton();

            return;
        }

        await sleep(START_RETRY_MS);
    }
};

const finishTour = () => {
    isVisible.value = false;
    persistSeen();
};

const goToStep = async (index) => {
    currentStepIndex.value = index;
    await updateSpotlight();
    focusNextButton();
};

const nextStep = async () => {
    if (isLastStep.value) {
        finishTour();

        return;
    }

    await goToStep(currentStepIndex.value + 1);
};

const previousStep = async () => {
    if (currentStepIndex.value === 0) {
        return;
    }

    await goToStep(currentStepIndex.value - 1);
};

const handleLayoutChange = async () => {
    updateViewportMode();

    if (!isVisible.value) {
        return;
    }

    await rebuildSteps();
};

const handleScroll = () => {
    if (isVisible.value) {
        updateSpotlight();
    }
};

const handleReplay = async (event) => {
    if (event.detail?.variant !== props.variant || isVisible.value) {
        return;
    }

    updateViewportMode();
    hasPersisted = true;
    await startTour();
};

const handleKeydown = (event) => {
    if (!isVisible.value) {
        return;
    }

    if (event.key === 'Escape') {
        finishTour();
    } else if (event.key === 'ArrowRight') {
        nextStep();
    } else if (event.key === 'ArrowLeft') {
        previousStep();
    }
};

onMounted(async () => {
    updateViewportMode();

    window.addEventListener('resize', handleLayoutChange);
    window.addEventListener('scroll', handleScroll, { passive: true, capture: true });
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener(ONBOARDING_TOUR_REPLAY_EVENT, handleReplay);

    if (shouldShowTour.value) {
        await startTour();
    }
});

watch(shouldShowTour, (value) => {
    if (!value) {
        isVisible.value = false;
    }
});

watch(currentStep, async () => {
    if (isVisible.value) {
        await updateSpotlight();
    }
});

watch(() => page.url, (newUrl, oldUrl) => {
    if (newUrl !== oldUrl && isVisible.value) {
        finishTour();
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleLayoutChange);
    window.removeEventListener('scroll', handleScroll, { capture: true });
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener(ONBOARDING_TOUR_REPLAY_EVENT, handleReplay);
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
        <div v-if="isVisible && currentStep" class="fixed inset-0 z-[120]" role="dialog" aria-modal="true" :aria-label="`Tour de bienvenida: ${currentStep.title}`">

            <!-- Overlay SVG con recorte limpio del spotlight -->
            <svg
                class="pointer-events-auto absolute inset-0 h-full w-full"
                :viewBox="`0 0 ${vw} ${vh}`"
                preserveAspectRatio="none"
            >
                <defs>
                    <mask :id="maskId">
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
                    :mask="`url(#${maskId})`"
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
                        ref="nextButtonRef"
                        type="button"
                        class="rounded-xl bg-[#FF7A00] px-6 py-2.5 text-sm font-black text-white shadow-[0_6px_20px_rgba(255,122,0,0.35)] transition-all hover:-translate-y-0.5 hover:bg-orange-500 hover:shadow-[0_10px_28px_rgba(255,122,0,0.42)] disabled:cursor-not-allowed disabled:opacity-60"
                                                @click="nextStep"
                    >
                        {{ isLastStep ? '¡Listo! →' : 'Siguiente →' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
