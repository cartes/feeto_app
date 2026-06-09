<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.tenants.index'), { search: val }, { preserveState: true, replace: true });
    }, 350);
});

const toggleStatus = (tenant) => {
    if (confirm(`¿Estás seguro de que quieres cambiar el estado de este taller?`)) {
        router.put(route('admin.tenants.suspend', tenant.id), {}, { preserveScroll: true });
    }
};

const goToPage = (url) => {
    if (url) router.get(url, {}, { preserveState: true });
};
</script>

<template>
    <Head title="Gestión de Talleres" />

    <AdminLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Talleres</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ tenants.total }} taller{{ tenants.total !== 1 ? 'es' : '' }} registrado{{ tenants.total !== 1 ? 's' : '' }}.
                </p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <Link
                    :href="route('admin.tenants.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear Taller
                </Link>
            </div>
        </div>

        <!-- Buscador -->
        <div class="mb-4">
            <div class="relative max-w-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, slug o RUT..."
                    class="w-full rounded-md border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400"
                />
            </div>
        </div>

        <div class="flow-root">
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
                                <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-50/50 transition-colors">
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
                                        {{ tenant.subscription_ends_at ? new Date(tenant.subscription_ends_at).toLocaleDateString('es-CL') : 'Ilimitada' }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-3">
                                        <Link
                                            :href="route('admin.tenants.activity', tenant.id)"
                                            class="text-indigo-600 hover:text-indigo-900 font-semibold"
                                        >
                                            Actividad
                                        </Link>
                                        <button @click="toggleStatus(tenant)" :class="[tenant.status === 'active' ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900', 'font-semibold']">
                                            {{ tenant.status === 'active' ? 'Suspender' : 'Activar' }}
                                        </button>
                                        <Link :href="route('admin.tenants.edit', tenant.id)" class="text-slate-600 hover:text-slate-900 font-semibold">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="tenants.data.length === 0">
                                    <td colspan="7" class="py-10 text-center text-sm text-slate-500">
                                        No se encontraron talleres.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div v-if="tenants.last_page > 1" class="mt-6 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                Mostrando {{ tenants.from }}–{{ tenants.to }} de {{ tenants.total }}
            </p>
            <div class="flex gap-1">
                <button
                    v-for="link in tenants.links"
                    :key="link.label"
                    @click="goToPage(link.url)"
                    :disabled="!link.url"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded text-sm transition-colors',
                        link.active
                            ? 'bg-slate-900 text-white font-semibold'
                            : link.url
                                ? 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
                                : 'bg-white border border-slate-200 text-slate-300 cursor-not-allowed',
                    ]"
                />
            </div>
        </div>
    </AdminLayout>
</template>
