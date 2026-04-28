<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tenantRouteParams: {
        type: Object,
        required: true,
    },
    currentSection: {
        type: String,
        required: true,
    },
    canAccessRoles: {
        type: Boolean,
        default: false,
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

const isActive = (section) => props.currentSection === section;
</script>

<template>
    <div class="flex flex-wrap gap-2 bg-white/60 border border-white rounded-2xl p-1.5 shadow-sm w-fit">
        <Link
            :href="route('taller.settings', { ...tenantRouteParams, tab: 'users' })"
            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
            :class="isActive('users') ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
        >
            Usuarios<span v-if="currentUserCount !== null && planMaxUsers !== null"> ({{ currentUserCount }}/{{ planMaxUsers }})</span>
        </Link>
        <Link
            :href="route('taller.settings', { ...tenantRouteParams, tab: 'branches' })"
            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
            :class="isActive('branches') ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
        >
            Sucursales<span v-if="branchesCount !== null"> ({{ branchesCount }})</span>
        </Link>
        <Link
            :href="route('taller.settings', { ...tenantRouteParams, tab: 'commercial' })"
            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
            :class="isActive('commercial') ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
        >
            Comercial
        </Link>
        <Link
            v-if="canAccessRoles"
            :href="route('taller.roles.index', tenantRouteParams)"
            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-200"
            :class="isActive('roles') ? 'bg-[#F9A826] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
        >
            Roles y Permisos
        </Link>
    </div>
</template>
