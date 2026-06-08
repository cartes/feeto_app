<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
});

const selectedUser = ref(null);
const showPasswordModal = ref(false);

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openPasswordModal = (user) => {
    selectedUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    selectedUser.value = null;
    passwordForm.reset();
    passwordForm.clearErrors();
};

const submitPassword = () => {
    passwordForm.put(route('admin.users.change-password', selectedUser.value.id), {
        onSuccess: () => closePasswordModal(),
    });
};
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
                                        <button
                                            class="text-amber-600 hover:text-amber-900 mr-4 font-semibold"
                                            @click="openPasswordModal(user)"
                                        >
                                            Cambiar contraseña
                                        </button>
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
        </div>

        <!-- Modal Cambiar Contraseña -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50" @click="closePasswordModal"></div>

                <!-- Modal -->
                <div class="relative z-10 w-full max-w-md mx-4 bg-white rounded-xl shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Cambiar contraseña</h2>
                                <p class="text-sm text-slate-500 mt-0.5">{{ selectedUser?.name }}</p>
                            </div>
                            <button
                                type="button"
                                @click="closePasswordModal"
                                class="text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitPassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nueva contraseña</label>
                                <PasswordInput
                                    v-model="passwordForm.password"
                                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500 focus:outline-none text-slate-900"
                                    placeholder="Mínimo 8 caracteres"
                                    autocomplete="new-password"
                                />
                                <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-rose-600">{{ passwordForm.errors.password }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirmar contraseña</label>
                                <PasswordInput
                                    v-model="passwordForm.password_confirmation"
                                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500 focus:outline-none text-slate-900"
                                    placeholder="Repetir contraseña"
                                    autocomplete="new-password"
                                />
                                <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-xs text-rose-600">{{ passwordForm.errors.password_confirmation }}</p>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="closePasswordModal"
                                    class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="flex-1 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition-colors disabled:opacity-50"
                                >
                                    {{ passwordForm.processing ? 'Guardando...' : 'Guardar contraseña' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
