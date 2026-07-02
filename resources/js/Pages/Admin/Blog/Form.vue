<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const TipTapEditor = defineAsyncComponent(() => import('@/Components/TipTapEditor.vue'));
import MediaPickerModal from '@/Components/MediaPickerModal.vue';

const props = defineProps({
    post: Object,
    categories: { type: Array, default: () => [] },
});

const isEditing = computed(() => !!props.post);

const form = useForm({
    title: props.post?.title || '',
    slug: props.post?.slug || '',
    meta_title: props.post?.meta_title || '',
    summary: props.post?.summary || '',
    meta_description: props.post?.meta_description || '',
    content: props.post?.content || '',
    featured_media_id: props.post?.featured_media_id || null,
    is_published: props.post?.is_published ?? false,
    category_ids: props.post?.categories?.map(c => c.id) || [],
});

const page = usePage();
const appUrl = computed(() => page.props.ziggy?.url || 'https://tallerflow.cl');
const seoTitleLength = computed(() => form.meta_title.length);
const seoDescLength = computed(() => form.meta_description.length);

const slugPreview = computed(() => {
    if (form.slug) {
        return form.slug.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
    if (form.title) {
        return form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
    return 'slug-del-articulo';
});

const mediaFiles = ref([]);
const showMediaPicker = ref(false);
const mediaPurpose = ref('featured');
const editorRef = ref(null);

const featuredMedia = ref(props.post?.featured_media || null);

function openMediaPicker(purpose) {
    mediaPurpose.value = purpose;
    showMediaPicker.value = true;
    loadMedia();
}

async function loadMedia() {
    if (mediaFiles.value.length) return;
    try {
        const res = await axios.get(route('admin.media-library.index'), {
            headers: { Accept: 'application/json', 'X-Inertia': false },
        });
        mediaFiles.value = res.data?.files || [];
    } catch {
        mediaFiles.value = [];
    }
}

function onMediaSelect(file) {
    if (mediaPurpose.value === 'insert') {
        editorRef.value?.insertImage(file.url);
    } else {
        featuredMedia.value = file;
        form.featured_media_id = file.id;
    }
}

function onMediaUploaded(file) {
    if (!mediaFiles.value.find(f => f.id === file.id)) {
        mediaFiles.value.unshift(file);
    }
}

function removeFeaturedImage() {
    featuredMedia.value = null;
    form.featured_media_id = null;
}

function onMediaDeleted(deletedId) {
    mediaFiles.value = mediaFiles.value.filter(f => f.id !== deletedId);
    if (form.featured_media_id === deletedId) {
        featuredMedia.value = null;
        form.featured_media_id = null;
    }
}

function toggleCategory(id) {
    const idx = form.category_ids.indexOf(id);
    if (idx === -1) form.category_ids.push(id);
    else form.category_ids.splice(idx, 1);
}

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.blog.update', props.post.id), { preserveScroll: true });
    } else {
        form.post(route('admin.blog.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isEditing ? `Editar Artículo: ${post.title}` : 'Nuevo Artículo de Blog'" />

    <AdminLayout>
        <!-- Header -->
        <div class="mb-8 flex items-center gap-4">
            <Link
                :href="route('admin.blog.index')"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 hover:bg-slate-50 hover:text-slate-700 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ isEditing ? 'Editar Artículo' : 'Nuevo Artículo' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ isEditing ? 'Modifica los detalles del artículo de blog.' : 'Redacta un nuevo artículo de blog para el sitio público.' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main column -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6 space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título del artículo <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="title"
                            v-model="form.title"
                            required
                            placeholder="Ej: 5 Consejos para digitalizar la agenda de tu taller mecánico"
                            class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                        />
                        <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700">Enlace permanente (Slug)</label>
                        <div class="mt-2 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-200 bg-gray-50 px-3 text-gray-500 sm:text-sm">
                                blog/
                            </span>
                            <input
                                type="text"
                                id="slug"
                                v-model="form.slug"
                                placeholder="ej-titulo-del-articulo"
                                class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Si se deja vacío, se generará automáticamente a partir del título.</p>
                        <div v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</div>
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-medium text-gray-700">Resumen / Extracto</label>
                        <textarea
                            id="summary"
                            v-model="form.summary"
                            rows="3"
                            placeholder="Breve descripción que se mostrará en el listado del blog..."
                            class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-400">Máx. 500 caracteres. Aparece en tarjetas y redes sociales.</p>
                        <div v-if="form.errors.summary" class="mt-1 text-sm text-red-600">{{ form.errors.summary }}</div>
                    </div>
                </div>

                <!-- Editor -->
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Contenido <span class="text-red-500">*</span></label>
                    <TipTapEditor
                        ref="editorRef"
                        v-model="form.content"
                        placeholder="Escribe el contenido del artículo aquí..."
                        @open-media-picker="openMediaPicker('insert')"
                    />
                    <div v-if="form.errors.content" class="mt-2 text-sm text-red-600">{{ form.errors.content }}</div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Publish -->
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-4">Publicación</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-sm font-medium text-gray-700">Publicar artículo</span>
                            <span class="text-xs text-gray-400">Desactivado = borrador.</span>
                        </div>
                        <button
                            type="button"
                            @click="form.is_published = !form.is_published"
                            :class="form.is_published ? 'bg-orange-500' : 'bg-slate-200'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                            role="switch"
                            :aria-checked="form.is_published"
                        >
                            <span :class="form.is_published ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                        </button>
                    </div>
                    <div class="mt-6 flex flex-col gap-2">
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="w-full inline-flex justify-center rounded-lg bg-orange-500 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                        >
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Artículo' }}
                        </button>
                        <Link
                            :href="route('admin.blog.index')"
                            class="w-full inline-flex justify-center rounded-lg border border-gray-200 bg-white py-2.5 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors text-center"
                        >
                            Cancelar
                        </Link>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">Imagen Destacada</h3>

                    <div v-if="featuredMedia" class="mb-3">
                        <div class="relative rounded-lg overflow-hidden aspect-video bg-slate-100">
                            <img :src="featuredMedia.url" :alt="featuredMedia.alt_text || ''" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="removeFeaturedImage"
                                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-white/90 text-slate-600 hover:bg-white flex items-center justify-center shadow"
                            >
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500 truncate">{{ featuredMedia.original_name }}</p>
                    </div>

                    <button
                        type="button"
                        @click="openMediaPicker('featured')"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg border-2 border-dashed border-slate-300 text-sm text-slate-500 hover:border-orange-400 hover:text-orange-500 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ featuredMedia ? 'Cambiar imagen' : 'Seleccionar del banco' }}
                    </button>
                    <p class="mt-2 text-xs text-slate-400">Recomendada: 1200×630 px</p>
                    <div v-if="form.errors.featured_media_id" class="mt-1 text-sm text-red-600">{{ form.errors.featured_media_id }}</div>
                </div>

                <!-- Categories -->
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-slate-800">Categorías</h3>
                        <Link :href="route('admin.blog-categories.create')" class="text-xs text-orange-500 hover:text-orange-700">+ Nueva</Link>
                    </div>

                    <div v-if="categories.length === 0" class="text-xs text-slate-400 py-2">
                        No hay categorías. <Link :href="route('admin.blog-categories.create')" class="text-orange-500 underline">Crea una.</Link>
                    </div>

                    <div v-else class="flex flex-wrap gap-2">
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            @click="toggleCategory(cat.id)"
                            :class="form.category_ids.includes(cat.id)
                                ? 'text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-50'"
                            :style="form.category_ids.includes(cat.id) ? `background-color: ${cat.color}; border-color: ${cat.color}` : ''"
                            class="px-3 py-1 rounded-full text-xs font-medium border border-slate-200 transition-all"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                    <div v-if="form.errors.category_ids" class="mt-2 text-sm text-red-600">{{ form.errors.category_ids }}</div>
                </div>
            </div>
        </div>

        <!-- SEO Block (full width below columns) -->
        <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6 mt-0">
            <div class="flex items-center gap-2 mb-5">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <h3 class="text-sm font-semibold text-slate-800">SEO</h3>
                <span class="text-xs text-slate-400 ml-1">Personaliza cómo aparece este artículo en Google y redes sociales</span>
            </div>

            <!-- Google Preview -->
            <div class="mb-5 p-4 rounded-lg bg-slate-50 border border-slate-200">
                <p class="text-xs font-medium text-slate-500 mb-2 uppercase tracking-wider">Vista previa en Google</p>
                <p class="text-[#1a0dab] text-base font-medium truncate">
                    {{ form.meta_title || form.title || 'Título del artículo' }}
                </p>
                <p class="text-[#006621] text-xs truncate">
                    {{ appUrl }}/blog/{{ slugPreview }}
                </p>
                <p class="text-[#545454] text-sm line-clamp-2 mt-0.5">
                    {{ form.meta_description || form.summary || 'Descripción que aparecerá en los resultados de búsqueda de Google...' }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Título SEO</label>
                        <span :class="seoTitleLength > 60 ? 'text-red-500' : seoTitleLength > 50 ? 'text-amber-500' : 'text-slate-400'" class="text-xs font-mono">
                            {{ seoTitleLength }}/70
                        </span>
                    </div>
                    <input
                        type="text"
                        v-model="form.meta_title"
                        maxlength="70"
                        placeholder="Ej: 5 Consejos para Digitalizar tu Taller · TallerFlow"
                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-slate-400">Si lo dejas vacío se usa el título del artículo. Ideal: 50-60 caracteres.</p>
                    <div v-if="form.errors.meta_title" class="mt-1 text-sm text-red-600">{{ form.errors.meta_title }}</div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Meta descripción</label>
                        <span :class="seoDescLength > 155 ? 'text-red-500' : seoDescLength > 140 ? 'text-amber-500' : 'text-slate-400'" class="text-xs font-mono">
                            {{ seoDescLength }}/160
                        </span>
                    </div>
                    <textarea
                        v-model="form.meta_description"
                        rows="3"
                        maxlength="160"
                        placeholder="Descripción concisa del artículo para Google y redes sociales..."
                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                    ></textarea>
                    <p class="mt-1 text-xs text-slate-400">Si lo dejas vacío se usa el resumen. Ideal: 140-155 caracteres.</p>
                    <div v-if="form.errors.meta_description" class="mt-1 text-sm text-red-600">{{ form.errors.meta_description }}</div>
                </div>
            </div>

            <!-- OG image info -->
            <div class="mt-4 p-3 rounded-lg bg-blue-50 border border-blue-100 flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <p class="text-xs text-blue-600">
                    La <strong>imagen destacada</strong> se usará como imagen para Open Graph (Facebook, LinkedIn) y Twitter Card.
                    Recomendada: 1200×630 px.
                </p>
            </div>
        </div>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            :show="showMediaPicker"
            :files="mediaFiles"
            :mode="mediaPurpose"
            :initial-selected-id="mediaPurpose === 'featured' ? form.featured_media_id : null"
            @close="showMediaPicker = false"
            @select="onMediaSelect"
            @uploaded="onMediaUploaded"
            @deleted="onMediaDeleted"
        />
    </AdminLayout>
</template>
