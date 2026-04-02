<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
  BuildingOfficeIcon, 
  UsersIcon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    totalTenants: Number,
    activeUsers: Number,
    expiredSubscriptions: Number,
});

const stats = [
  { name: 'Total Talleres', stat: props.totalTenants, icon: BuildingOfficeIcon, color: 'text-blue-500', bg: 'bg-blue-50' },
  { name: 'Usuarios Globales', stat: props.activeUsers, icon: UsersIcon, color: 'text-amber-500', bg: 'bg-amber-50' },
  { name: 'Suscripciones Vencidas', stat: props.expiredSubscriptions, icon: ExclamationTriangleIcon, color: 'text-rose-500', bg: 'bg-rose-50' },
];
</script>

<template>
    <Head title="Panel de Administración Global" />

    <AdminLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Panel de Administración Global</h1>
            <p class="mt-1 text-sm text-slate-500">Métricas principales de toda la plataforma SaaS.</p>
        </div>

        <div>
            <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="item in stats" :key="item.name" class="relative overflow-hidden rounded-xl bg-white px-4 pb-12 pt-5 shadow-sm ring-1 ring-slate-900/5 sm:px-6 sm:pt-6">
                    <dt>
                        <div :class="[item.bg, 'absolute rounded-md p-3']">
                            <component :is="item.icon" :class="[item.color, 'h-6 w-6']" aria-hidden="true" />
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-slate-500">{{ item.name }}</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                        <p class="text-2xl font-semibold text-slate-900">{{ item.stat }}</p>
                    </dd>
                </div>
            </dl>
        </div>
    </AdminLayout>
</template>
