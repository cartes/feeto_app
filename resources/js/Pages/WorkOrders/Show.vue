<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import { useStatusConfig } from '@/composables/useStatusConfig';
import QuoteSidebar from './Partials/QuoteSidebar.vue';
import QuoteItemsTable from './Partials/QuoteItemsTable.vue';
import AddQuoteItemForm from './Partials/AddQuoteItemForm.vue';
import QuoteHistoryCard from './Partials/QuoteHistoryCard.vue';
import EditCatalogItemModal from './Partials/EditCatalogItemModal.vue';
import ApproveManuallyModal from './Partials/ApproveManuallyModal.vue';
import DeleteWorkOrderModal from './Partials/DeleteWorkOrderModal.vue';

const page = usePage();

const { resolveWorkOrderStatus, resolveQuoteStatus } = useStatusConfig();

const props = defineProps({
    workOrder: Object,
    taxName: {
        type: String,
        default: 'IVA',
    },
    defaultTaxRate: {
        type: Number,
        default: 19,
    },
    products: Array,
    services: Array,
    discountPolicy: Object,
    ufValue: {
        type: Number,
        default: null,
    },
});

const planAccess = computed(() => page.props.planAccess ?? null);
const commercialQuotesEnabled = computed(() => planAccess.value?.commercial_quotes_enabled ?? false);
const roles = computed(() => page.props.auth?.user?.roles ?? []);
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const isSuperAdmin = computed(() => Boolean(page.props.auth?.user?.is_super_admin));

const canManageItems = computed(() => (
    isSuperAdmin.value || permissions.value.includes('work-orders.manage-items')
));

const canManageInventory = computed(() => (
    isSuperAdmin.value ||
    permissions.value.includes('inventory.manage') ||
    roles.value.includes('Admin') ||
    roles.value.includes('Supervisor') ||
    roles.value.includes('Dueño') ||
    roles.value.includes('Jefe')
));

const canDeleteWorkOrder = computed(() => (
    isSuperAdmin.value || permissions.value.includes('work-orders.delete')
));

const quote = computed(() => props.workOrder.quote ?? {
    status: 'draft',
    subtotal_amount: props.workOrder.total_amount ?? 0,
    items: [],
    events: [],
});

const items = computed(() => quote.value.items ?? []);

const workOrderStatus = computed(() => resolveWorkOrderStatus(props.workOrder.status));

const quoteStatus = computed(() => resolveQuoteStatus(quote.value.status));

const canDeliverQuote = computed(() => (
    isSuperAdmin.value || roles.value.some((role) => ['Admin', 'Supervisor', 'Jefe'].includes(role))
));
const canNotifyAdmin = computed(() => (
    !canDeliverQuote.value && items.value.length > 0 && !['pending_customer', 'accepted'].includes(quote.value.status)
));
const canShareQuote = computed(() => quote.value.status === 'pending_customer');

const showCatalogEditModal = ref(false);
const editingCatalogItem = ref(null);

const openEditCatalogItemModal = (item) => {
    editingCatalogItem.value = item;
    showCatalogEditModal.value = true;
};

const showApproveManuallyModal = ref(false);
const showDeleteModal = ref(false);
</script>

