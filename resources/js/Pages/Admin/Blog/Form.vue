<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    post: Object,
});

const isEditing = computed(() => !!props.post);

const form = useForm({
    title: props.post?.title || '',
    summary: props.post?.summary || '',
    content: props.post?.content || '',
    featured_image: props.post?.featured_image || '',
    is_published: props.post?.is_published ?? false,
});

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
                    {{ isEditing ? `Editar Artículo` : 'Nuevo Artículo' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ isEditing ? 'Modifica los detalles del artículo de blog.' : 'Redacta un nuevo artículo de blog para el sitio público.' }}
                </p>
            </div>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-xl overflow-hidden">
            <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-8">
                <!-- Basic info -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Detalles del Artículo</h2>
                    <div class="space-y-6">
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
                            <label for="summary" class="block text-sm font-medium text-gray-700">Resumen / Extracto</label>
                            <textarea
                                id="summary"
                                v-model="form.summary"
                                rows="3"
                                placeholder="Breve descripción que se mostrará en el listado del blog para captar lectores..."
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            ></textarea>
                            <div v-if="form.errors.summary" class="mt-1 text-sm text-red-600">{{ form.errors.summary }}</div>
                        </div>

                        <div>
                            <label for="featured_image" class="block text-sm font-medium text-gray-700">URL de la Imagen Destacada</label>
                            <input
                                type="text"
                                id="featured_image"
                                v-model="form.featured_image"
                                placeholder="Ej: https://tallerflow.cl/images/blog/digitalizar-agenda.jpg"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-gray-400">Dirección web de la imagen de portada. Recomendada: 1200×630 px.</p>
                            <div v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">{{ form.errors.featured_image }}</div>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">Contenido del artículo (Markdown / HTML) <span class="text-red-500">*</span></label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                rows="15"
                                required
                                placeholder="Escribe el cuerpo del artículo aquí..."
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm font-mono"
                            ></textarea>
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Toggles -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Configuración de publicación</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between max-w-lg">
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Publicar artículo</span>
                                <span class="text-xs text-gray-400">Si está desactivado, se guardará como borrador visible solo para ti.</span>
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
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.blog.index')"
                        class="inline-flex justify-center rounded-md border border-gray-200 bg-white py-2 px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                    >
                        {{ isEditing ? 'Guardar Cambios' : 'Crear Artículo' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
