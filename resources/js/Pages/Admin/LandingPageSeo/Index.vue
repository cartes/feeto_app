<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    pages: { type: Array, default: () => [] },
    analytics_settings: Object,
});

const form = useForm({
    pages: props.pages.map(p => ({
        key: p.key,
        title: p.title ?? '',
        description: p.description ?? '',
        og_image: p.og_image ?? '',
    })),
    analytics_google_analytics_code: props.analytics_settings?.analytics_google_analytics_code?.value || '',
    analytics_google_search_console_code: props.analytics_settings?.analytics_google_search_console_code?.value || '',
});

const isEditingGa = ref(false);

const titleLen = (i) => form.pages[i]?.title?.length ?? 0;
const descLen = (i) => form.pages[i]?.description?.length ?? 0;

const titleColor = (i) => {
    const l = titleLen(i);
    if (l === 0) return 'text-slate-400';
    if (l <= 60) return 'text-emerald-600';
    if (l <= 80) return 'text-amber-500';
    return 'text-rose-500';
};

const descColor = (i) => {
    const l = descLen(i);
    if (l === 0) return 'text-slate-400';
    if (l <= 160) return 'text-emerald-600';
    if (l <= 220) return 'text-amber-500';
    return 'text-rose-500';
};

const titleProgress = (i) => Math.min(titleLen(i) / 80, 1);
const descProgress = (i) => Math.min(descLen(i) / 220, 1);

const previewTitle = (i) => form.pages[i]?.title || props.pages[i]?.default_title || '';
const previewDesc = (i) => form.pages[i]?.description || props.pages[i]?.default_description || '';

const submit = () => {
    form.put(route('admin.landing-seo.update'));
};

const resetPage = (i) => {
    form.pages[i].title = props.pages[i]?.default_title ?? '';
    form.pages[i].description = props.pages[i]?.default_description ?? '';
    form.pages[i].og_image = '';
};
</script>

