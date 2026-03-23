<script setup>
import { Head } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
</script>

<template>
    <Head title="Directorio de Clientes" />

    <TallerLayout>
        <div class="bg-white p-8 sm:p-12 text-center rounded-[2.5rem] shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-gray-100 min-h-[60vh] flex flex-col items-center justify-center animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="w-20 h-20 bg-gray-50 rounded-2xl flex items-center justify-center mb-6">
                <svg viewBox="0 0 24 24" fill="none" class="w-10 h-10 stroke-gray-400" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight mb-3">Directorio de Clientes</h1>
            <p class="text-lg font-medium text-gray-500 max-w-md">
                Este módulo se encuentra actualmente en construcción. Pronto podrás gestionar la información de tus clientes desde aquí.
            </p>
        </div>
    </TallerLayout>
</template>
