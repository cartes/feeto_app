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
const spotlightStyle = ref({});
const cardStyle = ref({});
const cardRef = ref(null);
const viewportMode = ref('desktop');

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
    admin: {
        desktop: [
            {
                selectors: ['[data-tour="admin-navigation"]'],
                title: 'Panel de administracion',
                description: 'Este menu concentra el acceso a talleres, usuarios, planes, pagos, auditoria y SEO.',
            },
            {
                selectors: ['[data-tour="admin-blog"]'],
                title: 'Contenido y medios',
                description: 'Desde Blog gestionas articulos, categorias y el banco de imagenes del sitio.',
            },
            {
                selectors: ['[data-tour="admin-user-menu"]'],
                title: 'Perfil y salida',
                description: 'Aqui tienes tu perfil de administrador y la salida segura de la sesion.',
            },
        ],
        mobile: [
            {
                selectors: ['[data-tour="admin-brand"]'],
                title: 'Vista administrativa',
                description: 'Estas dentro del panel maestro para operar la plataforma completa.',
            },
            {
                selectors: ['[data-tour="admin-mobile-menu"]'],
                title: 'Menu movil',
                description: 'Abre este boton para navegar entre talleres, planes, pagos y el resto de modulos.',
            },
            {
                selectors: ['[data-tour="admin-content"]'],
                title: 'Zona de trabajo',
                description: 'El contenido principal cambia segun el modulo, pero siempre se renderiza aqui.',
            },
        ],
    },
};

const currentStep = computed(() => activeSteps.value[currentStepIndex.value] ?? null);
const shouldShowTour = computed(() => Boolean(page.props.onboarding?.show_tour));
const isLastStep = computed(() => currentStepIndex.value === activeSteps.value.length - 1);

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const updateViewportMode = () => {
    viewportMode.value = window.innerWidth < 1024 ? 'mobile' : 'desktop';
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
        spotlightStyle.value = {};
        cardStyle.value = {};

        return;
    }

    element.scrollIntoView({
        behavior: 'smooth',
        block: viewportMode.value === 'mobile' ? 'center' : 'nearest',
        inline: 'nearest',
    });

    await nextTick();

    const rect = element.getBoundingClientRect();
    const padding = viewportMode.value === 'mobile' ? 10 : 12;
    const cardWidth = viewportMode.value === 'mobile'
        ? Math.min(window.innerWidth - 32, 360)
        : 340;
    const cardHeight = cardRef.value?.offsetHeight ?? 224;

    spotlightStyle.value = {
        top: `${Math.max(rect.top - padding, 8)}px`,
        left: `${Math.max(rect.left - padding, 8)}px`,
        width: `${rect.width + (padding * 2)}px`,
        height: `${rect.height + (padding * 2)}px`,
    };

    if (viewportMode.value === 'mobile') {
        cardStyle.value = {
            left: '16px',
            right: '16px',
            bottom: '16px',
        };

        return;
    }

    const fitsBelow = rect.bottom + 28 + cardHeight < window.innerHeight - 16;
    const preferredTop = fitsBelow
        ? rect.bottom + 20
        : rect.top - cardHeight - 20;

    cardStyle.value = {
        top: `${clamp(preferredTop, 16, Math.max(window.innerHeight - cardHeight - 16, 16))}px`,
        left: `${clamp(rect.left + (rect.width / 2) - (cardWidth / 2), 16, Math.max(window.innerWidth - cardWidth - 16, 16))}px`,
        width: `${cardWidth}px`,
    };
};

const rebuildSteps = async () => {
    const steps = TOUR_VARIANTS[props.variant]?.[viewportMode.value] ?? [];

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
        await window.axios.post(route('onboarding-tour.complete'));
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

const lockScroll = (locked) => {
    document.documentElement.style.overflow = locked ? 'hidden' : '';
    document.body.style.overflow = locked ? 'hidden' : '';
};

onMounted(async () => {
    updateViewportMode();

    if (!shouldShowTour.value) {
        return;
    }

    currentStepIndex.value = 0;
    isVisible.value = true;
    lockScroll(true);

    await rebuildSteps();

    window.addEventListener('resize', handleLayoutChange);
    window.addEventListener('scroll', updateSpotlight, true);
});

watch(shouldShowTour, async (value) => {
    if (value) {
        return;
    }

    isVisible.value = false;
});

watch(isVisible, (value) => {
    lockScroll(value);
});

watch(currentStep, async () => {
    if (!isVisible.value) {
        return;
    }

    await updateSpotlight();
});

onBeforeUnmount(() => {
    lockScroll(false);
    window.removeEventListener('resize', handleLayoutChange);
    window.removeEventListener('scroll', updateSpotlight, true);
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
        <div v-if="isVisible && currentStep" class="fixed inset-0 z-[120]">
            <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]" />

            <div
                class="pointer-events-none absolute rounded-[2rem] border-2 border-[#FF7A00] bg-transparent shadow-[0_0_0_9999px_rgba(15,23,42,0.62)] transition-all duration-300"
                :style="spotlightStyle"
            />

            <div
                ref="cardRef"
                class="absolute rounded-[2rem] border border-white/70 bg-white/96 p-6 text-slate-900 shadow-[0_24px_80px_rgba(15,23,42,0.28)]"
                :style="cardStyle"
            >
                <div class="flex items-center justify-between gap-4">
                    <span class="rounded-full bg-[#FF7A00]/10 px-3 py-1 text-[11px] font-black uppercase tracking-[0.24em] text-[#FF7A00]">
                        Paso {{ currentStepIndex + 1 }} / {{ activeSteps.length }}
                    </span>

                    <button
                        type="button"
                        class="text-sm font-semibold text-slate-400 transition-colors hover:text-slate-600"
                        @click="finishTour"
                    >
                        Omitir
                    </button>
                </div>

                <div class="mt-5 space-y-3">
                    <h3 class="text-2xl font-black tracking-tight text-slate-950">
                        {{ currentStep.title }}
                    </h3>
                    <p class="text-sm font-medium leading-6 text-slate-600">
                        {{ currentStep.description }}
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-500 transition-colors hover:border-slate-300 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="currentStepIndex === 0"
                        @click="previousStep"
                    >
                        Anterior
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl bg-[#FF7A00] px-5 py-3 text-sm font-black text-white shadow-[0_12px_30px_rgba(255,122,0,0.28)] transition-transform hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isSubmitting"
                        @click="nextStep"
                    >
                        {{ isLastStep ? 'Finalizar' : 'Siguiente' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