<template>
    <Head :title="`OT #${workOrder.id} — ${workOrder.vehicle?.plate ?? ''}`" />

    <TallerLayout>
        <div class="animate-in fade-in slide-in-from-bottom-4 duration-700 space-y-6">

            <!-- Header compacto sin card -->
            <div class="space-y-3">
                <Link
                    :href="route('work-orders.index')"
                    class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-gray-400 transition-colors hover:text-gray-700"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    Tablero Kanban
                </Link>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', workOrderStatus.classes]">
                            {{ workOrderStatus.label }}
                        </span>
                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest', quoteStatus.classes]">
                            Cotización {{ quoteStatus.label }}
                        </span>
                    </div>

                    <button
                        v-if="canDeleteWorkOrder"
                        type="button"
                        class="rounded-xl bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1.5"
                        @click="showDeleteModal = true"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar OT
                    </button>
                </div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-gray-900">
                            {{ workOrder.vehicle?.brand }} {{ workOrder.vehicle?.model }}
                        </h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                            <span class="font-mono text-base font-bold tracking-widest text-gray-700">{{ workOrder.vehicle?.plate }}</span>
                            <span class="text-gray-300">·</span>
                            <span>{{ workOrder.vehicle?.client?.name ?? 'Cliente no asignado' }}</span>
                            <template v-if="workOrder.vehicle?.client?.phone">
                                <span class="text-gray-300">·</span>
                                <span>{{ workOrder.vehicle.client.phone }}</span>
                            </template>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-black uppercase tracking-widest text-gray-300">OT #{{ workOrder.id }}</span>
                </div>

                <p v-if="workOrder.observations" class="text-sm italic text-gray-400">
                    {{ workOrder.observations }}
                </p>

                <div v-if="quote.customer_response_notes" class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-500">Respuesta Cliente</p>
                    <p class="mt-1.5 text-sm font-medium text-amber-900">{{ quote.customer_response_notes }}</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div v-if="commercialQuotesEnabled" class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px] xl:items-start">

                <!-- SIDEBAR: primero en DOM → aparece arriba en mobile, columna derecha en desktop -->
                <QuoteSidebar
                    :work-order="workOrder"
                    :quote="quote"
                    :items="items"
                    :uf-value="ufValue"
                    :tax-name="taxName"
                    :default-tax-rate="defaultTaxRate"
                    :can-deliver-quote="canDeliverQuote"
                    :can-notify-admin="canNotifyAdmin"
                    :can-share-quote="canShareQuote"
                    :can-manage-items="canManageItems"
                    @open-approve-modal="showApproveManuallyModal = true"
                />

                <!-- CONTENIDO PRINCIPAL: tabla + formulario -->
                <div class="space-y-6 xl:order-first">
                    <QuoteItemsTable
                        :work-order-id="workOrder.id"
                        :items="items"
                        :subtotal-amount="quote.subtotal_amount"
                        :tax-amount="quote.tax_amount"
                        :total-amount="quote.total_amount"
                        :apply-tax="quote.apply_tax"
                        :tax-rate="quote.tax_rate ?? defaultTaxRate"
                        :tax-name="taxName"
                        :uf-value="ufValue"
                        :can-manage-items="canManageItems"
                        :can-manage-inventory="canManageInventory"
                        @edit-catalog-item="openEditCatalogItemModal"
                    />

                    <AddQuoteItemForm
                        v-if="canManageItems"
                        :work-order-id="workOrder.id"
                        :products="products"
                        :services="services"
                        :discount-policy="discountPolicy"
                    />

                    <!-- Historial (solo mobile; en desktop va en el sidebar) -->
                    <QuoteHistoryCard :events="quote.events" variant="mobile" />
                </div>
            </div>

            <!-- Banner upgrade -->
            <div v-else class="rounded-2xl border border-dashed border-orange-200 bg-orange-50/70 p-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="max-w-2xl">
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-widest text-orange-600">Upgrade</span>
                        <h2 class="mt-4 text-2xl font-black tracking-tight text-gray-900">Cotizaciones comerciales bloqueadas para este plan</h2>
                        <p class="mt-2 text-sm font-medium text-gray-600">
                            Este taller puede seguir operando con órdenes de trabajo, pero el flujo comercial avanzado de servicios, cotización formal y aprobación del cliente está reservado para planes superiores.
                        </p>
                        <p class="mt-3 text-sm font-semibold text-orange-700">{{ planAccess?.upgrade_messages?.commercial_quotes_enabled }}</p>
                    </div>
                    <div class="rounded-2xl border border-orange-100 bg-white px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Plan actual</p>
                        <p class="mt-2 text-lg font-black text-gray-900">{{ planAccess?.plan_name || 'Sin plan' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <EditCatalogItemModal v-model:show="showCatalogEditModal" :item="editingCatalogItem" :tax-name="taxName" :default-tax-rate="defaultTaxRate" />
        <ApproveManuallyModal v-model:show="showApproveManuallyModal" :work-order-id="workOrder.id" />
        <DeleteWorkOrderModal v-model:show="showDeleteModal" :work-order="workOrder" />
    </TallerLayout>
</template>
