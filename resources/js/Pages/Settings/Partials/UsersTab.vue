<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';
import PasswordInput from '@/Components/PasswordInput.vue';

const { tenantRouteParams } = useTenantRouting();

const props = defineProps({
    users: Array,
    roles: Array,
    planMaxUsers: Number,
    currentUserCount: Number,
});

const showUserForm = ref(false);
const userForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'Recepcionista',
});

const hasReachedUserLimit = computed(() => props.currentUserCount >= props.planMaxUsers);

const submitUser = () => {
    userForm.post(route('tenant.users.store', tenantRouteParams.value), {
        onSuccess: () => {
            userForm.reset();
            showUserForm.value = false;
        },
    });
};

const deleteUser = (userId) => {
    if (!confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')) return;
    router.delete(route('tenant.users.destroy', { ...tenantRouteParams.value, user: userId }), { preserveScroll: true });
};

const roleColor = (role) => {
    const map = {
        Admin: 'bg-orange-100 text-orange-700',
        Recepcionista: 'bg-blue-100 text-blue-700',
        Supervisor: 'bg-purple-100 text-purple-700',
        Jefe: 'bg-rose-100 text-rose-700',
        Mecanico: 'bg-emerald-100 text-emerald-700',
    };
    return map[role] ?? 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <div class="space-y-5 animate-in fade-in duration-300">

        <!-- Límite de plan -->
        <div v-if="hasReachedUserLimit" class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
            <p class="text-sm font-semibold text-amber-700">Has alcanzado el límite de <strong>{{ planMaxUsers }} usuarios</strong> de tu plan. Actualiza para agregar más.</p>
        </div>

        <!-- Botón agregar usuario -->
        <div class="flex justify-end">
            <button
                id="btn-add-user"
                @click="showUserForm = !showUserForm"
                :disabled="hasReachedUserLimit"
                class="flex items-center gap-2 px-5 py-3 bg-[#FF7A00] text-white rounded-2xl font-bold text-sm shadow-md hover:bg-[#CC6200] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nuevo Usuario
            </button>
        </div>

        <!-- Formulario nuevo usuario -->
        <div v-if="showUserForm" class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5 animate-in slide-in-from-top-2 duration-300">
            <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">Agregar Usuario al Taller</h3>
            <form @submit.prevent="submitUser" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nombre</label>
                    <input v-model="userForm.name" type="text" required placeholder="Juan Pérez"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                    <p v-if="userForm.errors.name" class="text-red-500 text-xs">{{ userForm.errors.name }}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email</label>
                    <input v-model="userForm.email" type="email" required placeholder="correo@taller.cl"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                    <p v-if="userForm.errors.email" class="text-red-500 text-xs">{{ userForm.errors.email }}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Contraseña</label>
                    <PasswordInput v-model="userForm.password" required placeholder="Mínimo 8 caracteres"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                    <p v-if="userForm.errors.password" class="text-red-500 text-xs">{{ userForm.errors.password }}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Confirmar Contraseña</label>
                    <PasswordInput v-model="userForm.password_confirmation" required placeholder="Repite la contraseña"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                    <p v-if="userForm.errors.password_confirmation" class="text-red-500 text-xs">{{ userForm.errors.password_confirmation }}</p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rol</label>
                    <select v-model="userForm.role"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]">
                        <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                    </select>
                    <p v-if="userForm.errors.role" class="text-red-500 text-xs">{{ userForm.errors.role }}</p>
                </div>
                <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                    <button type="button" @click="showUserForm = false"
                        class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" :disabled="userForm.processing"
                        class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
                        {{ userForm.processing ? 'Guardando...' : 'Crear Usuario' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de usuarios -->
        <div class="bg-white/80 border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
            <table class="w-full">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Usuario</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400 hidden sm:table-cell">Email</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Rol</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#FF7A00]/10 flex items-center justify-center font-black text-[#FF7A00] text-sm flex-shrink-0">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-semibold text-sm text-gray-800">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 hidden sm:table-cell">{{ user.email }}</td>
                        <td class="px-6 py-4">
                            <span v-for="role in user.roles" :key="role"
                                class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wide mr-1"
                                :class="roleColor(role)">{{ role }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="deleteUser(user.id)"
                                class="text-xs font-bold text-red-400 hover:text-red-600 transition-colors px-3 py-1.5 rounded-lg hover:bg-red-50">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400 font-medium">No hay usuarios registrados.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
