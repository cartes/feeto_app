<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenants: Array,
});

const toggleStatus = (tenant) => {
    if (confirm(`¿Estás seguro de que quieres cambiar el estado de este taller?`)) {
        router.put(route('admin.tenants.suspend', tenant.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestión de Talleres" />

    <AdminLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Talleres</h1>
                <p class="mt-1 text-sm text-slate-500">Lista de todos los talleres registrados en el SaaS (Tenants).</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors">
                    Crear Taller (Próximamente)
                </button>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nombre de Taller</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Slug / URL</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Plan</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Usuarios</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Estado</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Suscripción</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="tenant in tenants" :key="tenant.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        {{ tenant.name }}
                                        <div class="text-xs text-slate-500 font-normal">RUT: {{ tenant.rut_taller || 'N/D' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono text-slate-700">/taller/{{ tenant.slug }}</code>
                                            <a
                                                :href="'/taller/' + tenant.slug + '/dashboard'"
                                                target="_blank"
                                                class="text-slate-400 hover:text-amber-600 transition-colors"
                                                title="Abrir taller"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 uppercase tracking-wide">
                                            {{ tenant.plan || 'Básico' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ tenant.users_count || 0 }} / {{ tenant.max_users || '∞' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="tenant.status === 'active'" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            Activo
                                        </span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            Suspendido
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ tenant.subscription_ends_at ? new Date(tenant.subscription_ends_at).toLocaleDateString() : 'Ilimitada' }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button @click="toggleStatus(tenant)" :class="[tenant.status === 'active' ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900', 'mr-4 font-semibold']">
                                            {{ tenant.status === 'active' ? 'Suspender' : 'Activar' }}
                                        </button>
                                        <Link :href="route('admin.tenants.edit', tenant.id)" class="text-slate-600 hover:text-slate-900 font-semibold">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="tenants.length === 0">
                                    <td colspan="7" class="py-10 text-center text-sm text-slate-500">
                                        No hay talleres registrados aún.
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
