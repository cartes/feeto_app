<script setup>
import { computed } from 'vue';
import { useTenantRouting } from '@/composables/useTenantRouting';
import TallerLayout from '@/Layouts/TallerLayout.vue';
import SettingsSectionTabs from '@/Components/SettingsSectionTabs.vue';

const props = defineProps({
    currentSection: {
        type: String,
        required: true,
    },
    currentUserCount: {
        type: Number,
        default: null,
    },
    planMaxUsers: {
        type: Number,
        default: null,
    },
    branchesCount: {
        type: Number,
        default: null,
    },
});

const { page, tenantRouteParams } = useTenantRouting();
const user = computed(() => page.props.auth.user ?? null);
const permissions = computed(() => user.value?.permissions ?? []);
const userRoles = computed(() => user.value?.roles ?? []);
const tenantContext = computed(() => page.props.tenantContext ?? null);

const hasPermission = (permission) => permissions.value.includes(permission);

const canAccessRoles = computed(() => (
    (userRoles.value.includes('Admin') || hasPermission('users.manage'))
    && (tenantContext.value?.features ?? []).includes('custom_roles')
));

const canAccessSeo = computed(() => (tenantContext.value?.features ?? []).includes('seo_manager'));
const canAccessBranding = computed(() => true);

const tenant = computed(() => page.props.tenant ?? null);

// Resolve counters from page props if not passed directly
const computedCurrentUserCount = computed(() => 
    props.currentUserCount !== null 
        ? props.currentUserCount 
        : (page.props.currentUserCount ?? page.props.currentCount ?? (page.props.users ? page.props.users.length : null))
);

const computedPlanMaxUsers = computed(() => 
    props.planMaxUsers !== null 
        ? props.planMaxUsers 
        : (page.props.planMaxUsers ?? null)
);

const computedBranchesCount = computed(() => 
    props.branchesCount !== null 
        ? props.branchesCount 
        : (page.props.branchesCount ?? (page.props.branches ? page.props.branches.length : null))
);
</script>

<template>
    <TallerLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 px-1">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-gray-900 uppercase">Configuración</h1>
                    <p class="text-sm text-gray-500 mt-1 font-medium">Administra usuarios, sucursales y ajustes del taller</p>
                </div>
                <div class="flex items-center gap-2 bg-white border border-gray-100 rounded-2xl px-4 py-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[#FF7A00]"></span>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ tenant?.name }}</span>
                </div>
            </div>

            <SettingsSectionTabs
                :tenant-route-params="tenantRouteParams"
                :current-section="currentSection"
                :can-access-roles="canAccessRoles"
                :can-access-seo="canAccessSeo"
                :can-access-branding="canAccessBranding"
                :current-user-count="computedCurrentUserCount"
                :plan-max-users="computedPlanMaxUsers"
                :branches-count="computedBranchesCount"
            />

            <!-- Page Content -->
            <slot />
        </div>
    </TallerLayout>
</template>
