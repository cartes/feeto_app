<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    posts: Array,
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const deletePost = (post) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el artículo "${post.title}"?`)) {
        router.delete(route('admin.blog.destroy', post.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Artículos de Blog" />

    <AdminLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Artículos de Blog</h1>
                <p class="mt-1 text-sm text-slate-500">Administra las publicaciones del blog público de TallerFlow.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <Link
                    :href="route('admin.blog.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Artículo
                </Link>
            </div>
        </div>

        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Título</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Slug</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Publicado el</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Creado el</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="post in posts" :key="post.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 max-w-sm">
                                        <div class="font-medium text-slate-900 truncate">{{ post.title }}</div>
                                        <div v-if="post.summary" class="text-xs text-slate-400 mt-0.5 truncate">{{ post.summary }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 font-mono">
                                        /blog/{{ post.slug }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span v-if="post.is_published" class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Publicado
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Borrador
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ formatDate(post.published_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ formatDate(post.created_at) }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-4">
                                        <Link :href="route('blog.show', post.slug)" target="_blank" v-if="post.is_published" class="text-slate-600 hover:text-slate-900 font-semibold">Ver</Link>
                                        <Link :href="route('admin.blog.edit', post.id)" class="text-amber-600 hover:text-amber-900 font-semibold">Editar</Link>
                                        <button @click="deletePost(post)" class="text-rose-600 hover:text-rose-900 font-semibold">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="!posts || posts.length === 0">
                                    <td colspan="6" class="py-12 text-center text-sm text-slate-500">
                                        No hay artículos de blog creados aún.
                                        <Link :href="route('admin.blog.create')" class="ml-1 text-amber-600 hover:text-amber-800 font-medium">Crear el primero</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
