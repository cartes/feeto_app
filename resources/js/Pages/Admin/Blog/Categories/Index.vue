<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categories: Array,
});

const confirmDelete = ref(null);

function destroy(category) {
    router.delete(route('admin.blog-categories.destroy', category.id), {
        onFinish: () => { confirmDelete.value = null; },
    });
}
</script>

<template>
    <Head title="Categorías del Blog" />

    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Categorías</h1>
                <p class="mt-1 text-sm text-slate-500">Organiza los artículos del blog por temáticas.</p>
            </div>
            <Link
                :href="route('admin.blog-categories.create')"
                class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Categoría
            </Link>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl overflow-hidden">
            <div v-if="categories.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                </svg>
                <p class="font-medium">No hay categorías todavía</p>
            </div>

            <table v-else class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Artículos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="`background-color: ${cat.color}`"></span>
                                <span class="text-sm font-medium text-slate-900">{{ cat.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 font-mono">/blog/categoria/{{ cat.slug }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                {{ cat.posts_count }} artículo{{ cat.posts_count !== 1 ? 's' : '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('admin.blog-categories.edit', cat.id)"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white ring-1 ring-slate-200 hover:bg-slate-50 transition-colors"
                                >
                                    Editar
                                </Link>
                                <button
                                    type="button"
                                    @click="confirmDelete = cat"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-red-600 bg-white ring-1 ring-red-200 hover:bg-red-50 transition-colors"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Delete modal -->
        <div v-if="confirmDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="confirmDelete = null"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-base font-semibold text-slate-900 mb-2">Eliminar categoría</h3>
                <p class="text-sm text-slate-600 mb-5">¿Eliminar "<strong>{{ confirmDelete.name }}</strong>"? Los artículos no serán eliminados, solo se desvinculará la categoría.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmDelete = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancelar</button>
                    <button type="button" @click="destroy(confirmDelete)" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Eliminar</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
