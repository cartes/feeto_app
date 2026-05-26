<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    name: '',
    rut_taller: '',
    domain: '',
    plan: 'basico',
    status: 'active',
    subscription_ends_at: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

const submit = () => {
    form.post(route('admin.tenants.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo Taller" />

    <AdminLayout>
        <!-- Header -->
        <div class="mb-8 flex items-center gap-4">
            <Link
                :href="route('admin.tenants.index')"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 hover:bg-slate-50 hover:text-slate-700 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Crear Nuevo Taller</h1>
                <p class="mt-1 text-sm text-slate-500">Registra manualmente un nuevo taller y configura su administrador inicial.</p>
            </div>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-xl overflow-hidden">
            <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-8">
                <!-- Sección 1: Detalles del Taller -->
                <div>
                    <h2 class="text-base font-semibold text-slate-900 mb-6 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600 text-xs font-bold">1</span>
                        Información del Taller
                    </h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Taller <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                required
                                placeholder="Ej: Taller Motors"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label for="rut_taller" class="block text-sm font-medium text-gray-700">RUT del Taller</label>
                            <input
                                type="text"
                                id="rut_taller"
                                v-model="form.rut_taller"
                                placeholder="Ej: 76.123.456-k"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.rut_taller" class="mt-1 text-sm text-red-600">{{ form.errors.rut_taller }}</div>
                        </div>

                        <div>
                            <label for="domain" class="block text-sm font-medium text-gray-700">Dominio</label>
                            <div class="relative mt-2 rounded-md shadow-sm">
                                <span class="text-gray-500 sm:text-sm absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">http://</span>
                                <input
                                    type="text"
                                    id="domain"
                                    v-model="form.domain"
                                    placeholder="mitaller.tallerflow.cl"
                                    class="block w-full rounded-md border-gray-200 pl-14 text-gray-900 focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Opcional. Si queda vacío, se generará como slug.tallerflow.cl</p>
                            <div v-if="form.errors.domain" class="mt-1 text-sm text-red-600">{{ form.errors.domain }}</div>
                        </div>

                        <div>
                            <label for="plan" class="block text-sm font-medium text-gray-700">Plan de Suscripción <span class="text-red-500">*</span></label>
                            <select
                                id="plan"
                                v-model="form.plan"
                                required
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            >
                                <option value="gratuito">Gratuito</option>
                                <option value="basico">Básico</option>
                                <option value="profesional">Profesional</option>
                                <option value="empresa">Empresa</option>
                            </select>
                            <div v-if="form.errors.plan" class="mt-1 text-sm text-red-600">{{ form.errors.plan }}</div>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Estado del Servicio <span class="text-red-500">*</span></label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            >
                                <option value="active">Activo</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                            <div v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</div>
                        </div>

                        <div>
                            <label for="subscription_ends_at" class="block text-sm font-medium text-gray-700">Fecha fin de suscripción</label>
                            <input
                                type="date"
                                id="subscription_ends_at"
                                v-model="form.subscription_ends_at"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-gray-400">Opcional. Deja vacío para acceso ilimitado.</p>
                            <div v-if="form.errors.subscription_ends_at" class="mt-1 text-sm text-red-600">{{ form.errors.subscription_ends_at }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-150"></div>

                <!-- Sección 2: Administrador Inicial -->
                <div>
                    <h2 class="text-base font-semibold text-slate-900 mb-6 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600 text-xs font-bold">2</span>
                        Usuario Administrador
                    </h2>
                    <div class="rounded-md bg-blue-50 p-4 mb-6 max-w-2xl">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">Se creará este usuario y se le asignará automáticamente el rol de Administrador en el nuevo taller.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700">Nombre Completo <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="admin_name"
                                v-model="form.admin_name"
                                required
                                placeholder="Ej: Juan Pérez"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.admin_name" class="mt-1 text-sm text-red-600">{{ form.errors.admin_name }}</div>
                        </div>

                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700">Correo Electrónico <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                id="admin_email"
                                v-model="form.admin_email"
                                required
                                placeholder="juan@taller.cl"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.admin_email" class="mt-1 text-sm text-red-600">{{ form.errors.admin_email }}</div>
                        </div>

                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700">Contraseña <span class="text-red-500">*</span></label>
                            <input
                                type="password"
                                id="admin_password"
                                v-model="form.admin_password"
                                required
                                placeholder="••••••••"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <p class="mt-1 text-xs text-gray-400">Mínimo 8 caracteres.</p>
                            <div v-if="form.errors.admin_password" class="mt-1 text-sm text-red-600">{{ form.errors.admin_password }}</div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-6 border-t border-gray-150 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.tenants.index')"
                        class="inline-flex justify-center rounded-md border border-gray-200 bg-white py-2 px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Creando...' : 'Crear Taller' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
