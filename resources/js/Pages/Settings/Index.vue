<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});
const user = computed(() => page.props.auth.user ?? null);
const permissions = computed(() => user.value?.permissions ?? []);
const userRoles = computed(() => user.value?.roles ?? []);
const tenantContext = computed(() => page.props.tenantContext ?? null);
const currentTab = computed(() => new URL(page.url, window.location.origin).searchParams.get('tab'));
const props = defineProps({
    users: Array,
    branches: Array,
    roles: Array,
    planMaxUsers: Number,
    currentUserCount: Number,
    canCreateBranch: Boolean,
    branchLimitInfo: String,
    tenant: Object,
    brandingRoutes: Object,
    canAccessSeo: Boolean,
    canAccessBranding: Boolean,
});

const activeTab = computed(() => currentTab.value ?? 'users');
const hasPermission = (permission) => permissions.value.includes(permission);
const canAccessRoles = computed(() => (
    (userRoles.value.includes('Admin') || hasPermission('users.manage'))
    && (tenantContext.value?.features ?? []).includes('custom_roles')
));

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

// ── SEO ───────────────────────────────────────────────────
const seoForm = useForm({
    seo_description: props.tenant?.seo_description ?? '',
    seo_address: props.tenant?.seo_address ?? '',
    whatsapp_number: props.tenant?.whatsapp_number ?? '',
});

const submitSeoSettings = () => {
    seoForm.patch(route('taller.settings.seo.update', tenantRouteParams.value), {
        preserveScroll: true,
    });
};

// ── IA DESCRIPCIÓN ────────────────────────────────────────
const showAiModal = ref(false);
const aiConcepts = ref(['', '', '']);
const aiGenerating = ref(false);
const aiError = ref('');

const generateAiDescription = async () => {
    const filled = aiConcepts.value.filter(c => c.trim().length > 0);
    if (filled.length === 0) {
        aiError.value = 'Ingresa al menos un concepto.';
        return;
    }
    aiError.value = '';
    aiGenerating.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const res = await fetch(route('taller.settings.seo.generate', tenantRouteParams.value), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                workshop_name: props.tenant?.name ?? '',
                concepts: filled,
            }),
        });
        const data = await res.json();
        if (data.error) {
            aiError.value = data.error;
        } else {
            seoForm.seo_description = data.description ?? '';
            showAiModal.value = false;
            aiConcepts.value = ['', '', ''];
        }
    } catch {
        aiError.value = 'Error al conectar con la IA.';
    } finally {
        aiGenerating.value = false;
    }
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

// ── BRANDING ─────────────────────────────────────────────
const colorForm = useForm({
    primary_color: props.tenant?.primary_color ?? '#FF7A00',
});

const logoFile = ref(null);
const logoPreview = ref(props.tenant?.logo_url ?? null);
const logoForm = useForm({ logo: null });

const onLogoSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    logoFile.value = file;
    logoPreview.value = URL.createObjectURL(file);
    logoForm.logo = file;
};

const submitColor = () => {
    colorForm.patch(props.brandingRoutes.color, { preserveScroll: true });
};

const submitLogo = () => {
    logoForm.post(props.brandingRoutes.logo, {
        preserveScroll: true,
        onSuccess: () => { logoFile.value = null; },
    });
};

const deleteLogo = () => {
    if (!confirm('¿Eliminar el logo del taller?')) return;
    router.delete(props.brandingRoutes.deleteLogo, { preserveScroll: true, onSuccess: () => { logoPreview.value = null; } });
};

// Watch for changes in props.tenant to update color form and logo preview reactively
watch(() => props.tenant, (newTenant) => {
    if (newTenant) {
        colorForm.primary_color = newTenant.primary_color ?? '#FF7A00';
        if (!logoFile.value) {
            logoPreview.value = newTenant.logo_url ?? null;
        }
    }
}, { deep: true });
</script>

