<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    category: Object,
});

const isEditing = computed(() => !!props.category);

const COLORS = [
    '#FF7A00', '#3B82F6', '#10B981', '#F59E0B',
    '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6',
    '#F97316', '#6366F1', '#84CC16', '#64748B',
];

const form = useForm({
    name: props.category?.name || '',
    color: props.category?.color || '#FF7A00',
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.blog-categories.update', props.category.id), { preserveScroll: true });
    } else {
        form.post(route('admin.blog-categories.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isEditing ? `Editar: ${category.name}` : 'Nueva Categoría'" />

    <AdminLayout>
        <div class="mb-8 flex items-center gap-4">
            <Link
                :href="route('admin.blog-categories.index')"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 hover:bg-slate-50 hover:text-slate-700 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                {{ isEditing ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h1>
        </div>

        <div class="max-w-lg bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Ej: Consejos para talleres"
                    class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="color in COLORS"
                        :key="color"
                        type="button"
                        @click="form.color = color"
                        :style="`background-color: ${color}`"
                        :class="form.color === color ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : 'hover:scale-105'"
                        class="w-7 h-7 rounded-full transition-all"
                    ></button>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <div class="w-6 h-6 rounded-full flex-shrink-0" :style="`background-color: ${form.color}`"></div>
                    <input
                        type="text"
                        v-model="form.color"
                        class="block w-32 rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm font-mono"
                        placeholder="#FF7A00"
                    />
                    <span class="text-sm font-medium px-3 py-1 rounded-full text-white" :style="`background-color: ${form.color}`">{{ form.name || 'Ejemplo' }}</span>
                </div>
                <div v-if="form.errors.color" class="mt-1 text-sm text-red-600">{{ form.errors.color }}</div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100">
                <Link
                    :href="route('admin.blog-categories.index')"
                    class="inline-flex justify-center rounded-md border border-gray-200 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                >
                    Cancelar
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex justify-center rounded-md bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 disabled:opacity-50 transition-colors"
                >
                    {{ isEditing ? 'Guardar Cambios' : 'Crear Categoría' }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
