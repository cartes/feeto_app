<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import WorkOrderQuote from '@/Components/WorkOrderQuote.vue';

const props = defineProps({
    workOrder: Object,
    quoteStatuses: Array,
    commercialQuotesEnabled: Boolean,
});

const quote = computed(() => props.workOrder.quote ?? null);

const decisionForm = useForm({
    decision: '',
    notes: '',
});

const steps = [
    { id: 'recepcion', title: 'Recepción', description: 'Vehículo ingresado y registrado en el taller.' },
    { id: 'diagnostico', title: 'En Diagnóstico', description: 'Estamos evaluando el estado de tu vehículo.' },
    { id: 'esperando_repuestos', title: 'Esperando Repuestos', description: 'Estamos coordinando piezas y trabajos necesarios.' },
    { id: 'control_calidad', title: 'Control de Calidad', description: 'Se está revisando el trabajo antes de la entrega.' },
    { id: 'listo', title: 'Listo para Entrega', description: 'Tu vehículo está listo para ser retirado.' },
];

const quoteStatusConfig = {
    draft: { label: 'En Preparación', classes: 'bg-slate-100 text-slate-600 border-slate-200' },
    pending_customer: { label: 'Pendiente de Tu Respuesta', classes: 'bg-amber-50 text-amber-600 border-amber-200' },
    accepted: { label: 'Aceptada', classes: 'bg-emerald-50 text-emerald-600 border-emerald-200' },
    rejected: { label: 'Rechazada', classes: 'bg-rose-50 text-rose-600 border-rose-200' },
};

const currentIndex = computed(() => steps.findIndex((step) => step.id === props.workOrder.status));
const quoteStatus = computed(() => quoteStatusConfig[quote.value?.status] ?? quoteStatusConfig.draft);