<template>
    <Head title="Configuración del Taller" />
    <SettingsLayout
        :current-section="activeTab"
        :current-user-count="currentUserCount"
        :plan-max-users="planMaxUsers"
        :branches-count="branches.length"
    >

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
                            <input v-model="userForm.password" type="password" required placeholder="Mínimo 8 caracteres"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="userForm.errors.password" class="text-red-500 text-xs">{{ userForm.errors.password }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Confirmar Contraseña</label>
                            <input v-model="userForm.password_confirmation" type="password" required placeholder="Repite la contraseña"
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
                        class="flex items-center gap-2 px-5 py-3 bg-[#FF7A00] text-white rounded-2xl font-bold text-sm shadow-md hover:bg-[#CC6200] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
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
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                            <p v-if="branchForm.errors.name" class="text-red-500 text-xs">{{ branchForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Código</label>
                            <input v-model="branchForm.code" type="text" placeholder="CM-01"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Dirección</label>
                            <input v-model="branchForm.address" type="text" placeholder="Av. Principal 1234"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Teléfono</label>
                            <input v-model="branchForm.phone" type="text" placeholder="+56 9 1234 5678"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email</label>
                            <input v-model="branchForm.email" type="email" placeholder="sucursal@taller.cl"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]" />
                        </div>
                        <div class="flex items-center gap-3 pt-5">
                            <input id="is_main" v-model="branchForm.is_main" type="checkbox"
                                class="w-4 h-4 rounded text-[#FF7A00] focus:ring-[#FF7A00]" />
                            <label for="is_main" class="text-sm font-semibold text-gray-600">Marcar como Sucursal Principal</label>
                        </div>
                        <div class="sm:col-span-2 flex gap-3 justify-end pt-2">
                            <button type="button" @click="showBranchForm = false"
                                class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">Cancelar</button>
                            <button type="submit" :disabled="branchForm.processing"
                                class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
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
                                <div class="w-9 h-9 bg-[#FF7A00]/10 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <p class="font-black text-gray-800 text-sm leading-tight">{{ branch.name }}</p>
                                    <p v-if="branch.code" class="text-[10px] text-gray-400 font-mono">{{ branch.code }}</p>
                                </div>
                            </div>
                            <span v-if="branch.is_main" class="text-[9px] font-black uppercase tracking-wider bg-[#FF7A00]/10 text-[#FF7A00] px-2 py-1 rounded-full border border-[#FF7A00]/20">Principal</span>
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
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
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
                                class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50"
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

        <!-- ── TAB SEO ── -->
        <div v-if="activeTab === 'seo' && canAccessSeo" class="space-y-6 animate-in fade-in duration-300">

            <!-- AI Modal -->
            <transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-5 animate-in zoom-in-95 duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-violet-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-black uppercase tracking-widest text-gray-700">Generar con IA</p>
                            </div>
                            <button @click="showAiModal = false; aiError = ''" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">Ingresa hasta 3 conceptos que describan tu taller y la IA generará una descripción SEO optimizada.</p>
                        <div class="space-y-3">
                            <div v-for="(_, i) in aiConcepts" :key="i" class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Concepto {{ i + 1 }}{{ i === 0 ? ' *' : '' }}</label>
                                <input
                                    v-model="aiConcepts[i]"
                                    type="text"
                                    :placeholder="['ej: servicio express, garantía 3 meses, diagnóstico gratuito'][i] ?? ''"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-400"
                                />
                            </div>
                        </div>
                        <p v-if="aiError" class="text-red-500 text-xs font-semibold">{{ aiError }}</p>
                        <div class="flex gap-3 justify-end pt-1">
                            <button type="button" @click="showAiModal = false; aiError = ''"
                                class="px-5 py-2.5 bg-gray-100 text-gray-500 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button @click="generateAiDescription" :disabled="aiGenerating"
                                class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold text-sm shadow-sm hover:bg-violet-700 transition-all disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="aiGenerating" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                {{ aiGenerating ? 'Generando...' : 'Generar descripción' }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                <form @submit.prevent="submitSeoSettings" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
                    <div>
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Perfil público</p>
                        <h3 class="mt-2 text-2xl font-black text-gray-900">SEO y WhatsApp</h3>
                        <p class="mt-2 text-sm font-medium text-gray-500">
                            Esta información aparece en tu página pública y mejora tu posicionamiento en Google.
                        </p>
                    </div>

                    <!-- Descripción -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Descripción del taller</label>
                            <button type="button" @click="showAiModal = true"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 text-violet-600 text-[10px] font-black uppercase tracking-widest hover:bg-violet-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Generar con IA
                            </button>
                        </div>
                        <textarea
                            v-model="seoForm.seo_description"
                            rows="3"
                            placeholder="Taller especializado en mantenciones, mecánica general y diagnóstico computarizado. Servicio rápido con garantía."
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00] resize-none"
                        ></textarea>
                        <p class="text-[10px] text-gray-400 font-medium">{{ seoForm.seo_description?.length ?? 0 }}/500 caracteres. Ideal: 100–160 para Google.</p>
                        <p v-if="seoForm.errors.seo_description" class="text-red-500 text-xs">{{ seoForm.errors.seo_description }}</p>
                    </div>

                    <!-- Dirección -->
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Dirección del taller</label>
                        <input
                            v-model="seoForm.seo_address"
                            type="text"
                            placeholder="Av. Apoquindo 4501, Las Condes, Santiago"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                        />
                        <p v-if="seoForm.errors.seo_address" class="text-red-500 text-xs">{{ seoForm.errors.seo_address }}</p>
                    </div>

                    <!-- WhatsApp -->
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Número de WhatsApp</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <input
                                v-model="seoForm.whatsapp_number"
                                type="text"
                                placeholder="+56 9 1234 5678"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                            />
                        </div>
                        <p class="text-[10px] text-gray-400 font-medium">Formato internacional: +56 9 XXXX XXXX. Aparecerá como botón flotante en tu página pública.</p>
                        <p v-if="seoForm.errors.whatsapp_number" class="text-red-500 text-xs">{{ seoForm.errors.whatsapp_number }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="seoForm.processing"
                            class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
                            {{ seoForm.processing ? 'Guardando...' : 'Guardar Perfil SEO' }}
                        </button>
                    </div>
                </form>

                <!-- Panel derecho: preview -->
                <div class="space-y-4">
                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
                        <p class="text-sm font-black uppercase tracking-widest text-gray-500">Vista previa Google</p>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 space-y-1">
                            <p class="text-[#1a0dab] text-sm font-medium leading-tight truncate">{{ tenant?.name ?? 'Tu Taller' }} | Agendar cita</p>
                            <p class="text-[#006621] text-[11px]">feeto.cl/taller/{{ tenant?.slug ?? 'tu-taller' }}</p>
                            <p class="text-gray-600 text-[11px] leading-relaxed">
                                {{ seoForm.seo_description?.slice(0, 160) || 'Agrega una descripción para tu taller y aparecerá aquí en los resultados de Google.' }}
                            </p>
                        </div>
                        <div v-if="seoForm.seo_address" class="flex items-start gap-2 text-xs text-gray-600">
                            <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="font-medium">{{ seoForm.seo_address }}</span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-[#25D366]/20 bg-[#25D366]/5 p-5 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <p class="text-sm font-black text-[#128C7E]">Botón flotante de WhatsApp</p>
                        </div>
                        <p class="text-xs text-gray-600 font-medium">
                            {{ seoForm.whatsapp_number ? `Al configurar el número, un botón verde flotante aparecerá en tu página pública para que los clientes te contacten directamente.` : 'Ingresa tu número para activar el botón flotante de WhatsApp en tu página pública.' }}
                        </p>
                        <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-widest">Cada clic genera una notificación en tu CRM.</p>
                    </div>
                </div>
            </div>
        </div>

            <!-- ── TAB APARIENCIA ── -->
            <div v-if="activeTab === 'branding' && canAccessBranding" class="space-y-6 animate-in fade-in duration-300">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Color primario -->
                    <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">Color del Taller</h3>
                            <p class="text-xs text-gray-400 mt-1 font-medium">Se usará en tu página pública para botones y acentos de color.</p>
                        </div>
                        <form @submit.prevent="submitColor" class="space-y-5">
                            <div class="space-y-3">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Color principal</label>
                                <div class="flex items-center gap-4">
                                    <input
                                        v-model="colorForm.primary_color"
                                        type="color"
                                        class="h-14 w-14 rounded-2xl border border-gray-200 cursor-pointer p-1 bg-gray-50"
                                    />
                                    <div>
                                        <p class="text-lg font-black tracking-tight text-gray-900" :style="{ color: colorForm.primary_color }">{{ colorForm.primary_color.toUpperCase() }}</p>
                                        <p class="text-xs text-gray-400 font-medium mt-0.5">Haz clic en el cuadro para elegir</p>
                                    </div>
                                </div>
                                <p v-if="colorForm.errors.primary_color" class="text-red-500 text-xs">{{ colorForm.errors.primary_color }}</p>
                            </div>

                            <!-- Preview del color -->
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vista previa</p>
                                <button type="button" class="inline-flex items-center gap-2 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-sm transition-all" :style="{ backgroundColor: colorForm.primary_color }">
                                    Agendar mi Cita Ahora
                                </button>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" :disabled="colorForm.processing"
                                    class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
                                    {{ colorForm.processing ? 'Guardando...' : 'Guardar Color' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Logo del taller -->
                    <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">Logo del Taller</h3>
                            <p class="text-xs text-gray-400 mt-1 font-medium">Se mostrará en tu página pública con el nombre del taller como texto alternativo.</p>
                        </div>

                        <!-- Preview del logo actual -->
                        <div class="flex items-center gap-4">
                            <div class="h-20 w-20 rounded-2xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="logoPreview" :src="logoPreview" :alt="tenant?.name" class="h-full w-full object-contain p-1" />
                                <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-gray-900">{{ logoPreview ? tenant?.name : 'Sin logo' }}</p>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">PNG, JPG o WebP. Máx. 2 MB.</p>
                                <button v-if="logoPreview && !logoFile" @click="deleteLogo" type="button"
                                    class="mt-2 text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                    Eliminar logo
                                </button>
                            </div>
                        </div>

                        <form @submit.prevent="submitLogo" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Subir nuevo logo</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onLogoSelected"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#FF7A00]/10 file:text-[#FF7A00] hover:file:bg-[#FF7A00]/20 cursor-pointer"
                                />
                                <p v-if="logoForm.errors.logo" class="text-red-500 text-xs">{{ logoForm.errors.logo }}</p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" :disabled="!logoFile || logoForm.processing"
                                    class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ logoForm.processing ? 'Subiendo...' : 'Subir Logo' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>    </SettingsLayout>
</template>
