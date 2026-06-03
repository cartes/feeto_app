<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const marketingWhatsApp = computed(() => page.props.marketing_whatsapp ?? {});
</script>

<template>
    <a
        v-if="marketingWhatsApp.is_ready && marketingWhatsApp.href"
        :href="marketingWhatsApp.href"
        target="_blank"
        rel="noopener noreferrer nofollow"
        :aria-label="`Escribir por WhatsApp a ${marketingWhatsApp.number}`"
        class="fixed bottom-5 right-5 z-[9998] inline-flex items-center gap-3 rounded-full bg-[#25D366] p-3 text-white shadow-[0_20px_45px_rgba(11,56,31,0.32)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#1ebe5b] focus:outline-none focus:ring-4 focus:ring-[#25D366]/30 sm:bottom-6 sm:right-6 sm:px-4 sm:py-3"
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/14 ring-1 ring-white/20">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M19.05 4.94A9.94 9.94 0 0 0 12.03 2C6.56 2 2.11 6.45 2.1 11.91c0 1.75.46 3.47 1.32 4.99L2 22l5.24-1.37a9.9 9.9 0 0 0 4.76 1.21h.01c5.47 0 9.92-4.45 9.93-9.91A9.86 9.86 0 0 0 19.05 4.94Zm-7.04 15.23h-.01a8.23 8.23 0 0 1-4.19-1.15l-.3-.18-3.11.81.83-3.03-.2-.31a8.18 8.18 0 0 1-1.26-4.38c.01-4.52 3.7-8.2 8.24-8.2 2.2 0 4.26.85 5.82 2.41a8.17 8.17 0 0 1 2.4 5.82c-.02 4.52-3.7 8.21-8.22 8.21Zm4.51-6.16c-.25-.13-1.47-.72-1.7-.8-.23-.08-.4-.13-.57.12-.17.25-.65.8-.8.97-.15.17-.3.19-.56.06-.25-.13-1.08-.4-2.05-1.28-.76-.68-1.27-1.52-1.42-1.77-.15-.25-.02-.39.11-.52.12-.12.25-.3.38-.45.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.57-1.37-.78-1.87-.21-.5-.42-.43-.57-.43h-.49c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.09 0 1.22.9 2.41 1.02 2.57.13.17 1.77 2.71 4.28 3.79.6.26 1.07.41 1.43.52.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.17.21-.57.21-1.06.15-1.17-.06-.1-.23-.17-.48-.3Z" />
            </svg>
        </span>

        <span class="hidden min-w-[12rem] flex-col text-left sm:flex">
            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/72">WhatsApp</span>
            <span class="text-sm font-semibold leading-tight">Habla con TallerFlow</span>
            <span class="text-xs leading-tight text-white/80">{{ marketingWhatsApp.number }}</span>
        </span>
    </a>
</template>
