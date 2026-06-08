<script setup>
const downloadPdf = () => {
    window.print();
};
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-gray-800 print:hidden"
        @click="downloadPdf"
    >
        Descargar PDF
    </button>
</template>
