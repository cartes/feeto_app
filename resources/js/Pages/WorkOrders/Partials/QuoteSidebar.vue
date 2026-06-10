<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';
import QuoteHistoryCard from './QuoteHistoryCard.vue';

const { formatCurrency, formatUf: formatUfRaw } = useFormatting();

const props = defineProps({
    workOrder: Object,
    quote: Object,
    items: {
        type: Array,
        default: () => [],
    },
    ufValue: {
        type: Number,
        default: null,
    },
    canDeliverQuote: Boolean,
    canNotifyAdmin: Boolean,
    canShareQuote: Boolean,
});

const emit = defineEmits(['open-approve-modal']);

const formatUf = (clpValue) => formatUfRaw(clpValue, props.ufValue);

const trackingUrl = computed(() => `${window.location.origin}/ot/${props.workOrder.uuid}`);
const whatsAppMessage = computed(() => {
    const vehicle = `${props.workOrder.vehicle?.brand ?? ''} ${props.workOrder.vehicle?.model ?? ''}`.trim();

    return encodeURIComponent(`Hola, tu cotización para ${vehicle} (${props.workOrder.vehicle?.plate}) está disponible: ${trackingUrl.value}`);
});

const whatsAppLink = computed(() => {
    const phone = props.workOrder.vehicle?.client?.phone ?? '';
    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${whatsAppMessage.value}`;
});
const hasClientPhone = computed(() => Boolean(props.workOrder.vehicle?.client?.phone));
const hasClientEmail = computed(() => Boolean(props.workOrder.vehicle?.client?.email));
const mailSubject = computed(() => encodeURIComponent(`Cotización OT #${props.workOrder.id} disponible para revisión`));
const mailBody = computed(() => {
    const clientName = props.workOrder.vehicle?.client?.name ?? 'cliente';
    const vehicle = `${props.workOrder.vehicle?.brand ?? ''} ${props.workOrder.vehicle?.model ?? ''}`.trim();

    return encodeURIComponent(
        `Hola ${clientName},%0D%0A%0D%0ATu cotización${vehicle ? ` para ${vehicle}` : ''} ya está disponible para revisión.%0D%0A%0D%0APuedes verla y responderla aquí:%0D%0A${trackingUrl.value}`
    );
});
const mailToLink = computed(() => {
    const email = props.workOrder.vehicle?.client?.email ?? '';

    return `mailto:${email}?subject=${mailSubject.value}&body=${mailBody.value}`;
});

const sendChannel = ref('manual');
const sendQuoteForm = useForm({ channel: 'manual' });
const notifyReadyForm = useForm({});

const shareQuoteByWhatsApp = () => {
    if (!hasClientPhone.value) {
        return;
    }

    window.open(whatsAppLink.value, '_blank', 'noopener');
};

const shareQuoteByEmail = () => {
    if (!hasClientEmail.value) {
        return;
    }

    window.location.href = mailToLink.value;
};

const shareQuoteByBoth = () => {
    shareQuoteByWhatsApp();
    shareQuoteByEmail();
};

const sendQuote = () => {
    sendQuoteForm.channel = sendChannel.value;

    sendQuoteForm.post(route('work-orders.quote.send', { workOrder: props.workOrder.id }), {
        preserveScroll: true,
        onSuccess: () => {
            if (sendChannel.value === 'whatsapp') {
                shareQuoteByWhatsApp();
            } else if (sendChannel.value === 'email') {
                shareQuoteByEmail();
            } else if (sendChannel.value === 'both') {
                shareQuoteByBoth();
            }
        },
    });
};

const notifyReady = () => {
    notifyReadyForm.post(route('work-orders.quote.notify-ready', { workOrder: props.workOrder.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-4 xl:order-last xl:sticky xl:top-6">

        <!-- Total -->
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Cotización</p>
            <p class="mt-1.5 text-3xl font-black text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</p>
            <p v-if="formatUf(quote.subtotal_amount)" class="mt-0.5 text-xs font-semibold text-gray-400">≈ UF {{ formatUf(quote.subtotal_amount) }}</p>
            <p class="mt-1.5 text-xs font-medium text-gray-400">{{ items.length }} ítems cargados</p>
        </div>

        <!-- Canal de envío + botón (admin/supervisor) -->
        <template v-if="canDeliverQuote">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Canal de envío</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors" :class="sendChannel === 'manual' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-500'" @click="sendChannel = 'manual'">Solo marcar</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'whatsapp' ? 'bg-green-600 text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientPhone" @click="sendChannel = 'whatsapp'">WhatsApp</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'email' ? 'bg-sky-600 text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientEmail" @click="sendChannel = 'email'">Email</button>
                    <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black uppercase tracking-widest transition-colors disabled:cursor-not-allowed disabled:opacity-50" :class="sendChannel === 'both' ? 'bg-[#FF7A00] text-white' : 'bg-gray-50 text-gray-500'" :disabled="!hasClientPhone || !hasClientEmail" @click="sendChannel = 'both'">Ambos</button>
                </div>
            </div>
            <button
                type="button"
                class="w-full rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-[#FF7A00] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="sendQuoteForm.processing || items.length === 0 || quote.status === 'accepted'"
                @click="sendQuote"
            >
                {{ sendQuoteForm.processing ? 'Enviando...' : sendChannel === 'whatsapp' ? 'Enviar por WhatsApp' : sendChannel === 'email' ? 'Enviar por Email' : sendChannel === 'both' ? 'Enviar por Ambos' : 'Marcar como Enviada' }}
            </button>
        </template>

        <!-- Aprobar manualmente (admin/supervisor/jefe) -->
        <template v-if="canDeliverQuote && quote.status !== 'accepted'">
            <button
                type="button"
                class="w-full rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="items.length === 0"
                @click="emit('open-approve-modal')"
            >
                Aprobar manualmente
            </button>
        </template>

        <!-- Aviso + botón (mecánico) -->
        <template v-if="!canDeliverQuote">
            <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">Revisión administrativa</p>
                <p class="mt-2 text-sm font-medium text-amber-900">Cuando termines la cotización, avisa al equipo administrador para que la envíe al cliente.</p>
            </div>
            <button
                type="button"
                class="w-full rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="notifyReadyForm.processing || !canNotifyAdmin"
                @click="notifyReady"
            >
                {{ notifyReadyForm.processing ? 'Avisando...' : 'Avisar al Administrador' }}
            </button>
        </template>

        <!-- Compartir (pending_customer) -->
        <template v-if="canShareQuote">
            <a v-if="hasClientPhone" :href="whatsAppLink" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-green-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-green-600">Compartir por WhatsApp</a>
            <a v-if="hasClientEmail" :href="mailToLink" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-sky-700">Compartir por Email</a>
            <button v-if="hasClientPhone && hasClientEmail" type="button" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#FF7A00] px-5 py-3 text-sm font-black text-white transition-colors hover:bg-[#CC6200]" @click="shareQuoteByBoth">Compartir por Ambos</button>
        </template>

        <!-- Tracking -->
        <a :href="trackingUrl" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50">
            Ver Tracking del Cliente
        </a>

        <!-- Historial (solo desktop; en mobile va al final del contenido principal) -->
        <QuoteHistoryCard :events="quote.events" variant="desktop" />
    </div>
</template>
