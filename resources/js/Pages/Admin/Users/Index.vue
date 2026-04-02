<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: Object,
});
</script>

<template>
    <Head title="Usuarios Globales" />

    <AdminLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Usuarios Globales</h1>
                <p class="mt-1 text-sm text-slate-500">Todos los usuarios del sistema a través de todos los talleres.</p>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow-sm ring-1 ring-slate-900/5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Usuario</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Email</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Rol</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Taller Asociado</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="user in users.data" :key="user.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-medium border border-slate-200">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            {{ user.name }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ user.email }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="user.is_super_admin" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">
                                            Super Admin
                                        </span>
                                        <span v-else class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                            Usuario Regular
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span v-if="user.tenant">
                                            {{ user.tenant.name }}
                                        </span>
                                        <span v-else class="text-slate-400 italic">
                                            Sin Taller (Global)
                                        </span>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button class="text-amber-600 hover:text-amber-900 mr-4 font-semibold" v-if="!user.is_super_admin">Reset Password</button>
                                        <button class="text-rose-600 hover:text-rose-900 font-semibold" v-if="!user.is_super_admin">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="users.data && users.data.length === 0">
                                    <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                        No hay usuarios registrados aún.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Pagination could go here -->
        </div>
    </AdminLayout>
</template>
