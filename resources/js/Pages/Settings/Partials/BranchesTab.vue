<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

defineProps({
    branches: Array,
    canCreateBranch: Boolean,
    branchLimitInfo: String,
});

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
</script>

<template>
    <div class="space-y-5 animate-in fade-in duration-300">

        <div v-if="branchLimitInfo" class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-2xl px-5 py-3">
            <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
            <p class="text-xs font-semibold text-blue-600">{{ branchLimitInfo }}</p>
        </div>

        <!-- Botón agregar sucursal -->
        <div class="flex justify-end">
            <button
                id="btn-add-branch"
                data-support="branch-add"
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
</template>