const formatDate = (dateString) => {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const submitDecision = (decision) => {
    decisionForm.decision = decision;
    decisionForm.post(route('tracking.quote.respond', { uuid: props.workOrder.uuid }), {
        preserveScroll: true,
    });
};

const whatsappText = encodeURIComponent(`Hola, quisiera consultar sobre la OT #${props.workOrder.id} de mi vehículo ${props.workOrder.vehicle?.plate}.`);
const wsLink = `https://wa.me/?text=${whatsappText}`;
</script>

<template>
    <Head :title="`Seguimiento | ${workOrder.vehicle?.plate}`" />

    <div class="min-h-screen bg-gray-50 pb-10 font-sans">
        <header class="sticky top-0 z-50 w-full border-b border-gray-100 bg-white shadow-sm">
            <div class="mx-auto flex max-w-md items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#F9A826] rotate-3">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-black leading-none text-gray-900">Mi Taller</h1>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Seguimiento en línea</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto mt-8 flex w-full max-w-md flex-col gap-8 px-6">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-start justify-between border-b border-gray-50 pb-4">
                    <div>
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">Orden de Trabajo</p>
                        <p class="text-2xl font-black text-gray-900">#{{ workOrder.id }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-center shadow-inner">
                        <p class="text-[8px] font-bold uppercase tracking-widest text-gray-400">Patente</p>
                        <p class="mt-0.5 text-lg font-black leading-none tracking-widest text-[#F9A826]">{{ workOrder.vehicle?.plate }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-bold uppercase text-gray-800">{{ workOrder.vehicle?.brand }} {{ workOrder.vehicle?.model }}</p>
                    <p class="mt-1 text-xs font-medium text-gray-500">Ingresado el {{ formatDate(workOrder.created_at) }}</p>
                </div>
            </div>

            <div v-if="quote && commercialQuotesEnabled" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-900">Estado de la Cotización</h3>
                    <span :class="['rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', quoteStatus.classes]">
                        {{ quoteStatus.label }}
                    </span>
                </div>

                <WorkOrderQuote :workOrder="workOrder" />

                <div v-if="quote.status === 'pending_customer'" class="mt-6 space-y-4 rounded-3xl border border-orange-100 bg-orange-50/60 p-5">
                    <div>
                        <p class="text-sm font-black text-gray-900">Responder cotización</p>
                        <p class="mt-1 text-xs font-medium text-gray-500">Puedes aceptarla para autorizar el trabajo o rechazarla para que el taller la ajuste.</p>
                    </div>

                    <textarea
                        v-model="decisionForm.notes"
                        rows="3"
                        class="w-full rounded-2xl border border-orange-100 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition-all focus:border-transparent focus:ring-2 focus:ring-orange-300"
                        placeholder="Comentario opcional para el taller"
                    />
                    <p v-if="decisionForm.errors.notes" class="text-[10px] font-semibold text-rose-500">{{ decisionForm.errors.notes }}</p>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-black text-white transition-colors hover:bg-emerald-600 disabled:opacity-50"
                            :disabled="decisionForm.processing"
                            @click="submitDecision('accepted')"
                        >
                            Aceptar
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-rose-500 px-4 py-3 text-sm font-black text-white transition-colors hover:bg-rose-600 disabled:opacity-50"
                            :disabled="decisionForm.processing"
                            @click="submitDecision('rejected')"
                        >
                            Rechazar
                        </button>
                    </div>
                </div>

                <div v-else-if="quote.customer_response_notes" class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Tu comentario</p>
                    <p class="mt-2 text-sm font-medium text-gray-700">{{ quote.customer_response_notes }}</p>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-xs font-black uppercase tracking-widest text-gray-900">Cotización en línea</h3>
                <p class="mt-3 text-sm font-medium text-gray-500">
                    Este taller no tiene habilitada la aprobación comercial en línea para tu plan actual.
                    Si necesitas una propuesta formal, contáctate directamente con recepción.
                </p>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-xs font-black uppercase tracking-widest text-gray-900">Estado de la Reparación</h3>

                <div class="relative space-y-8 pl-6">
                    <div class="absolute bottom-6 left-[-1px] top-2 ml-8 w-0.5 bg-gray-100"></div>

                    <div v-for="(step, index) in steps" :key="step.id" class="relative z-10">
                        <div class="flex items-start gap-4">
                            <div class="relative shrink-0">
                                <template v-if="index < currentIndex">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full border-4 border-white bg-emerald-500 shadow">
                                        <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </template>
                                <template v-else-if="index === currentIndex">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full border-4 border-[#F9A826] bg-white shadow ring-4 ring-[#F9A826]/20">
                                        <div class="h-2 w-2 animate-pulse rounded-full bg-[#F9A826]"></div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="h-6 w-6 rounded-full border-4 border-gray-200 bg-white"></div>
                                </template>
                            </div>

                            <div class="flex flex-col pb-2">
                                <h4 class="text-sm font-black uppercase tracking-wide" :class="index === currentIndex ? 'text-[#F9A826]' : (index < currentIndex ? 'text-gray-900' : 'text-gray-400')">
                                    {{ step.title }}
                                </h4>
                                <p class="mt-1 text-[11px] font-medium leading-relaxed" :class="index === currentIndex ? 'text-gray-600' : 'text-gray-400'">
                                    {{ step.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="workOrder.observations" class="mt-8 rounded-2xl border border-amber-100/50 bg-amber-50 p-4">
                    <p class="mb-1.5 flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-amber-500">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Observaciones del Taller
                    </p>
                    <p class="text-xs font-medium leading-relaxed text-amber-800">{{ workOrder.observations }}</p>
                </div>
            </div>

            <a
                :href="wsLink"
                target="_blank"
                class="flex w-full items-center justify-center gap-3 rounded-full bg-[#25D366] px-6 py-4 text-sm font-black uppercase tracking-wider text-white shadow-[0_10px_20px_rgba(37,211,102,0.3)] transition-all hover:bg-[#128C7E] active:scale-95"
            >
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
                Contactar al Taller
            </a>
        </main>
    </div>
</template>
