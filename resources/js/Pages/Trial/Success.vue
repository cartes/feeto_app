<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
</script>

<template>
    <Head title="Solicitud recibida · TallerFlow" />

    <PublicNav />

    <div class="min-h-screen bg-gray-50 font-sans antialiased flex flex-col">
        <!-- Contenido centrado -->
        <div class="flex-1 flex items-center justify-center px-4 pt-28 pb-16">
            <div class="text-center max-w-md">
                <div class="mx-auto mb-6 w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-black text-gray-900 tracking-tight mb-3">¡Solicitud recibida!</h1>
                <p class="text-gray-500 text-base leading-relaxed mb-8">
                    Tu solicitud de prueba gratuita fue registrada exitosamente.<br>
                    Nuestro equipo la revisará y te contactará o activará tu acceso en menos de 24 horas.
                </p>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-left mb-8 space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">Recibirás un correo con tus credenciales de acceso.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">14 días de acceso completo sin costo.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <p class="text-sm text-amber-800">Sin tarjeta de crédito requerida.</p>
                    </div>
                </div>

                <Link
                    :href="route('home')"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors font-medium"
                >
                    ← Volver al inicio
                </Link>
            </div>
        </div>
    </div>
</template>
