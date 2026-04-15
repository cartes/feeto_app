<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    logs: Object,
});

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('es-CL');
};

const modelBasename = (modelType) => {
    if (!modelType) return '-';
    return modelType.split('\\').pop();
};

const actionBadgeClass = (action) => {
    if (!action) return 'bg-slate-100 text-slate-600 ring-slate-500/10';
    if (action.startsWith('profile.')) return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    if (action.startsWith('password.')) return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
    if (action.startsWith('plan.')) return 'bg-purple-50 text-purple-700 ring-purple-600/20';
    if (action.startsWith('api_keys.')) return 'bg-orange-50 text-orange-700 ring-orange-600/20';
    return 'bg-slate-100 text-slate-600 ring-slate-500/10';
};
</script>

<template>
    <Head title="Auditoría" />

    <AdminLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Registro de Auditoría</h1>
            <p class="mt-1 text-sm text-slate-500">Historial de acciones realizadas en el sistema.</p>
        </div>

        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Fecha</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Usuario</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Acción</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Descripción</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">IP</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Modelo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="log in logs.data" :key="log.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-500 sm:pl-6">
                                        {{ formatDateTime(log.created_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <div class="font-medium text-slate-900">{{ log.user?.name ?? '—' }}</div>
                                        <div class="text-xs text-slate-400">{{ log.user?.email ?? '' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset', actionBadgeClass(log.action)]">
                                            {{ log.action ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-slate-500 max-w-xs">
                                        <span class="line-clamp-2">{{ log.description ?? '—' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ log.ip ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="log.model_type" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                            {{ modelBasename(log.model_type) }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                </tr>
                                <tr v-if="!logs.data || logs.data.length === 0">
                                    <td colspan="6" class="py-10 text-center text-sm text-slate-500">
                                        No hay registros de auditoría disponibles.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="logs.links && logs.links.length > 3" class="mt-6 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                Mostrando <span class="font-medium text-slate-900">{{ logs.from }}</span>–<span class="font-medium text-slate-900">{{ logs.to }}</span>
                de <span class="font-medium text-slate-900">{{ logs.total }}</span> registros
            </p>
            <nav class="flex gap-1" aria-label="Paginación">
                <template v-for="link in logs.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'bg-orange-500 text-white border-orange-500'
                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                            'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border transition-colors'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        :class="[
                            link.active ? 'bg-orange-500 text-white' : 'bg-slate-50 text-slate-400',
                            'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-slate-200'
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AdminLayout>
</template>
