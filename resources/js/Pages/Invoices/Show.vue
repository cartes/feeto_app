<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

const props = defineProps({
    invoice: Object,
});

const formatCurrency = (value) => new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const canSendManualWhatsApp = computed(() => Boolean(props.invoice.manual_whatsapp_url));
</script>

<template>
    <Head :title="invoice.invoice_number || `Factura #${invoice.id}`" />

    <TallerLayout>
        <div class="space-y-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <Link :href="route('invoices.index', tenantRouteParams)" class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 transition-colors hover:text-gray-700">
                        Volver a facturas
                    </Link>
                    <h1 class="mt-3 text-3xl font-black tracking-tight text-gray-900">{{ invoice.invoice_number || `Factura #${invoice.id}` }}</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">{{ invoice.client?.name }} · vence {{ formatDate(invoice.due_at) }}</p>
                </div>

                <a
                    v-if="canSendManualWhatsApp"
                    :href="invoice.manual_whatsapp_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-2xl bg-green-500 px-5 py-3 text-sm font-black text-white transition-colors hover:bg-green-600"
                >
                    Seguimiento por WhatsApp
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Estado</p>
                    <p class="mt-3 text-3xl font-black text-gray-900">{{ invoice.status }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Monto Total</p>
                    <p class="mt-3 text-3xl font-black text-gray-900">{{ formatCurrency(invoice.amount_total) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Saldo Pendiente</p>
                    <p class="mt-3 text-3xl font-black text-rose-500">{{ formatCurrency(invoice.amount_due) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-3">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Detalle</h2>

                    <dl class="mt-6 space-y-5">
                        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-4">
                            <dt class="text-sm font-bold text-gray-500">Cliente</dt>
                            <dd class="text-sm font-black text-gray-900">{{ invoice.client?.name || 'Sin cliente' }}</dd>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-4">
                            <dt class="text-sm font-bold text-gray-500">Teléfono</dt>
                            <dd class="text-sm font-black text-gray-900">{{ invoice.client?.phone || 'No registrado' }}</dd>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-4">
                            <dt class="text-sm font-bold text-gray-500">Fecha de emisión</dt>
                            <dd class="text-sm font-black text-gray-900">{{ formatDate(invoice.issued_at) }}</dd>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-4">
                            <dt class="text-sm font-bold text-gray-500">Fecha de vencimiento</dt>
                            <dd class="text-sm font-black text-gray-900">{{ formatDate(invoice.due_at) }}</dd>
                        </div>
                        <div v-if="invoice.work_order" class="flex items-center justify-between rounded-2xl bg-gray-50 px-4 py-4">
                            <dt class="text-sm font-bold text-gray-500">Orden de trabajo</dt>
                            <dd class="text-sm font-black text-gray-900">#{{ invoice.work_order.id }} · {{ invoice.work_order.plate || 'Sin patente' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cobranza</h2>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Recordatorios enviados</p>
                            <p class="mt-2 text-3xl font-black text-emerald-900">{{ invoice.whatsapp_reminder_count }}</p>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">Último recordatorio</p>
                            <p class="mt-2 text-sm font-black text-gray-900">
                                {{ invoice.last_whatsapp_reminder_sent_at ? formatDate(invoice.last_whatsapp_reminder_sent_at) : 'Sin envíos aún' }}
                            </p>
                        </div>

                        <div v-if="invoice.notes" class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">Notas</p>
                            <p class="mt-2 text-sm font-medium text-gray-700">{{ invoice.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
