<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const props = defineProps({
    users: Array,
    branches: Array,
    roles: Array,
    planMaxUsers: Number,
    currentUserCount: Number,
    canCreateBranch: Boolean,
    branchLimitInfo: String,
    tenant: Object,
});

const activeTab = ref('users');

// ── USUARIOS ──────────────────────────────────────────────
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

// ── SUCURSALES ────────────────────────────────────────────
const showBranchForm = ref(false);
const editingBranch = ref(null);

const branchForm = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    is_main: false,
});

const openNewBranch = () => {
    editingBranch.value = null;
    branchForm.reset();
    showBranchForm.value = true;
};

const openEditBranch = (branch) => {
    editingBranch.value = branch;
    branchForm.name = branch.name;
    branchForm.code = branch.code ?? '';
    branchForm.address = branch.address ?? '';
    branchForm.phone = branch.phone ?? '';
    branchForm.email = branch.email ?? '';
    branchForm.is_main = branch.is_main;
    showBranchForm.value = true;
};

const submitBranch = () => {
    if (editingBranch.value) {
        branchForm.put(route('branches.update', { ...tenantRouteParams.value, branch: editingBranch.value.id }), {
            onSuccess: () => { showBranchForm.value = false; branchForm.reset(); },
        });
    } else {
        branchForm.post(route('branches.store', tenantRouteParams.value), {
            onSuccess: () => { showBranchForm.value = false; branchForm.reset(); },
        });
    }
};

const deleteBranch = (branch) => {
    if (branch.is_main) return;
    if (!confirm(`¿Eliminar la sucursal "${branch.name}"?`)) return;
    router.delete(route('branches.destroy', { ...tenantRouteParams.value, branch: branch.id }), { preserveScroll: true });
};

const commercialForm = useForm({
    max_discount_without_approval: props.tenant?.max_discount_without_approval ?? 10,
});

