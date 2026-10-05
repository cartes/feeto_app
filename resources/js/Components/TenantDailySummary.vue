<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ items: { type: Array, required: true } });
</script>

<template>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4" data-tour="dashboard-summary" aria-label="Resumen del día">
        <component :is="item.url.startsWith('#') ? 'a' : Link" v-for="item in items" :key="item.id" :href="item.url" class="rounded-2xl border bg-white p-4 shadow-sm transition hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-500" :class="item.attention && item.count > 0 ? 'border-amber-200' : 'border-gray-100'">
            <p class="text-3xl font-bold tracking-tight" :class="item.attention && item.count > 0 ? 'text-amber-700' : 'text-gray-900'">{{ item.count }}</p>
            <p class="mt-2 text-sm font-semibold text-gray-600">{{ item.label }}</p>
            <p class="mt-2 text-xs font-medium text-orange-700">Revisar →</p>
        </component>
    </div>
</template>
