<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { defineComponent, h } from 'vue';

const BuildingOfficeIcon = defineComponent({
  render() {
    return h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', strokeWidth: '1.5', stroke: 'currentColor' }, [
      h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21' })
    ]);
  }
});

const UsersIcon = defineComponent({
  render() {
    return h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', strokeWidth: '1.5', stroke: 'currentColor' }, [
      h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-2.533-4.656 6.953 6.953 0 0 1-5.137 0m1.522-1.74a6.7 6.7 0 0 0-5.78 0 4.125 4.125 0 0 0-2.533 4.656 9.337 9.337 0 0 0 4.121.952 9.38 9.38 0 0 0 2.625-.372 9.236 9.236 0 0 0 4.203-1.488A4.125 4.125 0 0 0 14.829 9.828a6.726 6.726 0 0 0-3.326-2.573ZM9 15c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4Z' })
    ]);
  }
});

const ExclamationTriangleIcon = defineComponent({
  render() {
    return h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', strokeWidth: '1.5', stroke: 'currentColor' }, [
      h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z' })
    ]);
  }
});

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
