<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tenant: Object,
});

const activeTab = ref('details');

const adminUser = props.tenant.users && props.tenant.users.length > 0 ? props.tenant.users[0] : null;

const tenantForm = useForm({
    name: props.tenant.name || '',
    domain: props.tenant.domain || '',
    plan: props.tenant.plan || 'gratuito',
    status: props.tenant.status || 'active',
});

const adminForm = useForm({
    name: adminUser ? adminUser.name : '',
    email: adminUser ? adminUser.email : '',
    password: '',
});

const submitTenant = () => {
    tenantForm.put(route('admin.tenants.update', props.tenant.id), {
        preserveScroll: true,
    });
};

const submitAdmin = () => {
    adminForm.put(route('admin.tenants.update_admin', props.tenant.id), {
        preserveScroll: true,
        onSuccess: () => adminForm.reset('password'),
    });
};
</script>

<template>
    <Head :title="`Editar Taller: ${tenant.name}`" />

    <AdminLayout>
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.tenants.index')" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 hover:bg-slate-50 hover:text-slate-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Editar Taller: {{ tenant.name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Modifica los detalles globales o gestiona al Administrador de este tenant.</p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-xl overflow-hidden">
            <!-- Tabs Nav -->
            <div class="border-b border-gray-200 bg-gray-50/50 px-4 sm:px-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button 
                        @click="activeTab = 'details'"
                        :class="[activeTab === 'details' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm transition-colors']"
                    >
                        Detalles del Taller
                    </button>
                    <button 
                        @click="activeTab = 'admin'"
                        :class="[activeTab === 'admin' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm transition-colors']"
                    >
                        Administrador
                    </button>
                </nav>
            </div>

            <div class="p-6 sm:p-8">
                <!-- Tab 1: Tenant Details -->
                <div v-show="activeTab === 'details'">
                    <form @submit.prevent="submitTenant" class="space-y-6 max-w-2xl">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Taller</label>
                            <input type="text" id="name" v-model="tenantForm.name" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                            <div v-if="tenantForm.errors.name" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.name }}</div>
                        </div>

                        <div>
                            <label for="domain" class="block text-sm font-medium text-gray-700">Dominio</label>
                            <div class="relative mt-2 rounded-md shadow-sm">
                                <span class="text-gray-500 sm:text-sm absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">http://</span>
                                <input type="text" id="domain" v-model="tenantForm.domain" class="block w-full rounded-md border-gray-200 pl-14 text-gray-900 focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="mitaller.feeto.cl" />
                            </div>
                            <div v-if="tenantForm.errors.domain" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.domain }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="plan" class="block text-sm font-medium text-gray-700">Plan de Suscripción</label>
                                <select id="plan" v-model="tenantForm.plan" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                    <option value="gratuito">Gratuito</option>
                                    <option value="basico">Básico</option>
                                    <option value="profesional">Profesional</option>
                                    <option value="empresa">Empresa</option>
                                </select>
                                <div v-if="tenantForm.errors.plan" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.plan }}</div>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Estado del Servicio</label>
                                <select id="status" v-model="tenantForm.status" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                    <option value="active">Activo</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                                <div v-if="tenantForm.errors.status" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.status }}</div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button type="submit" :disabled="tenantForm.processing" class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Admin User -->
                <div v-show="activeTab === 'admin'">
                    <form @submit.prevent="submitAdmin" class="space-y-6 max-w-2xl">
                        <div class="rounded-md bg-blue-50 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-blue-700">Este es el usuario principal con permisos de administrador dentro del taller.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                            <input type="text" id="admin_name" v-model="adminForm.name" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                            <div v-if="adminForm.errors.name" class="mt-1 text-sm text-red-600">{{ adminForm.errors.name }}</div>
                        </div>

                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                            <input type="email" id="admin_email" v-model="adminForm.email" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                            <div v-if="adminForm.errors.email" class="mt-1 text-sm text-red-600">{{ adminForm.errors.email }}</div>
                        </div>

                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input type="password" id="admin_password" v-model="adminForm.password" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="Deja en blanco para mantener la actual" />
                            <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres. Opcional si ya existe un usuario.</p>
                            <div v-if="adminForm.errors.password" class="mt-1 text-sm text-red-600">{{ adminForm.errors.password }}</div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button type="submit" :disabled="adminForm.processing" class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                                Guardar Administrador
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
