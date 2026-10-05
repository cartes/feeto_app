<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    checklist: { type: Object, required: true },
    stepId: { type: String, default: null },
});
const expanded = ref(false);
const form = useForm({ step: '', completed: false });
const visibleSteps = computed(() => props.stepId ? props.checklist.steps.filter(step => step.id === props.stepId) : props.checklist.steps);
const showSteps = computed(() => !props.checklist.is_complete || expanded.value);

watch(() => props.checklist.is_complete, complete => {
    if (complete) expanded.value = false;
});

const updateStep = (step) => {
    if (form.processing) return;
    form.clearErrors();
    form.step = step.id;
    form.completed = !step.completed;
    form.patch(props.checklist.update_url, { preserveScroll: true });
};
</script>

<template>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" aria-label="Primeros pasos del taller">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ checklist.is_complete ? 'Primeros pasos completados' : 'Prepara tu taller en cinco pasos' }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ checklist.completed_count }} de {{ checklist.total }} pasos revisados</p>
            </div>
            <button v-if="checklist.is_complete" type="button" @click="expanded = !expanded" :aria-expanded="expanded" class="rounded-lg px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">{{ expanded ? 'Ocultar pasos' : 'Revisar pasos' }}</button>
            <Link v-else-if="stepId" :href="checklist.overview_url" class="rounded-lg px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">Ver todos los pasos</Link>
        </div>
        <div v-if="showSteps" class="mt-4 space-y-4">
            <progress :value="checklist.completed_count" :max="checklist.total" aria-label="Progreso de preparación" class="h-2 w-full accent-[#FF7A00]"></progress>
            <p class="text-sm text-gray-500">Revisa cada sección, guarda los cambios necesarios y marca el paso como revisado. El progreso se comparte entre los administradores de tu taller.</p>
            <ol class="space-y-3">
                <li v-for="step in visibleSteps" :key="step.id" class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center" :class="step.completed ? 'border-emerald-100 bg-emerald-50/50' : 'border-gray-100'">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="step.completed ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'" aria-hidden="true">{{ step.completed ? '✓' : checklist.steps.indexOf(step) + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-bold text-gray-900">{{ step.title }} <span v-if="step.completed" class="font-normal text-emerald-700">— Revisado</span></h3>
                        <p class="mt-1 text-sm leading-relaxed text-gray-500">{{ step.description }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2 sm:max-w-[210px] sm:justify-end">
                        <a v-if="step.external" :href="step.url" target="_blank" rel="noopener noreferrer" class="rounded-lg px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">{{ step.action }}</a>
                        <Link v-else :href="step.url" class="rounded-lg px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">{{ step.action }}</Link>
                        <button type="button" :disabled="form.processing" @click="updateStep(step)" :aria-label="`${step.completed ? 'Volver a pendiente' : 'Marcar como revisado'}: ${step.title}`" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">{{ form.processing && form.step === step.id ? 'Guardando…' : step.completed ? 'Volver a pendiente' : step.id === 'share_link' ? 'Ya compartí el enlace' : 'Marcar como revisado' }}</button>
                    </div>
                </li>
            </ol>
        </div>
        <p v-if="form.errors.step || form.errors.completed" role="alert" class="mt-3 text-sm text-red-600">{{ form.errors.step || form.errors.completed }}</p>
    </section>
</template>
