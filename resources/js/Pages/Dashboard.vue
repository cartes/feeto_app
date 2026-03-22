<script setup>
import { Head } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PatentCapture from '@/Components/PatentCapture.vue';
</script>

<template>
    <Head title="Dashboard" />

    <TallerLayout>
        
        <!-- Categorías (Row de bolitas) -->
        <div class="flex gap-4 overflow-x-auto py-2 mb-2 no-scrollbar">
            <div class="flex flex-col items-center gap-2 min-w-[70px]">
                <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center border-2 border-transparent">
                    <span class="text-xl">📊</span>
                </div>
                <span class="text-xs font-semibold text-slate-700">Resumen</span>
            </div>
            <div class="flex flex-col items-center gap-2 min-w-[70px]">
                <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center">
                    <span class="text-xl">🚙</span>
                </div>
                <span class="text-xs font-medium text-slate-500">Patentes</span>
            </div>
            <div class="flex flex-col items-center gap-2 min-w-[70px]">
                <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center">
                    <span class="text-xl">🛠️</span>
                </div>
                <span class="text-xs font-medium text-slate-500">Tickets</span>
            </div>
             <div class="flex flex-col items-center gap-2 min-w-[70px]">
                <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center">
                    <span class="text-xl">⚙️</span>
                </div>
                <span class="text-xs font-medium text-slate-500">Ajustes</span>
            </div>
        </div>

        <h3 class="text-xl font-bold text-slate-800 tracking-tight mt-2">Visión General</h3>

        <!-- Main Card 1 (Inspirada en "Your Nearby Bikes") -->
        <div class="bg-white/90 backdrop-blur-md rounded-[2rem] p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white relative overflow-hidden flex flex-col gap-4">
            <div class="absolute top-4 left-4 bg-[#F9A826] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full z-10">
                Acceso Rápido
            </div>
            <button class="absolute top-4 right-4 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm z-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>
            </button>
            
            <div class="h-40 bg-slate-100 rounded-2xl flex items-center justify-center mt-2 overflow-hidden">
                <PatentCapture />
            </div>

            <div class="bg-[#1C1C1E] text-white p-4 rounded-b-[1.5rem] rounded-t-xl -mx-2 -mb-2 shadow-inner">
                <h4 class="font-bold text-lg">Recepción Inteligente</h4>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-sm text-gray-400">Escáner de Patentes Activo</span>
                    <span class="text-xs flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> Online</span>
                </div>
            </div>
        </div>

    </TallerLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
