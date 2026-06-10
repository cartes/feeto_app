<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PrintableReportShell from '@/Components/PrintableReportShell.vue';
import ReportsNavigation from '@/Components/ReportsNavigation.vue';
import ReportExportActions from '@/Components/ReportExportActions.vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import { useFormatting } from '@/composables/useFormatting';

const { tenantRouteParams } = useTenantRouting();
const { formatCurrency } = useFormatting();

defineProps({
    summary: Object,
    criticalProducts: Array,
    lowStockProducts: Array,
    reservedProducts: Array,
    highValueProducts: Array,
});
</script>

<template>
    <Head title="Reporte de Existencias" />

    <TallerLayout>
        <PrintableReportShell>
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.25em] text-gray-400">Operación</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight text-gray-900">Reporte de Existencias</h1>
                    <p class="mt-2 text-sm font-medium text-gray-500">Stock crítico, valorización del inventario y repuestos comprometidos.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('inventory.index', tenantRouteParams)"
                        class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Ver inventario
                    </Link>
                    <ReportExportActions report="inventory" />
                </div>
            </div>

            <ReportsNavigation />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Productos</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.total_products }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Unidades Físicas</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ summary.physical_units }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Reservadas</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.reserved_units }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Disponibles</p>
                    <p class="mt-3 text-4xl font-black text-emerald-600">{{ summary.available_units }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Stock Crítico</p>
                    <p class="mt-3 text-4xl font-black text-rose-500">{{ summary.critical_products }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Bajo Mínimo</p>
                    <p class="mt-3 text-4xl font-black text-amber-500">{{ summary.low_stock_products }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Valor a Costo</p>
                    <p class="mt-3 text-4xl font-black text-gray-900">{{ formatCurrency(summary.inventory_cost_value) }}</p>
                </div>
                <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Valor a Venta</p>
                    <p class="mt-3 text-4xl font-black text-cyan-600">{{ formatCurrency(summary.inventory_sales_value) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Stock Crítico</h2>
                    </div>

                    <div v-if="criticalProducts.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin productos agotados
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="product in criticalProducts"
                            :key="product.id"
                            :href="route('inventory.show', { ...tenantRouteParams, inventory: product.id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-rose-50/70 px-4 py-4 transition-colors hover:bg-rose-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ product.name }}</p>
                                <p class="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ product.sku }}</p>
                            </div>
                            <span class="text-sm font-black text-rose-500">{{ product.physical_stock }} un.</span>
                        </Link>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Bajo Mínimo</h2>
                    </div>

                    <div v-if="lowStockProducts.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin alertas activas
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="product in lowStockProducts"
                            :key="product.id"
                            :href="route('inventory.show', { ...tenantRouteParams, inventory: product.id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-amber-50/70 px-4 py-4 transition-colors hover:bg-amber-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ product.name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    mínimo {{ product.min_stock }} · disponible {{ product.available_stock }}
                                </p>
                            </div>
                            <span class="text-sm font-black text-amber-600">{{ product.physical_stock }} un.</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-5">
                <div class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm xl:col-span-3">
                    <div class="border-b border-gray-100 px-8 py-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Mayor Valor Inmovilizado</h2>
                    </div>

                    <div v-if="highValueProducts.length === 0" class="px-8 py-12 text-center text-sm font-medium text-gray-500">
                        No hay productos para analizar.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <th class="px-8 py-4 text-left">Producto</th>
                                    <th class="px-4 py-4 text-right">Stock</th>
                                    <th class="px-4 py-4 text-right">Costo</th>
                                    <th class="px-4 py-4 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="product in highValueProducts" :key="product.id" class="transition-colors hover:bg-gray-50/40">
                                    <td class="px-8 py-4 text-sm font-semibold text-gray-700">{{ product.name }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ product.physical_stock }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-medium text-gray-500">{{ formatCurrency(product.cost_price) }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-black text-gray-900">{{ formatCurrency(product.stock_cost_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm xl:col-span-2">
                    <div class="mb-6">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400">Stock Reservado</h2>
                    </div>

                    <div v-if="reservedProducts.length === 0" class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Sin repuestos comprometidos
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="product in reservedProducts"
                            :key="product.id"
                            :href="route('inventory.show', { ...tenantRouteParams, inventory: product.id })"
                            class="flex items-center justify-between rounded-2xl border border-gray-100 bg-gray-50/70 px-4 py-4 transition-colors hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ product.name }}</p>
                                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ product.available_stock }} disponibles reales
                                </p>
                            </div>
                            <span class="text-sm font-black text-amber-500">{{ product.reserved_stock }} reservadas</span>
                        </Link>
                    </div>
                </div>
            </div>
        </PrintableReportShell>
    </TallerLayout>
</template>
