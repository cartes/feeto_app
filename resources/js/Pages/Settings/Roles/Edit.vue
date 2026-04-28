<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import SettingsSectionTabs from '@/Components/SettingsSectionTabs.vue';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const props = defineProps({
    role: {
        type: Object,
        required: true,
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
const isSystemRole = computed(() => props.role.is_system ?? false);

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

const isPermissionSelected = (permission) => form.permissions.includes(permission);

const togglePermission = (permission) => {
    const index = form.permissions.indexOf(permission);
    if (index === -1) {
        form.permissions.push(permission);
    } else {
        form.permissions.splice(index, 1);
    }
};

const toggleGroup = (groupPermissions) => {
    const allSelected = groupPermissions.every((p) => form.permissions.includes(p));
    if (allSelected) {
        form.permissions = form.permissions.filter((p) => !groupPermissions.includes(p));
    } else {
        groupPermissions.forEach((p) => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
};

const isGroupFullySelected = (groupPermissions) => groupPermissions.every((p) => form.permissions.includes(p));
const isGroupPartiallySelected = (groupPermissions) =>
    groupPermissions.some((p) => form.permissions.includes(p)) && !isGroupFullySelected(groupPermissions);

const submit = () => {
    form.put(route('taller.roles.update', { ...tenantRouteParams.value, role: props.role.id }));
};
</script>

<template>
    <TallerLayout>
        <Head :title="`Editar Rol: ${role.name}`" />

        <div class="mx-auto max-w-2xl space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Editar Rol: {{ role.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ isSystemRole
                        ? 'Personaliza los permisos de este rol base para tu tenant. El nombre del rol se mantiene fijo.'
                        : 'Modifica el nombre y los permisos asignados a este rol personalizado.' }}
                </p>
            </div>

            <SettingsSectionTabs
                :tenant-route-params="tenantRouteParams"
                current-section="roles"
                :can-access-roles="true"
                :current-user-count="currentUserCount"
                :plan-max-users="planMaxUsers"
                :branches-count="branchesCount"
            />

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Nombre del Rol -->
                <div class="rounded-[1.5rem] bg-white p-6 shadow-sm">
                    <label class="block text-sm font-bold text-slate-700" for="roleName">
                        Nombre del Rol
                    </label>
                    <input
                        id="roleName"
                        v-model="form.name"
                        type="text"
                        :disabled="isSystemRole"
                        class="mt-2 w-full rounded-[1rem] border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-slate-700 outline-none transition focus:border-[#F9A826] focus:ring-2 focus:ring-[#F9A826]/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
                        :class="{ 'border-red-300': form.errors.name }"
                    />
                    <p v-if="isSystemRole" class="mt-1.5 text-xs text-slate-500">
                        Este nombre es parte del catálogo base y no se puede cambiar.
                    </p>
                    <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Permisos por Módulo -->
                <div class="rounded-[1.5rem] bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-700">Permisos por Módulo</h2>
                    <p v-if="form.errors.permissions" class="mb-3 text-xs text-red-600">{{ form.errors.permissions }}</p>

                    <div class="space-y-4">
                        <div
                            v-for="(group, groupKey) in permissionGroups"
                            :key="groupKey"
                            class="rounded-[1rem] border border-gray-100 p-4"
                        >
                            <!-- Group Header -->
                            <div class="mb-3 flex items-center gap-3">
                                <input
                                    :id="`group-${groupKey}`"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-[#F9A826] focus:ring-[#F9A826]"
                                    :checked="isGroupFullySelected(group.permissions)"
                                    :indeterminate="isGroupPartiallySelected(group.permissions)"
                                    @change="toggleGroup(group.permissions)"
                                />
                                <label :for="`group-${groupKey}`" class="cursor-pointer text-sm font-bold text-slate-700">
                                    {{ group.label }}
                                </label>
                            </div>

                            <!-- Individual Permissions -->
                            <div class="ml-7 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 transition hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-[#F9A826] focus:ring-[#F9A826]"
                                        :checked="isPermissionSelected(permission)"
                                        @change="togglePermission(permission)"
                                    />
                                    <span class="text-xs font-medium text-slate-600">{{ permission }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('taller.roles.index', tenantRouteParams)"
                        class="rounded-[1.25rem] border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-[1.25rem] bg-[#F9A826] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#e8971f] disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </TallerLayout>
</template>
