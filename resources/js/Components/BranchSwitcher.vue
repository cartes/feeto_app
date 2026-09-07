<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';

const page = usePage();
const { tenantRouteParams } = useTenantRouting();

const branchContext = computed(() => page.props.branchContext ?? null);
const currentBranch = computed(() => branchContext.value?.current ?? null);
const isAll = computed(() => branchContext.value?.is_all ?? true);
const isFixed = computed(() => branchContext.value?.is_fixed ?? false);
const availableBranches = computed(() => branchContext.value?.available ?? []);

const isOpen = ref(false);
const isSwitching = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    if (isFixed.value) return;
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdown);
});

const selectBranch = (branchId) => {
    if (isSwitching.value) return;

    isSwitching.value = true;
    isOpen.value = false;

    router.post(
        route('branches.switch', tenantRouteParams.value),
        { branch_id: branchId },
        {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => {
                isSwitching.value = false;
            },
        }
    );
};
</script>

<template>
    <div v-if="branchContext && availableBranches.length > 0" class="relative" ref="dropdownRef">
        <!-- Usuario con sucursal fija (Solo badge informativo) -->
        <div
            v-if="isFixed"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-white/90 border border-gray-200/80 shadow-sm text-xs font-bold text-gray-700"
            :title="currentBranch?.address ? `Dirección: ${currentBranch.address}` : 'Tu sucursal asignada'"
        >
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="truncate max-w-[140px] sm:max-w-[180px]">{{ currentBranch?.name || 'Mi Sucursal' }}</span>
        </div>

        <!-- Super Admin del Tenant (Selector de sucursal interactivo) -->
        <div v-else class="relative">
            <button
                type="button"
                id="branch-switcher-button"
                @click.stop="toggleDropdown"
                :disabled="isSwitching"
                class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white text-gray-800 border border-gray-200/80 shadow-sm hover:border-[#FF7A00]/50 hover:shadow-md transition-all text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#FF7A00]/30 disabled:opacity-50"
            >
                <!-- Indicador de estado -->
                <span v-if="isSwitching" class="animate-spin h-3.5 w-3.5 border-2 border-[#FF7A00] border-t-transparent rounded-full shrink-0"></span>
                <span v-else-if="isAll" class="h-2 w-2 rounded-full bg-blue-500 shrink-0"></span>
                <span v-else class="h-2 w-2 rounded-full bg-[#FF7A00] shrink-0"></span>

                <!-- Icono de Sede / Global -->
                <svg v-if="isAll" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>

                <!-- Texto de la selección -->
                <span class="truncate max-w-[130px] sm:max-w-[180px]">
                    {{ isAll ? 'Todas las sucursales' : currentBranch?.name }}
                </span>

                <!-- Flecha desplegable -->
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200 shrink-0"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Menú desplegable -->
            <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150"
                >
                    <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-gray-400">
                        Contexto de Sucursal
                    </div>

                    <!-- Opción: Todas las sucursales (Consolidado) -->
                    <button
                        type="button"
                        @click="selectBranch('all')"
                        class="w-full flex items-center justify-between px-3.5 py-2.5 text-left text-xs font-bold transition-colors hover:bg-gray-50"
                        :class="isAll ? 'text-[#FF7A00] bg-orange-50/50' : 'text-gray-700'"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="p-1 rounded-lg bg-blue-50 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="leading-tight">Todas las sucursales</p>
                                <p class="text-[10px] font-medium text-gray-400">Visión consolidada del taller</p>
                            </div>
                        </div>
                        <svg v-if="isAll" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="my-1.5 border-t border-gray-100"></div>

                    <!-- Lista de Sucursales -->
                    <div class="max-h-60 overflow-y-auto">
                        <button
                            v-for="branch in availableBranches"
                            :key="branch.id"
                            type="button"
                            @click="selectBranch(branch.id)"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 text-left text-xs font-bold transition-colors hover:bg-gray-50"
                            :class="!isAll && currentBranch?.id === branch.id ? 'text-[#FF7A00] bg-orange-50/50' : 'text-gray-700'"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="p-1 rounded-lg bg-gray-100 text-gray-600 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <p class="truncate leading-tight">{{ branch.name }}</p>
                                        <span v-if="branch.is_main" class="text-[9px] px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold shrink-0">
                                            Matriz
                                        </span>
                                    </div>
                                    <p v-if="branch.code" class="text-[10px] font-mono text-gray-400">{{ branch.code }}</p>
                                </div>
                            </div>
                            <svg v-if="!isAll && currentBranch?.id === branch.id" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#FF7A00] shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>
