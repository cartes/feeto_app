<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useTenantRouting } from '@/composables/useTenantRouting';

const props = defineProps({
    section: {
        type: String,
        default: null,
    },
    sectionLabel: {
        type: String,
        default: '',
    },
});

const { tenantRoute } = useTenantRouting();

const SECTION_LABELS = {
    dashboard: 'Dashboard',
    reception: 'Recepción',
    'work-orders': 'Órdenes de trabajo',
    quotes: 'Cotizaciones',
    inventory: 'Inventario',
    services: 'Servicios',
    clients: 'Clientes',
    reports: 'Reportes',
    settings: 'Configuración',
    'subscription-plans': 'Suscripción',
    'subscription-billing': 'Facturación',
};

const currentSectionLabel = computed(() => props.sectionLabel || SECTION_LABELS[props.section] || 'esta vista');

const isOpen = ref(false);
const faqs = ref([]);
const loadingFaqs = ref(false);
const faqsLoadedForSection = ref(null);

const messages = ref([]);
const inputText = ref('');
const isAsking = ref(false);

const panelRef = ref(null);
const messagesEndRef = ref(null);

const highlight = reactive({ visible: false, top: 0, left: 0, width: 0, height: 0 });
let highlightTimeout = null;
let highlightTarget = null;

const scrollMessagesToEnd = async () => {
    await nextTick();
    messagesEndRef.value?.scrollIntoView({ behavior: 'smooth', block: 'end' });
};

const clearHighlight = () => {
    highlight.visible = false;
    highlightTarget = null;
    window.removeEventListener('scroll', updateHighlightPosition, true);
    window.removeEventListener('resize', updateHighlightPosition);
    if (highlightTimeout) {
        clearTimeout(highlightTimeout);
        highlightTimeout = null;
    }
};

const updateHighlightPosition = () => {
    if (!highlightTarget) return;

    const rect = highlightTarget.getBoundingClientRect();

    if (rect.width === 0 && rect.height === 0) {
        clearHighlight();
        return;
    }

    const padding = 8;
    highlight.top = Math.max(rect.top - padding, 4);
    highlight.left = Math.max(rect.left - padding, 4);
    highlight.width = rect.width + padding * 2;
    highlight.height = rect.height + padding * 2;
};

const highlightSelector = async (selector) => {
    if (!selector) return;

    const element = document.querySelector(selector);

    if (!element) return;

    clearHighlight();
    highlightTarget = element;

    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    await nextTick();
    // Esperar a que termine el scroll suave antes de medir la posición final.
    setTimeout(() => {
        updateHighlightPosition();
        highlight.visible = true;
        window.addEventListener('scroll', updateHighlightPosition, true);
        window.addEventListener('resize', updateHighlightPosition);
        highlightTimeout = setTimeout(clearHighlight, 4000);
    }, 350);
};

const fetchFaqs = async () => {
    if (faqsLoadedForSection.value === props.section) return;

    loadingFaqs.value = true;

    try {
        const { data } = await axios.get(tenantRoute('support.faq'), {
            params: { section: props.section },
        });
        faqs.value = data.faqs ?? [];
        faqsLoadedForSection.value = props.section;
    } catch {
        faqs.value = [];
    } finally {
        loadingFaqs.value = false;
    }
};

const togglePanel = () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        fetchFaqs();
        scrollMessagesToEnd();
    } else {
        clearHighlight();
    }
};

const closePanel = () => {
    isOpen.value = false;
    clearHighlight();
};

const askFaq = (faq) => {
    messages.value.push({ role: 'user', text: faq.question });
    messages.value.push({ role: 'assistant', text: faq.answer, selector: faq.selector });
    scrollMessagesToEnd();
    highlightSelector(faq.selector);
};

const askFreeText = async () => {
    const question = inputText.value.trim();

    if (!question || isAsking.value) return;

    messages.value.push({ role: 'user', text: question });
    inputText.value = '';
    isAsking.value = true;
    scrollMessagesToEnd();

    try {
        const { data } = await axios.post(tenantRoute('support.ask'), {
            section: props.section,
            question,
        });

        messages.value.push({ role: 'assistant', text: data.answer, selector: data.selector });
        scrollMessagesToEnd();

        if (data.selector) {
            highlightSelector(data.selector);
        }
    } catch {
        messages.value.push({
            role: 'assistant',
            text: 'No pude procesar tu pregunta en este momento. Intenta nuevamente en unos segundos.',
            selector: null,
        });
        scrollMessagesToEnd();
    } finally {
        isAsking.value = false;
    }
};

watch(
    () => props.section,
    () => {
        messages.value = [];
        clearHighlight();
        if (isOpen.value) {
            fetchFaqs();
        }
    },
);

