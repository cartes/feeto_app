<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plans: Array,
    featureDefinitions: Object,
});

const formatCLP = (value) => {
    if (!value && value !== 0) return '-';
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 0,
    }).format(value);
};

const deletePlan = (plan) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el plan "${plan.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('admin.plans.destroy', plan.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Planes de Suscripción" />

    <AdminLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Planes de Suscripción</h1>
                <p class="mt-1 text-sm text-slate-500">Administra los planes disponibles para los talleres.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <Link
                    :href="route('admin.plans.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Plan
                </Link>
            </div>
        </div>

        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nombre</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Precio Mensual</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Precio Anual</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Usuarios Max</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Módulos</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Trial</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Descuento</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Popular</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Activo</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="plan in plans" :key="plan.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="font-medium text-slate-900">{{ plan.name }}</div>
                                        <div v-if="plan.description" class="text-xs text-slate-400 mt-0.5 max-w-xs truncate">{{ plan.description }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-700 font-medium">
                                        {{ formatCLP(plan.price_monthly) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-700 font-medium">
                                        {{ formatCLP(plan.price_annual) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ plan.max_users ?? '∞' }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-slate-500">
                                        <div class="flex flex-wrap gap-1.5 max-w-xs">
                                            <span
                                                v-for="featureKey in (plan.feature_keys || [])"
                                                :key="featureKey"
                                                class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-[11px] font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20"
                                            >
                                                {{ featureDefinitions[featureKey]?.label || featureKey }}
                                            </span>
                                            <span v-if="!(plan.feature_keys || []).length" class="text-slate-300">—</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ plan.trial_days ? `${plan.trial_days}d` : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="plan.discount_percent" class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                            {{ plan.discount_percent }}%
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span v-if="plan.is_popular" class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">
                                            ⭐ Popular
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span v-if="plan.is_active" class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Activo
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Inactivo
                                        </span>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-4">
                                        <Link :href="route('admin.plans.edit', plan.id)" class="text-amber-600 hover:text-amber-900 font-semibold">Editar</Link>
                                        <button @click="deletePlan(plan)" class="text-rose-600 hover:text-rose-900 font-semibold">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="!plans || plans.length === 0">
                                    <td colspan="10" class="py-10 text-center text-sm text-slate-500">
                                        No hay planes creados aún.
                                        <Link :href="route('admin.plans.create')" class="ml-1 text-amber-600 hover:text-amber-800 font-medium">Crear el primero</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
