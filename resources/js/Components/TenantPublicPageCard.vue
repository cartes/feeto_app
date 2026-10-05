<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    publicUrl: { type: String, required: true },
    settingsUrl: { type: String, default: null },
});
const copyMessage = ref('');
const copying = ref(false);
const whatsappUrl = computed(() => `https://wa.me/?text=${encodeURIComponent(`Conoce nuestro taller y solicita tu cita aquí: ${props.publicUrl}`)}`);

const copyLink = async () => {
    copying.value = true;
    try {
        await navigator.clipboard.writeText(props.publicUrl);
        copyMessage.value = 'Enlace copiado. Ya puedes compartirlo con tus clientes.';
    } catch {
        copyMessage.value = 'No pudimos copiar el enlace. Selecciónalo en el campo y cópialo manualmente.';
    } finally {
        copying.value = false;
    }
};
</script>

<template>
    <section class="rounded-2xl border border-orange-200 bg-orange-50 p-5 sm:p-6" aria-label="Tu página de reservas">
        <p class="text-xs font-bold uppercase tracking-wide text-orange-700">Incluida con tu taller</p>
        <h2 class="mt-2 text-xl font-bold text-gray-900">Tu página de reservas ya está creada</h2>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">Tus clientes pueden conocer tu taller y solicitar una cita desde este enlace. Revisa los datos y horarios antes de compartirla.</p>
        <label class="mt-4 block text-sm font-semibold text-gray-700">
            Enlace para tus clientes
            <input :value="publicUrl" readonly @focus="$event.target.select()" class="mt-2 block w-full rounded-xl border-gray-200 bg-white text-sm text-gray-700" />
        </label>
        <div class="mt-4 flex flex-wrap gap-3">
            <a :href="publicUrl" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-[#FF7A00] px-4 py-3 text-sm font-bold text-white hover:bg-[#CC6200]">Ver mi página</a>
            <button type="button" :disabled="copying" @click="copyLink" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">{{ copying ? 'Copiando…' : 'Copiar enlace' }}</button>
            <a :href="whatsappUrl" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Compartir por WhatsApp</a>
            <Link v-if="settingsUrl" :href="settingsUrl" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Personalizar</Link>
        </div>
        <p v-if="copyMessage" role="status" class="mt-3 text-sm text-gray-700">{{ copyMessage }}</p>
    </section>
</template>
