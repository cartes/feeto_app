<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenants: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const sortBy = ref(props.filters?.sort_by ?? 'usage');
const sortDirection = ref(props.filters?.sort_direction ?? 'desc');
const perPage = ref(props.filters?.per_page ?? 25);

let searchTimeout = null;

const applyFilters = (overrides = {}) => {
    const params = {
        search: search.value ? search.value.trim() : undefined,
        sort_by: sortBy.value,
        sort_direction: sortDirection.value,
        per_page: perPage.value,
        ...overrides,
    };

    Object.keys(params).forEach((key) => {
        if (params[key] === undefined || params[key] === '') {
            delete params[key];
        }
    });

    router.get(route('admin.tenants.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters({ page: 1 });
    }, 350);
});

const clearSearch = () => {
    search.value = '';
    applyFilters({ page: 1 });
};

const handleSort = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        if (['usage', 'work_orders_count', 'users_count', 'subscription_ends_at', 'created_at'].includes(column)) {
            sortDirection.value = 'desc';
        } else {
            sortDirection.value = 'asc';
        }
    }
    applyFilters({ page: 1 });
};

const changePerPage = (size) => {
    if (perPage.value === size) return;
    perPage.value = size;
    applyFilters({ page: 1 });
};

const toggleStatus = (tenant) => {
    if (confirm(`¿Estás seguro de que quieres cambiar el estado de este taller?`)) {
        router.put(route('admin.tenants.suspend', tenant.id), {}, { preserveScroll: true });
    }
};

const goToPage = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const getSortLabel = () => {
    const labels = {
        usage: 'Mayor Uso / Actividad',
        work_orders_count: 'Órdenes de Trabajo (OTs)',
        name: 'Nombre de Taller',
        slug: 'Slug / URL',
        plan: 'Plan',
        users_count: 'Cantidad de Usuarios',
        status: 'Estado',
        subscription_ends_at: 'Vencimiento de Suscripción',
        created_at: 'Fecha de Registro',
    };
    return labels[sortBy.value] || sortBy.value;
};
</script>

