<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';
import { useStatusConfig } from '@/composables/useStatusConfig';
import { useDebounce } from '@/composables/useDebounce';

const props = defineProps({
    quotes: Object,
    filters: Object,
    quoteStatuses: Array,
});

const { page, tenantRouteParams } = useTenantRouting();
const { formatCurrency, formatDate } = useFormatting();
const { resolveQuoteStatus } = useStatusConfig();
const { debounce } = useDebounce();
const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);

const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('quotes.index', tenantRouteParams.value), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));
</script>

<template>
    <Head title="Cotizaciones" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Cotizaciones</h1>
                    <p class="mt-1 text-sm font-medium text-gray-500">Cotiza a clientes sin necesidad de una cita ni una Orden de Trabajo previa.</p>
                </div>

                <div class="flex w-full flex-col gap-4 sm:flex-row md:w-auto">
                    <div class="relative w-full sm:w-80" data-tour="quotes-search">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Buscar por cliente, RUT o patente..."
                            class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-gray-900 shadow-sm transition-all placeholder:text-gray-400 focus:border-[#FF7A00] focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/50" />
                    </div>

                    <Link
                        v-if="commercialQuotesEnabled"
                        :href="route('quotes.create', tenantRouteParams)"
                        data-tour="quotes-new"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-gray-900 px-5 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-[#FF7A00]"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Cotización
                    </Link>
                </div>
            </div>

            <div v-if="commercialQuotesEnabled" class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
                <div v-if="quotes.data.length === 0" class="p-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50">
                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14l-4-3-3 3-3-3-3 3-3-3-2 1.5V5a2 2 0 012-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Aún no hay cotizaciones manuales</h3>
                    <p class="mt-1 text-sm text-gray-500">Crea una nueva para cotizar a un cliente sin pasar por una OT.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Vehículo</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4">Creada</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="quote in quotes.data" :key="quote.id" class="group transition-colors hover:bg-gray-50/40">
                                <td class="px-6 py-4 align-top">
                                    <div class="text-sm font-bold uppercase text-gray-900">{{ quote.client?.name ?? 'Sin cliente' }}</div>
                                    <div class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ quote.client?.rut }}</div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-600">
                                    <span class="font-mono font-bold tracking-widest text-gray-700">{{ quote.vehicle?.plate }}</span>
                                    <p class="text-xs text-gray-400">{{ quote.vehicle?.brand }} {{ quote.vehicle?.model }}</p>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', resolveQuoteStatus(quote.status).classes]">
                                        {{ resolveQuoteStatus(quote.status).label }}
                                    </span>
                                    <p v-if="quote.work_order" class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-emerald-500">OT #{{ quote.work_order.id }} generada</p>
                                </td>
                                <td class="px-6 py-4 align-top text-sm font-black text-gray-900">{{ formatCurrency(quote.subtotal_amount) }}</td>
                                <td class="px-6 py-4 align-top text-sm font-medium text-gray-600">{{ formatDate(quote.created_at) }}</td>
                                <td class="px-6 py-4 text-right align-top">
                                    <Link :href="route('quotes.show', { ...tenantRouteParams, quote: quote.id })"
                                        class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-gray-600 transition-colors hover:bg-[#FF7A00] hover:text-white">
                                        Ver Detalle
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="quotes.links && quotes.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-gray-100 px-6 py-4">
                    <template v-for="(link, i) in quotes.links" :key="i">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="link.active ? 'bg-[#FF7A00] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'" />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm font-medium text-gray-400"></span>
                    </template>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/70 p-8">
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                <h2 class="mt-4 text-2xl font-black tracking-tight text-gray-900">Cotizaciones manuales bloqueadas para este plan</h2>
                <p class="mt-2 text-sm font-medium text-gray-600">{{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}</p>
            </div>
        </div>
    </TallerLayout>
</template>
