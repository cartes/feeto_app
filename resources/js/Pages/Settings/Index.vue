<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useTenantRouting } from '@/composables/useTenantRouting';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import UsersTab from './Partials/UsersTab.vue';
import BranchesTab from './Partials/BranchesTab.vue';
import CommercialTab from './Partials/CommercialTab.vue';
import SeoTab from './Partials/SeoTab.vue';
import SchedulingTab from './Partials/SchedulingTab.vue';
import BrandingTab from './Partials/BrandingTab.vue';

const { page } = useTenantRouting();
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
</script>

<template>
    <Head title="Configuración del Taller" />
    <SettingsLayout
        :current-section="activeTab"
        :current-user-count="currentUserCount"
        :plan-max-users="planMaxUsers"
        :branches-count="branches.length"
    >
        <UsersTab
            v-if="activeTab === 'users'"
            :users="users"
            :roles="roles"
            :plan-max-users="planMaxUsers"
            :current-user-count="currentUserCount"
        />

        <BranchesTab
            v-if="activeTab === 'branches'"
            :branches="branches"
            :can-create-branch="canCreateBranch"
            :branch-limit-info="branchLimitInfo"
        />

        <CommercialTab v-if="activeTab === 'commercial'" :tenant="tenant" />

        <SeoTab v-if="activeTab === 'seo' && canAccessSeo" :tenant="tenant" />

        <SchedulingTab v-if="activeTab === 'scheduling'" :tenant="tenant" />

        <BrandingTab
            v-if="activeTab === 'branding' && canAccessBranding"
            :tenant="tenant"
            :branding-routes="brandingRoutes"
        />
    </SettingsLayout>
</template>
