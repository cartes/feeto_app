<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import SettingsSectionTabs from '@/Components/SettingsSectionTabs.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import PlanUpgradeBanner from '@/Components/PlanUpgradeBanner.vue';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    canManageRoles: {
        type: Boolean,
        default: false,
    },
    permissionGroups: {
        type: Object,
        default: () => ({}),
    },
    planMaxUsers: {
        type: Number,
        default: null,
    },
    currentUserCount: {
        type: Number,
        default: null,
    },
    branchesCount: {
        type: Number,
        default: null,
    },
});

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

const expandedRoles = ref({});

const toggleExpand = (roleId) => {
    expandedRoles.value[roleId] = !expandedRoles.value[roleId];
};

const permissionLabel = (permissionName) => {
    for (const group of Object.values(props.permissionGroups)) {
        if (group.permissions.includes(permissionName)) {
            return permissionName;
        }
    }
    return permissionName;
};

const groupLabel = (permissionName) => {
    for (const group of Object.values(props.permissionGroups)) {
        if (group.permissions.includes(permissionName)) {
            return group.label;
        }
    }
    return 'Otros';
};

const deleteRole = (roleId, roleName) => {
    if (!confirm(`¿Eliminar el rol "${roleName}"? Esta acción no se puede deshacer.`)) return;
    router.delete(route('taller.roles.destroy', { ...tenantRouteParams.value, role: roleId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <TallerLayout>
        <Head title="Gestión de Roles" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Roles y Permisos</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Gestiona los roles disponibles y los permisos asignados a cada uno.
                    </p>
                </div>
                <Link
                    v-if="canManageRoles"
                    :href="route('taller.roles.create', tenantRouteParams)"
                    class="inline-flex items-center gap-2 rounded-[1.25rem] bg-[#F9A826] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#e8971f]"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Rol
                </Link>
            </div>

            <SettingsSectionTabs
                :tenant-route-params="tenantRouteParams"
                current-section="roles"
                :can-access-roles="true"
                :current-user-count="currentUserCount"
                :plan-max-users="planMaxUsers"
                :branches-count="branchesCount"
            />

            <!-- Plan Upgrade Banner -->
            <PlanUpgradeBanner
                v-if="!canManageRoles"
                title="Roles personalizados — Plan Empresa"
                message="Con el Plan Empresa puedes crear roles con permisos completamente personalizados para tu equipo. En este plan puedes asignar los roles fijos del sistema a tus usuarios."
            />

            <!-- Roles Table -->
            <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-bold uppercase tracking-widest text-slate-400">
                            <th class="px-6 py-4">Rol</th>
                            <th class="px-6 py-4">Permisos</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="role in roles" :key="role.id" class="transition hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                                        :class="role.is_system ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700'"
                                    >
                                        {{ role.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ role.name }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ role.is_system ? 'Rol del sistema' : 'Rol personalizado' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="permission in role.permissions.slice(0, 4)"
                                        :key="permission"
                                        class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                    >
                                        {{ permission }}
                                    </span>
                                    <button
                                        v-if="role.permissions.length > 4"
                                        class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 hover:bg-amber-100"
                                        @click="toggleExpand(role.id)"
                                    >
                                        {{ expandedRoles[role.id] ? 'Ver menos' : `+${role.permissions.length - 4} más` }}
                                    </button>
                                </div>
                                <div v-if="expandedRoles[role.id]" class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="permission in role.permissions.slice(4)"
                                        :key="permission"
                                        class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                    >
                                        {{ permission }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        v-if="canManageRoles"
                                        :href="route('taller.roles.edit', { ...tenantRouteParams, role: role.id })"
                                        class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-200"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        v-if="canManageRoles && !role.is_system"
                                        class="rounded-xl bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100"
                                        @click="deleteRole(role.id, role.name)"
                                    >
                                        Eliminar
                                    </button>
                                    <span
                                        v-if="role.is_system"
                                        class="text-xs text-slate-400"
                                    >
                                        Base del sistema
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="roles.length === 0" class="py-12 text-center text-slate-400">
                    <p class="font-medium">No hay roles registrados.</p>
                </div>
            </div>
        </div>
    </TallerLayout>
</template>
