<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    actionLabel: { type: String, default: null },
    actionHref: { type: String, default: null },
});
defineEmits(['action']);
</script>

<template>
    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-center">
        <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
        <p class="mt-2 text-sm text-gray-500">{{ description }}</p>
        <Link v-if="actionLabel && actionHref" :href="actionHref" class="mt-4 inline-flex rounded-xl bg-[#FF7A00] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#CC6200]">{{ actionLabel }}</Link>
        <button v-else-if="actionLabel" type="button" @click="$emit('action')" class="mt-4 rounded-xl bg-[#FF7A00] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#CC6200]">{{ actionLabel }}</button>
    </div>
</template>
