<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

const props = defineProps({
    tenant: Object,
});

const seoForm = useForm({
    seo_description: props.tenant?.seo_description ?? '',
    seo_address: props.tenant?.seo_address ?? '',
    whatsapp_number: props.tenant?.whatsapp_number ?? '',
});

const submitSeoSettings = () => {
    seoForm.patch(route('taller.settings.seo.update', tenantRouteParams.value), {
        preserveScroll: true,
    });
};

// ── IA DESCRIPCIÓN ────────────────────────────────────────
const showAiModal = ref(false);
const aiConcepts = ref(['', '', '']);
const aiGenerating = ref(false);
const aiError = ref('');

const generateAiDescription = async () => {
    const filled = aiConcepts.value.filter(c => c.trim().length > 0);
    if (filled.length === 0) {
        aiError.value = 'Ingresa al menos un concepto.';
        return;
    }
    aiError.value = '';
    aiGenerating.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const res = await fetch(route('taller.settings.seo.generate', tenantRouteParams.value), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                workshop_name: props.tenant?.name ?? '',
                concepts: filled,
            }),
        });
        const data = await res.json();
        if (data.error) {
            aiError.value = data.error;
        } else {
            seoForm.seo_description = data.description ?? '';
            showAiModal.value = false;
            aiConcepts.value = ['', '', ''];
        }
    } catch {
        aiError.value = 'Error al conectar con la IA.';
    } finally {
        aiGenerating.value = false;
    }
};
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-300">

        <!-- AI Modal -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-5 animate-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-violet-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-black uppercase tracking-widest text-gray-700">Generar con IA</p>
                        </div>
                        <button @click="showAiModal = false; aiError = ''" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">Ingresa hasta 3 conceptos que describan tu taller y la IA generará una descripción SEO optimizada.</p>
                    <div class="space-y-3">
                        <div v-for="(_, i) in aiConcepts" :key="i" class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Concepto {{ i + 1 }}{{ i === 0 ? ' *' : '' }}</label>
                            <input
                                v-model="aiConcepts[i]"
                                type="text"
                                :placeholder="['ej: servicio express, garantía 3 meses, diagnóstico gratuito'][i] ?? ''"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-400"
                            />
                        </div>
                    </div>
                    <p v-if="aiError" class="text-red-500 text-xs font-semibold">{{ aiError }}</p>
                    <div class="flex gap-3 justify-end pt-1">
                        <button type="button" @click="showAiModal = false; aiError = ''"
                            class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">
                            Cancelar
                        </button>
                        <button @click="generateAiDescription" :disabled="aiGenerating"
                            class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-violet-700 transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="aiGenerating" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            {{ aiGenerating ? 'Generando...' : 'Generar descripción' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <form @submit.prevent="submitSeoSettings" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
                <div>
                    <p class="text-sm font-black uppercase tracking-widest text-gray-500">Perfil público</p>
                    <h3 class="mt-2 text-2xl font-black text-gray-900">SEO y WhatsApp</h3>
                    <p class="mt-2 text-sm font-medium text-gray-500">
                        Esta información aparece en tu página pública y mejora tu posicionamiento en Google.
                    </p>
                </div>

                <!-- Descripción -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Descripción del taller</label>
                        <button type="button" @click="showAiModal = true"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 text-violet-600 text-[10px] font-black uppercase tracking-widest hover:bg-violet-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Generar con IA
                        </button>
                    </div>
                    <textarea
                        v-model="seoForm.seo_description"
                        rows="3"
                        placeholder="Taller especializado en mantenciones, mecánica general y diagnóstico computarizado. Servicio rápido con garantía."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] resize-none"
                    ></textarea>
                    <p class="text-[10px] text-gray-400 font-medium">{{ seoForm.seo_description?.length ?? 0 }}/500 caracteres. Ideal: 100–160 para Google.</p>
                    <p v-if="seoForm.errors.seo_description" class="text-red-500 text-xs">{{ seoForm.errors.seo_description }}</p>
                </div>

                <!-- Dirección -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Dirección del taller</label>
                    <input
                        v-model="seoForm.seo_address"
                        type="text"
                        placeholder="Av. Apoquindo 4501, Las Condes, Santiago"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                    />
                    <p v-if="seoForm.errors.seo_address" class="text-red-500 text-xs">{{ seoForm.errors.seo_address }}</p>
                </div>

                <!-- WhatsApp -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Número de WhatsApp</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <input
                            v-model="seoForm.whatsapp_number"
                            type="text"
                            placeholder="+56 9 1234 5678"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                        />
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium">Formato internacional: +56 9 XXXX XXXX. Aparecerá como botón flotante en tu página pública.</p>
                    <p v-if="seoForm.errors.whatsapp_number" class="text-red-500 text-xs">{{ seoForm.errors.whatsapp_number }}</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="seoForm.processing"
                        class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
                        {{ seoForm.processing ? 'Guardando...' : 'Guardar Perfil SEO' }}
                    </button>
                </div>
            </form>

            <!-- Panel derecho: preview -->
            <div class="space-y-4">
                <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
                    <p class="text-sm font-black uppercase tracking-widest text-gray-500">Vista previa Google</p>
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 space-y-1">
                        <p class="text-[#1a0dab] text-sm font-medium leading-tight truncate">{{ tenant?.name ?? 'Tu Taller' }} | Agendar cita</p>
                        <p class="text-[#006621] text-[11px]">tallerflow.cl/taller/{{ tenant?.slug ?? 'tu-taller' }}</p>
                        <p class="text-gray-600 text-[11px] leading-relaxed">
                            {{ seoForm.seo_description?.slice(0, 160) || 'Agrega una descripción para tu taller y aparecerá aquí en los resultados de Google.' }}
                        </p>
                    </div>
                    <div v-if="seoForm.seo_address" class="flex items-start gap-2 text-xs text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">{{ seoForm.seo_address }}</span>
                    </div>
                </div>

                <div class="rounded-3xl border border-[#25D366]/20 bg-[#25D366]/5 p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <p class="text-sm font-black text-[#128C7E]">Botón flotante de WhatsApp</p>
                    </div>
                    <p class="text-xs text-gray-600 font-medium">
                        {{ seoForm.whatsapp_number ? `Al configurar el número, un botón verde flotante aparecerá en tu página pública para que los clientes te contacten directamente.` : 'Ingresa tu número para activar el botón flotante de WhatsApp en tu página pública.' }}
                    </p>
                    <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-widest">Cada clic genera una notificación en tu CRM.</p>
                </div>
            </div>
        </div>
    </div>
</template>