const handleClickOutside = (event) => {
    if (panelRef.value && !panelRef.value.contains(event.target) && isOpen.value) {
        // No cerrar si el clic fue en el propio botón flotante (maneja su propio toggle).
        if (event.target.closest('[data-support-toggle]')) return;
        closePanel();
    }
};

document.addEventListener('click', handleClickOutside, true);

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside, true);
    clearHighlight();
});
</script>

<template>
    <Teleport to="body">
        <!-- Resalte del elemento sugerido -->
        <div
            v-if="highlight.visible"
            class="pointer-events-none fixed z-[130] rounded-2xl ring-4 ring-[#FF7A00] ring-offset-2 transition-all duration-300"
            :style="{
                top: highlight.top + 'px',
                left: highlight.left + 'px',
                width: highlight.width + 'px',
                height: highlight.height + 'px',
                boxShadow: '0 0 0 9999px rgba(2,6,23,0.25)',
            }"
        />
    </Teleport>

    <!-- Botón flotante -->
    <button
        type="button"
        data-support-toggle
        :aria-label="isOpen ? 'Cerrar ayuda' : 'Abrir ayuda de TallerFlow'"
        class="fixed bottom-5 right-5 z-[95] flex h-14 w-14 items-center justify-center rounded-full bg-[#FF7A00] text-white shadow-[0_15px_35px_rgba(255,122,0,0.4)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#CC6200] focus:outline-none focus:ring-4 focus:ring-[#FF7A00]/30 sm:bottom-6 sm:right-6"
        @click="togglePanel"
    >
        <svg v-if="!isOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.06 0-2.076-.163-3.017-.463L3 21l1.395-3.72C3.512 15.845 3 14.483 3 13c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Panel de ayuda -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-95"
    >
        <div
            v-if="isOpen"
            ref="panelRef"
            class="fixed bottom-24 right-5 z-[95] flex h-[min(32rem,70vh)] w-[min(23rem,calc(100vw-2.5rem))] flex-col overflow-hidden rounded-[2rem] border border-white bg-white shadow-[0_30px_70px_rgba(2,6,23,0.25)] sm:bottom-28 sm:right-6"
        >
            <!-- Encabezado -->
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-br from-[#FF7A00] to-[#CC6200] px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/80">Ayuda TallerFlow</p>
                    <p class="text-sm font-black leading-tight">{{ currentSectionLabel }}</p>
                </div>
                <button type="button" class="rounded-lg p-1 text-white/80 hover:bg-white/10 hover:text-white" @click="closePanel">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mensajes / FAQ -->
            <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                <p class="text-xs font-medium leading-relaxed text-slate-500">
                    Pregúntame algo sobre <strong>{{ currentSectionLabel }}</strong> o elige una pregunta frecuente.
                </p>

                <div v-if="loadingFaqs" class="flex justify-center py-4">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-[#FF7A00]/30 border-t-[#FF7A00]"></div>
                </div>

                <div v-else-if="faqs.length && messages.length === 0" class="space-y-2">
                    <button
                        v-for="faq in faqs"
                        :key="faq.id"
                        type="button"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-bold text-slate-700 transition-colors hover:border-[#FF7A00]/40 hover:bg-[#FF7A00]/5 hover:text-[#FF7A00]"
                        @click="askFaq(faq)"
                    >
                        {{ faq.question }}
                    </button>
                </div>

                <p v-else-if="!loadingFaqs && !faqs.length && messages.length === 0" class="rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-400">
                    Aún no tengo preguntas frecuentes para esta vista. Escribe tu duda abajo y haré lo posible por ayudarte.
                </p>

                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="flex"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] rounded-2xl px-4 py-2.5 text-xs font-medium leading-relaxed"
                        :class="message.role === 'user'
                            ? 'bg-[#FF7A00] text-white'
                            : 'bg-slate-100 text-slate-700'"
                    >
                        {{ message.text }}
                        <button
                            v-if="message.role === 'assistant' && message.selector"
                            type="button"
                            class="mt-2 flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-[#FF7A00] hover:text-[#CC6200]"
                            @click="highlightSelector(message.selector)"
                        >
                            📍 Mostrar en pantalla
                        </button>
                    </div>
                </div>

                <div v-if="isAsking" class="flex justify-start">
                    <div class="flex items-center gap-1 rounded-2xl bg-slate-100 px-4 py-3">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.3s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.15s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>
                    </div>
                </div>

                <div ref="messagesEndRef"></div>
            </div>

            <!-- Input -->
            <form class="flex items-center gap-2 border-t border-slate-100 px-3 py-3" @submit.prevent="askFreeText">
                <input
                    v-model="inputText"
                    type="text"
                    maxlength="300"
                    placeholder="Escribe tu pregunta..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-[#FF7A00] focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/30"
                />
                <button
                    type="submit"
                    :disabled="isAsking || !inputText.trim()"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#FF7A00] text-white transition-colors hover:bg-[#CC6200] disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </form>
        </div>
    </Transition>
</template>