const submitCommercialSettings = () => {
    commercialForm.patch(route('taller.settings.commercial.update', tenantRouteParams.value), {
        preserveScroll: true,
    });
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
    <Head title="Configuración del Taller" />
    <TallerLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 px-1">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-gray-900 uppercase">Configuración</h1>
                    <p class="text-sm text-gray-500 mt-1 font-medium">Administra usuarios, sucursales y ajustes del taller</p>
                </div>
                <div class="flex items-center gap-2 bg-white border border-gray-100 rounded-2xl px-4 py-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[#F9A826]"></span>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ tenant?.name }}</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 bg-white/60 border border-white rounded-2xl p-1.5 shadow-sm w-fit">
                <button
                    id="tab-users"
                    @click="activeTab = 'users'"
                    class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
                    :class="activeTab === 'users' ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                >
                    Usuarios ({{ currentUserCount }}/{{ planMaxUsers }})
                </button>
                <button
                    id="tab-branches"
                    @click="activeTab = 'branches'"
                    class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
                    :class="activeTab === 'branches' ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                >
                    Sucursales ({{ branches.length }})
                </button>
                <button
                    id="tab-commercial"
                    @click="activeTab = 'commercial'"
                    class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
                    :class="activeTab === 'commercial' ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                >
                    Comercial
                </button>
            </div>

            <!-- Link a gestión de roles -->
            <div class="flex items-center justify-between rounded-[1.5rem] bg-white px-5 py-4 shadow-sm">
                <div>
                    <p class="text-sm font-bold text-slate-700">Roles y Permisos</p>
                    <p class="mt-0.5 text-xs text-slate-400">Gestiona los roles de tu equipo y sus niveles de acceso.</p>
                </div>
                <a
                    :href="route('taller.roles.index', tenantRouteParams)"
                    class="inline-flex items-center gap-2 rounded-[1.25rem] bg-slate-100 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-200"
                >
                    Ver Roles
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- ── TAB USUARIOS ── -->
            <div v-if="activeTab === 'users'" class="space-y-5 animate-in fade-in duration-300">

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
                        class="flex items-center gap-2 px-5 py-3 bg-[#F9A826] text-white rounded-2xl font-bold text-sm shadow-md hover:bg-[#E59A22] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
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
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="userForm.errors.name" class="text-red-500 text-xs">{{ userForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email</label>
                            <input v-model="userForm.email" type="email" required placeholder="correo@taller.cl"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="userForm.errors.email" class="text-red-500 text-xs">{{ userForm.errors.email }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Contraseña</label>
                            <input v-model="userForm.password" type="password" required placeholder="Mínimo 8 caracteres"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="userForm.errors.password" class="text-red-500 text-xs">{{ userForm.errors.password }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Confirmar Contraseña</label>
                            <input v-model="userForm.password_confirmation" type="password" required placeholder="Repite la contraseña"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="userForm.errors.password_confirmation" class="text-red-500 text-xs">{{ userForm.errors.password_confirmation }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rol</label>
                            <select v-model="userForm.role"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]">
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <p v-if="userForm.errors.role" class="text-red-500 text-xs">{{ userForm.errors.role }}</p>
                        </div>
                        <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                            <button type="button" @click="showUserForm = false"
                                class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">Cancelar</button>
                            <button type="submit" :disabled="userForm.processing"
                                class="px-6 py-2.5 bg-[#F9A826] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#E59A22] transition-all disabled:opacity-50">
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
                                        <div class="w-9 h-9 rounded-full bg-[#F9A826]/10 flex items-center justify-center font-black text-[#F9A826] text-sm flex-shrink-0">
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

            <!-- ── TAB SUCURSALES ── -->
            <div v-if="activeTab === 'branches'" class="space-y-5 animate-in fade-in duration-300">

                <div v-if="branchLimitInfo" class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-2xl px-5 py-3">
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
                    <p class="text-xs font-semibold text-blue-600">{{ branchLimitInfo }}</p>
                </div>

                <!-- Botón agregar sucursal -->
                <div class="flex justify-end">
                    <button
                        id="btn-add-branch"
                        @click="openNewBranch"
                        :disabled="!canCreateBranch"
                        class="flex items-center gap-2 px-5 py-3 bg-[#F9A826] text-white rounded-2xl font-bold text-sm shadow-md hover:bg-[#E59A22] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Nueva Sucursal
                    </button>
                </div>

                <!-- Formulario sucursal -->
                <div v-if="showBranchForm" class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5 animate-in slide-in-from-top-2 duration-300">
                    <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">
                        {{ editingBranch ? 'Editar Sucursal' : 'Nueva Sucursal' }}
                    </h3>
                    <form @submit.prevent="submitBranch" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nombre *</label>
                            <input v-model="branchForm.name" type="text" required placeholder="Casa Matriz"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                            <p v-if="branchForm.errors.name" class="text-red-500 text-xs">{{ branchForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Código</label>
                            <input v-model="branchForm.code" type="text" placeholder="CM-01"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Dirección</label>
                            <input v-model="branchForm.address" type="text" placeholder="Av. Principal 1234"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Teléfono</label>
                            <input v-model="branchForm.phone" type="text" placeholder="+56 9 1234 5678"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email</label>
                            <input v-model="branchForm.email" type="email" placeholder="sucursal@taller.cl"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]" />
                        </div>
                        <div class="flex items-center gap-3 pt-5">
                            <input id="is_main" v-model="branchForm.is_main" type="checkbox"
                                class="w-4 h-4 rounded text-[#F9A826] focus:ring-[#F9A826]" />
                            <label for="is_main" class="text-sm font-semibold text-gray-600">Marcar como Sucursal Principal</label>
                        </div>
                        <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                            <button type="button" @click="showBranchForm = false"
                                class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">Cancelar</button>
                            <button type="submit" :disabled="branchForm.processing"
                                class="px-6 py-2.5 bg-[#F9A826] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#E59A22] transition-all disabled:opacity-50">
                                {{ branchForm.processing ? 'Guardando...' : (editingBranch ? 'Actualizar' : 'Crear Sucursal') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de sucursales -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="branch in branches" :key="branch.id"
                        class="bg-white/80 border border-gray-100 rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col gap-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 bg-[#F9A826]/10 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#F9A826]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <p class="font-black text-gray-800 text-sm leading-tight">{{ branch.name }}</p>
                                    <p v-if="branch.code" class="text-[10px] text-gray-400 font-mono">{{ branch.code }}</p>
                                </div>
                            </div>
                            <span v-if="branch.is_main" class="text-[9px] font-black uppercase tracking-wider bg-[#F9A826]/10 text-[#F9A826] px-2 py-1 rounded-full border border-[#F9A826]/20">Principal</span>
                        </div>
                        <div class="space-y-1 text-xs text-gray-500 font-medium">
                            <p v-if="branch.address" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ branch.address }}
                            </p>
                            <p v-if="branch.phone" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ branch.phone }}
                            </p>
                        </div>
                        <div class="flex gap-2 pt-1 border-t border-gray-50">
                            <button @click="openEditBranch(branch)"
                                class="flex-1 py-2 text-xs font-bold text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-xl transition-colors text-center">
                                Editar
                            </button>
                            <button v-if="!branch.is_main" @click="deleteBranch(branch)"
                                class="flex-1 py-2 text-xs font-bold text-red-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors text-center">
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <div v-if="branches.length === 0"
                        class="sm:col-span-2 lg:col-span-3 py-16 text-center text-sm text-gray-400 font-medium bg-white/60 rounded-3xl border border-dashed border-gray-200">
                        No hay sucursales registradas.
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'commercial'" class="space-y-5 animate-in fade-in duration-300">
                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                    <form class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-5" @submit.prevent="submitCommercialSettings">
                        <div>
                            <p class="text-sm font-black uppercase tracking-widest text-gray-500">Política de descuentos</p>
                            <h3 class="mt-2 text-2xl font-black text-gray-900">Aprobación comercial</h3>
                            <p class="mt-2 text-sm font-medium text-gray-500">
                                Define el porcentaje máximo que cualquier usuario puede aplicar sin aprobación superior.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Descuento máximo sin aprobación (%)</label>
                                <input
                                    v-model="commercialForm.max_discount_without_approval"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#F9A826]"
                                />
                                <p v-if="commercialForm.errors.max_discount_without_approval" class="text-red-500 text-xs">
                                    {{ commercialForm.errors.max_discount_without_approval }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                            <p class="text-xs font-black uppercase tracking-widest text-amber-600">Regla activa</p>
                            <p class="mt-2 text-sm font-medium text-amber-900">
                                Descuentos superiores a {{ commercialForm.max_discount_without_approval || 0 }}% requerirán rol <strong>Jefe</strong> o <strong>Supervisor</strong>.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="commercialForm.processing"
                                class="px-6 py-2.5 bg-[#F9A826] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#E59A22] transition-all disabled:opacity-50"
                            >
                                {{ commercialForm.processing ? 'Guardando...' : 'Guardar Política Comercial' }}
                            </button>
                        </div>
                    </form>

                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Roles comerciales</p>
                        <div class="mt-5 space-y-4">
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="text-sm font-black text-gray-900">Supervisor</p>
                                <p class="mt-1 text-sm text-gray-500">Puede revisar descuentos altos, reportes y operación comercial del taller.</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-4">
                                <p class="text-sm font-black text-gray-900">Jefe</p>
                                <p class="mt-1 text-sm text-gray-500">Puede autorizar descuentos superiores al umbral y supervisar cartera atrasada.</p>
                            </div>
                            <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-4">
                                <p class="text-xs font-black uppercase tracking-widest text-gray-400">Plan actual</p>
                                <p class="mt-2 text-lg font-black text-gray-900">{{ tenant?.plan_label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </TallerLayout>
</template>
