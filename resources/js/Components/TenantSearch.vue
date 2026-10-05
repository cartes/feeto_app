<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    searchUrl: { type: String, required: true },
    label: { type: String, default: 'Buscar patente, orden o cliente' },
    listView: { type: Boolean, default: false },
});
const search = ref('');
const submitSearch = () => {
    const term = search.value.trim();
    if (!term) return;
    router.get(props.searchUrl, { search: term, ...(props.listView ? { view: 'list' } : {}) });
};
</script>

<template>
    <form role="search" @submit.prevent="submitSearch" class="flex items-center gap-2 rounded-full border border-gray-200 bg-white p-1.5 shadow-sm">
        <label class="min-w-0 flex-1">
            <span class="sr-only">{{ label }}</span>
            <input v-model="search" type="search" :placeholder="label" class="w-full rounded-full border-0 bg-transparent px-3 py-2 text-sm text-gray-700 focus:ring-2 focus:ring-orange-300" />
        </label>
        <button type="submit" class="shrink-0 rounded-full bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-100">Buscar</button>
    </form>
</template>