<template>
    <Head title="SEO Páginas Públicas · Admin" />

    <AdminLayout>
        <div class="mb-8 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">SEO — Páginas Públicas</h1>
                <p class="mt-1 text-sm text-slate-500">Configura los meta tags, descripción y OG image para cada página pública del sitio.</p>
            </div>
            <button
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors disabled:opacity-60"
            >
                <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
            </button>
        </div>

        <div class="space-y-8">
            <div
                v-for="(page, i) in pages"
                :key="page.key"
                class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden"
            >
                <!-- Card header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4 bg-slate-50">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-slate-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ page.label }}</p>
                            <code class="text-xs text-slate-400 font-mono">{{ page.url }}</code>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="resetPage(i)"
                        class="text-xs text-slate-400 hover:text-slate-600 font-medium transition-colors"
                    >
                        Restablecer por defecto
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Fields -->
                    <div class="space-y-5">
                        <!-- Title -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Meta Title</label>
                                <span :class="['text-xs font-mono', titleColor(i)]">{{ titleLen(i) }}/80</span>
                            </div>
                            <input
                                v-model="form.pages[i].title"
                                type="text"
                                maxlength="160"
                                placeholder="Ej: TallerFlow · Software para Talleres Mecánicos"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                            />
                            <!-- Progress bar -->
                            <div class="mt-1.5 h-1 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    :style="{ width: `${titleProgress(i) * 100}%` }"
                                    :class="['h-1 rounded-full transition-all', titleLen(i) <= 60 ? 'bg-emerald-400' : titleLen(i) <= 80 ? 'bg-amber-400' : 'bg-rose-400']"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Ideal: 50–60 caracteres para Google Search.</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Meta Description</label>
                                <span :class="['text-xs font-mono', descColor(i)]">{{ descLen(i) }}/220</span>
                            </div>
                            <textarea
                                v-model="form.pages[i].description"
                                rows="3"
                                maxlength="320"
                                placeholder="Describe brevemente el contenido de esta página para los motores de búsqueda…"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition resize-none"
                            ></textarea>
                            <div class="mt-1.5 h-1 rounded-full bg-slate-100 overflow-hidden">
                                <div
                                    :style="{ width: `${descProgress(i) * 100}%` }"
                                    :class="['h-1 rounded-full transition-all', descLen(i) <= 160 ? 'bg-emerald-400' : descLen(i) <= 220 ? 'bg-amber-400' : 'bg-rose-400']"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Ideal: 120–160 caracteres para Google Search.</p>
                        </div>

                        <!-- OG Image -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">OG Image URL <span class="text-slate-400 normal-case font-normal">(opcional)</span></label>
                            <input
                                v-model="form.pages[i].og_image"
                                type="url"
                                placeholder="https://tallerflow.cl/images/og-precios.jpg"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition"
                            />
                            <p class="mt-1 text-xs text-slate-400">Imagen que aparece al compartir en redes sociales. Recomendado: 1200×630 px.</p>
                        </div>
                    </div>

                    <!-- Google Preview -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Vista previa en Google</p>
                        <div class="rounded-xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 rounded-full bg-slate-200"></div>
                                <div>
                                    <p class="text-xs text-slate-600 font-medium leading-none">TallerFlow</p>
                                    <p class="text-xs text-slate-400 leading-none mt-0.5">tallerflow.cl{{ page.url }}</p>
                                </div>
                            </div>
                            <p class="text-[#1a0dab] text-lg font-normal leading-tight mb-1 line-clamp-1">
                                {{ previewTitle(i) || 'Sin título' }}
                            </p>
                            <p class="text-sm text-slate-600 leading-snug line-clamp-2">
                                {{ previewDesc(i) || 'Sin descripción.' }}
                            </p>
                        </div>

                        <!-- OG Preview -->
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-3">Vista previa en redes sociales</p>
                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div v-if="form.pages[i].og_image" class="aspect-[1200/630] bg-slate-100">
                                <img :src="form.pages[i].og_image" class="w-full h-full object-cover" :alt="previewTitle(i)" @error="$event.target.style.display='none'" />
                            </div>
                            <div v-else class="aspect-[1200/630] bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 12V21h19.5V12M2.25 3h19.5v9"/></svg>
                                    <p class="text-xs text-slate-400">Sin imagen OG definida</p>
                                </div>
                            </div>
                            <div class="p-3 bg-slate-50 border-t border-slate-100">
                                <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide">tallerflow.cl</p>
                                <p class="text-sm font-semibold text-slate-900 mt-0.5 line-clamp-1">{{ previewTitle(i) || 'Sin título' }}</p>
                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ previewDesc(i) || 'Sin descripción.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Analytics Card -->
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Seguimiento &amp; Analytics</p>
                            <p class="text-xs text-slate-500">Configura las herramientas globales de medición y verificación del sitio.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="analytics_google_analytics_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">Código de Google Analytics (Script)</label>
                            <button
                                type="button"
                                @click="isEditingGa = !isEditingGa"
                                class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 hover:text-amber-700 transition"
                            >
                                <svg v-if="!isEditingGa" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                {{ isEditingGa ? 'Bloquear' : 'Editar' }}
                            </button>
                        </div>
                        <textarea
                            id="analytics_google_analytics_code"
                            v-model="form.analytics_google_analytics_code"
                            rows="6"
                            :readonly="!isEditingGa"
                            placeholder="Pega aquí el código HTML completo proporcionado por Google Analytics (ej. &lt;script async src='https://www.googletagmanager.com/gtag/js...'&gt;&lt;/script&gt;)"
                            :class="[
                                'w-full rounded-lg border px-3 py-2.5 text-sm font-mono resize-y transition',
                                !isEditingGa
                                    ? 'bg-slate-50 border-slate-200 text-slate-500 cursor-not-allowed'
                                    : 'bg-white border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent'
                            ]"
                        ></textarea>
                        <div v-if="form.errors.analytics_google_analytics_code" class="mt-1 text-sm text-red-600">{{ form.errors.analytics_google_analytics_code }}</div>
                    </div>

                    <div>
                        <label for="analytics_google_search_console_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Código de Verificación de Google Search Console (Meta Tag)</label>
                        <textarea
                            id="analytics_google_search_console_code"
                            v-model="form.analytics_google_search_console_code"
                            rows="3"
                            placeholder="Pega aquí el tag meta de verificación completo (ej. &lt;meta name='google-site-verification' content='...' /&gt;)"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition font-mono resize-y"
                        ></textarea>
                        <div v-if="form.errors.analytics_google_search_console_code" class="mt-1 text-sm text-red-600">{{ form.errors.analytics_google_search_console_code }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save button bottom -->
        <div class="mt-8 flex justify-end">
            <button
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors disabled:opacity-60"
            >
                {{ form.processing ? 'Guardando…' : 'Guardar todos los cambios' }}
            </button>
        </div>
    </AdminLayout>
</template>
