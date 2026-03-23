<script setup>
import { Head } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
</script>

<template>

    <Head title="Inventario de Stock" />

    <TallerLayout>
        <div
            class="bg-white p-8 sm:p-12 text-center rounded-[2.5rem] shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-gray-100 min-h-[60vh] flex flex-col items-center justify-center animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="w-20 h-20 bg-gray-50 rounded-2xl flex items-center justify-center mb-6">
                <svg viewBox="0 0 24 24" fill="none" class="w-10 h-10 stroke-gray-400" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                    <line x1="12" y1="22.08" x2="12" y2="12" />
                </svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight mb-3">Inventario de Stock</h1>
            <p class="text-lg font-medium text-gray-500 max-w-md">
                Este módulo se encuentra actualmente en construcción. Pronto podrás gestionar tus repuestos e insumos
                desde aquí.
            </p>
        </div>
    </TallerLayout>
</template>