<template>
    <Head title="Gestión de Talleres" />

    <AdminLayout>
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Talleres</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ tenants.total }} taller{{ tenants.total !== 1 ? 'es' : '' }} registrado{{ tenants.total !== 1 ? 's' : '' }}.
                </p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <Link
                    :href="route('admin.tenants.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear Taller
                </Link>
            </div>
        </div>

        <!-- Filtros, Buscador y Controles -->
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <!-- Buscador -->
            <div class="relative flex-1 max-w-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, RUT, slug, teléfono, correo de admin..."
                    class="w-full rounded-lg border border-slate-200 bg-slate-50/50 py-2 pl-10 pr-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors"
                    title="Limpiar búsqueda"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Selector de Cantidad por Página & Estado de Orden -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-sm">
                <!-- Indicador de Orden Activo -->
                <div class="hidden lg:flex items-center gap-1.5 text-xs text-slate-500 bg-slate-100 px-2.5 py-1.5 rounded-lg border border-slate-200/60">
                    <span class="font-medium text-slate-700">Orden:</span>
                    <span>{{ getSortLabel() }}</span>
                    <span class="font-mono text-slate-600 font-bold">({{ sortDirection === 'asc' ? '↑ Asc' : '↓ Desc' }})</span>
                </div>

                <!-- Selector de Paginación (25, 50, 100) -->
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-slate-500 font-medium whitespace-nowrap">Mostrar:</span>
                    <div class="inline-flex rounded-lg shadow-sm border border-slate-200 p-0.5 bg-slate-50">
                        <button
                            v-for="size in [25, 50, 100]"
                            :key="size"
                            @click="changePerPage(size)"
                            type="button"
                            :class="[
                                'px-2.5 py-1 text-xs font-semibold rounded-md transition-colors',
                                perPage === size
                                    ? 'bg-slate-900 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60',
                            ]"
                        >
                            {{ size }}
                        </button>
                    </div>
                    <span class="text-xs text-slate-400">talleres</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Talleres -->
        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-xs ring-1 ring-slate-900/5 sm:rounded-xl bg-white">
                        <table class="min-w-full divide-y divide-slate-200 text-left">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <!-- Nombre de Taller -->
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-sm sm:pl-6">
                                        <button
                                            @click="handleSort('name')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Nombre de Taller
                                            <span :class="[sortBy === 'name' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'name' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'name' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Slug / URL -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('slug')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Slug / URL
                                            <span :class="[sortBy === 'slug' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'slug' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'slug' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Plan -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('plan')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Plan
                                            <span :class="[sortBy === 'plan' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'plan' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'plan' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Usuarios -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('users_count')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Usuarios
                                            <span :class="[sortBy === 'users_count' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'users_count' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'users_count' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Uso / Actividad -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('usage')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                            title="Ordenar por actividad global del taller (OTs, Citas, Logins)"
                                        >
                                            Uso / OTs
                                            <span :class="[sortBy === 'usage' || sortBy === 'work_orders_count' ? 'text-amber-700 font-bold' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="(sortBy === 'usage' || sortBy === 'work_orders_count') && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="(sortBy === 'usage' || sortBy === 'work_orders_count') && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Estado -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('status')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Estado
                                            <span :class="[sortBy === 'status' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'status' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'status' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Suscripción -->
                                    <th scope="col" class="px-3 py-3.5 text-sm">
                                        <button
                                            @click="handleSort('subscription_ends_at')"
                                            type="button"
                                            class="group inline-flex items-center gap-1.5 font-semibold text-slate-900 hover:text-slate-700"
                                        >
                                            Suscripción
                                            <span :class="[sortBy === 'subscription_ends_at' ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600', 'transition-colors']">
                                                <svg v-if="sortBy === 'subscription_ends_at' && sortDirection === 'asc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="sortBy === 'subscription_ends_at' && sortDirection === 'desc'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-40 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M5 8l5-5 5 5H5zM5 12l5 5 5-5H5z" />
                                                </svg>
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Acciones -->
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-sm font-semibold text-slate-900">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-50/70 transition-colors">
                                    <!-- Nombre / RUT / Dominio -->
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        <div class="font-semibold text-slate-900">{{ tenant.name }}</div>
                                        <div class="text-xs text-slate-500 font-normal mt-0.5 flex items-center gap-2">
                                            <span>RUT: {{ tenant.rut_taller || 'N/D' }}</span>
                                            <span v-if="tenant.domain" class="text-slate-400">• {{ tenant.domain }}</span>
                                        </div>
                                    </td>

                                    <!-- Slug / URL -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono text-slate-700">/taller/{{ tenant.slug }}</code>
                                            <a
                                                :href="'/taller/' + tenant.slug + '/dashboard'"
                                                target="_blank"
                                                class="text-slate-400 hover:text-amber-600 transition-colors p-0.5 rounded hover:bg-slate-100"
                                                title="Abrir taller en nueva pestaña"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Plan -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-500/10 uppercase tracking-wide">
                                            {{ tenant.plan || 'Básico' }}
                                        </span>
                                    </td>

                                    <!-- Usuarios -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <div class="font-medium text-slate-900">
                                            {{ tenant.users_count || 0 }} <span class="text-slate-400 font-normal">/ {{ tenant.max_users || '∞' }}</span>
                                        </div>
                                    </td>

                                    <!-- Uso / OTs -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1 font-semibold text-xs px-2 py-0.5 rounded-md font-mono border',
                                                    (tenant.work_orders_count || 0) > 0
                                                        ? 'bg-amber-50 text-amber-900 border-amber-200'
                                                        : 'bg-slate-50 text-slate-500 border-slate-200',
                                                ]"
                                                title="Órdenes de Trabajo creadas"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                {{ tenant.work_orders_count || 0 }} OT{{ (tenant.work_orders_count || 0) !== 1 ? 's' : '' }}
                                            </span>
                                            <span v-if="(tenant.appointments_count || 0) > 0" class="text-xs text-slate-500" title="Citas registradas">
                                                {{ tenant.appointments_count }} citas
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Estado -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="tenant.status === 'active'" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Activo
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Suspendido
                                        </span>
                                    </td>

                                    <!-- Suscripción -->
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <div v-if="tenant.subscription_ends_at" class="text-xs font-medium">
                                            {{ new Date(tenant.subscription_ends_at).toLocaleDateString('es-CL') }}
                                        </div>
                                        <span v-else class="text-xs font-medium text-slate-400">
                                            Ilimitada
                                        </span>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-3">
                                        <Link
                                            :href="route('admin.tenants.activity', tenant.id)"
                                            class="text-indigo-600 hover:text-indigo-900 font-semibold transition-colors"
                                        >
                                            Actividad
                                        </Link>
                                        <button
                                            @click="toggleStatus(tenant)"
                                            :class="[
                                                tenant.status === 'active' ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900',
                                                'font-semibold transition-colors',
                                            ]"
                                        >
                                            {{ tenant.status === 'active' ? 'Suspender' : 'Activar' }}
                                        </button>
                                        <Link
                                            :href="route('admin.tenants.edit', tenant.id)"
                                            class="text-slate-600 hover:text-slate-900 font-semibold transition-colors"
                                        >
                                            Editar
                                        </Link>
                                    </td>
                                </tr>

                                <!-- Empty state -->
                                <tr v-if="tenants.data.length === 0">
                                    <td colspan="8" class="py-12 text-center text-sm text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <p class="font-medium text-slate-700">No se encontraron talleres</p>
                                            <p class="text-xs text-slate-400 mt-1">
                                                {{ search ? 'Intenta con otro término de búsqueda o limpia el filtro.' : 'No hay talleres registrados en la plataforma.' }}
                                            </p>
                                            <button
                                                v-if="search"
                                                @click="clearSearch"
                                                type="button"
                                                class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                            >
                                                Limpiar búsqueda
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginación y Resumen -->
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-sm text-slate-500">
                Mostrando <span class="font-semibold text-slate-700">{{ tenants.from || 0 }}</span> a
                <span class="font-semibold text-slate-700">{{ tenants.to || 0 }}</span> de
                <span class="font-semibold text-slate-700">{{ tenants.total }}</span> talleres
            </p>

            <!-- Botones de Paginación -->
            <div v-if="tenants.last_page > 1" class="flex items-center gap-1">
                <button
                    v-for="(link, index) in tenants.links"
                    :key="index"
                    @click="goToPage(link.url)"
                    :disabled="!link.url"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-sm transition-all select-none',
                        link.active
                            ? 'bg-slate-900 text-white font-semibold shadow-xs'
                            : link.url
                                ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium'
                                : 'bg-slate-50 border border-slate-200/60 text-slate-300 cursor-not-allowed',
                    ]"
                />
            </div>
        </div>
    </AdminLayout>
</template>
