<script setup>
import { Head } from '@inertiajs/vue3';
import WorkOrderQuote from '@/Components/WorkOrderQuote.vue';

const props = defineProps({
    workOrder: Object,
});

const steps = [
    { id: 'recepcion', title: 'Recepción', description: 'Vehículo ingresado y registrado en el taller.' },
    { id: 'diagnostico', title: 'En Diagnóstico', description: 'Estamos evaluando el estado de tu vehículo.' },
    { id: 'esperando_repuestos', title: 'Esperando Repuestos', description: 'Buscando y esperando las piezas necesarias.' },
    { id: 'listo', title: 'Listo para Entrega', description: 'Tu vehículo está listo para ser retirado.' }
];

const getStepIndex = (status) => {
    return steps.findIndex(s => s.id === status);
};

const currentIndex = getStepIndex(props.workOrder.status);

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-CL', {
        day: '2-digit', month: 'long', year: 'numeric'
    });
};

const whatsappText = encodeURIComponent(`Hola, quisiera consultar sobre la OT #${props.workOrder.id} de mi vehículo
${props.workOrder.vehicle?.plate}.`);
const wsLink = `https://wa.me/?text=${whatsappText}`; // Asumiendo número principal o que lo abran para elegir si no se pasa número

</script>

<template>

    <Head :title="`Seguimiento | ${workOrder.vehicle?.plate}`" />

    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-start font-sans pb-10">
        <!-- Header Público -->
        <header class="w-full bg-white shadow-sm sticky top-0 z-50">
            <div class="max-w-md mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#F9A826] rounded-xl flex items-center justify-center rotate-3">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-black text-gray-900 tracking-tight text-lg leading-none">Mi Taller</h1>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Seguimiento en línea
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content (Mobile Optimized) -->
        <main class="w-full max-w-md mx-auto px-6 mt-8 flex-1 flex flex-col">

            <!-- Resumen Vehículo -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-8 w-full">
                <div class="flex justify-between items-start mb-4 pb-4 border-b border-gray-50">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Orden de Trabajo
                        </p>
                        <p class="text-2xl font-black text-gray-900">#{{ workOrder.id }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-xl shadow-inner text-center">
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Patente</p>
                        <p class="text-lg font-mono font-black tracking-widest text-[#F9A826] leading-none mt-0.5">{{
                            workOrder.vehicle?.plate }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-bold text-gray-800 text-sm uppercase">{{ workOrder.vehicle?.brand }} {{
                        workOrder.vehicle?.model }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 font-medium">Ingresado el {{ formatDate(workOrder.created_at)
                    }}</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 w-full mb-8 relative">
                <WorkOrderQuote :workOrder="workOrder" />
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 w-full mb-8 relative">
                <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest mb-6">Estado de la Reparación</h3>

                <div class="relative pl-6 space-y-8">
                    <!-- Linea de fondo -->
                    <div class="absolute left-[-1px] top-2 bottom-6 w-0.5 bg-gray-100 ml-8"></div>

                    <div v-for="(step, index) in steps" :key="step.id" class="relative z-10">
                        <div class="flex items-start gap-4">
                            <!-- Nodo -->
                            <div class="relative flex-shrink-0">
                                <template v-if="index < currentIndex">
                                    <div
                                        class="w-6 h-6 rounded-full bg-emerald-500 border-4 border-white shadow flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </template>
                                <template v-else-if="index === currentIndex">
                                    <div
                                        class="w-6 h-6 rounded-full bg-white border-4 border-[#F9A826] shadow ring-4 ring-[#F9A826]/20 flex items-center justify-center">
                                        <div class="w-2 h-2 rounded-full bg-[#F9A826] animate-pulse"></div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="w-6 h-6 rounded-full bg-white border-4 border-gray-200"></div>
                                </template>
                            </div>

                            <!-- Contenido -->
                            <div class="flex flex-col pb-2">
                                <h4 class="text-sm font-black uppercase tracking-wide"
                                    :class="index === currentIndex ? 'text-[#F9A826]' : (index < currentIndex ? 'text-gray-900' : 'text-gray-400')">
                                    {{ step.title }}
                                </h4>
                                <p class="text-[11px] font-medium mt-1 leading-relaxed"
                                    :class="index === currentIndex ? 'text-gray-600' : 'text-gray-400'">
                                    {{ step.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones actuales si las hay -->
                <div v-if="workOrder.observations" class="mt-8 p-4 bg-amber-50 rounded-2xl border border-amber-100/50">
                    <p
                        class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Observaciones del Taller
                    </p>
                    <p class="text-xs text-amber-800 font-medium leading-relaxed">{{ workOrder.observations }}</p>
                </div>
            </div>

            <div class="mt-auto"></div> <!-- Spacer -->

            <!-- Boton CTA WhatsApp -->
            <a :href="wsLink" target="_blank"
                class="w-full bg-[#25D366] hover:bg-[#128C7E] text-white px-6 py-4 rounded-full font-black text-sm uppercase tracking-wider flex items-center justify-center gap-3 shadow-[0_10px_20px_rgba(37,211,102,0.3)] transition-all active:scale-95">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
                Contactar al Taller
            </a>

        </main>
    </div>
</template>
