<script setup>
defineProps({
    alt: {
        type: String,
        default: 'Taller Flow',
    },
});
</script>

<template>
    <img
        src="/images/taller-flow-isotipo.png"
        :alt="alt"
        class="block"
    >
</template>
