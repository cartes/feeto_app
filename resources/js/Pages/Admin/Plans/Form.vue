<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plan: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash);
const isEditing = computed(() => !!props.plan);

const form = useForm({
    name: props.plan?.name || '',
    description: props.plan?.description || '',
    price_monthly: props.plan?.price_monthly ?? '',
    price_annual: props.plan?.price_annual ?? '',
    max_users: props.plan?.max_users ?? '',
    trial_days: props.plan?.trial_days ?? 0,
    features: props.plan?.features ? [...props.plan.features] : [],
    is_active: props.plan?.is_active ?? true,
    is_popular: props.plan?.is_popular ?? false,
    discount_percent: props.plan?.discount_percent ?? '',
    discount_valid_until: props.plan?.discount_valid_until
        ? props.plan.discount_valid_until.substring(0, 10)
        : '',
    sort_order: props.plan?.sort_order ?? 0,
});

const featureInput = ref('');

const addFeature = () => {
    const trimmed = featureInput.value.trim();
    if (trimmed) {
        form.features.push(trimmed);
        featureInput.value = '';
    }
};

const removeFeature = (index) => {
    form.features.splice(index, 1);
};

const handleFeatureKeydown = (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addFeature();
    }
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.plans.update', props.plan.id), { preserveScroll: true });
    } else {
        form.post(route('admin.plans.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isEditing ? `Editar Plan: ${plan.name}` : 'Nuevo Plan'" />

    <AdminLayout>
        <!-- Flash success -->
        <div v-if="flash?.success" class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-inset ring-emerald-600/20 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <!-- Header -->
        <div class="mb-8 flex items-center gap-4">
            <Link
                :href="route('admin.plans.index')"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 hover:bg-slate-50 hover:text-slate-700 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ isEditing ? `Editar Plan: ${plan.name}` : 'Nuevo Plan' }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ isEditing ? 'Modifica los detalles del plan de suscripción.' : 'Crea un nuevo plan de suscripción para los talleres.' }}
                </p>
            </div>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-xl overflow-hidden">
            <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-8">

                <!-- Basic info -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Información general</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre del plan <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                required
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Pricing -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Precios (CLP)</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="price_monthly" class="block text-sm font-medium text-gray-700">Precio Mensual</label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-400 sm:text-sm">$</span>
                                </div>
                                <input
                                    type="number"
                                    id="price_monthly"
                                    v-model="form.price_monthly"
                                    min="0"
                                    class="block w-full rounded-md border-gray-200 pl-7 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                />
                            </div>
                            <div v-if="form.errors.price_monthly" class="mt-1 text-sm text-red-600">{{ form.errors.price_monthly }}</div>
                        </div>
                        <div>
                            <label for="price_annual" class="block text-sm font-medium text-gray-700">Precio Anual</label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-400 sm:text-sm">$</span>
                                </div>
                                <input
                                    type="number"
                                    id="price_annual"
                                    v-model="form.price_annual"
                                    min="0"
                                    class="block w-full rounded-md border-gray-200 pl-7 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                />
                            </div>
                            <div v-if="form.errors.price_annual" class="mt-1 text-sm text-red-600">{{ form.errors.price_annual }}</div>
                        </div>
                        <div>
                            <label for="discount_percent" class="block text-sm font-medium text-gray-700">Descuento (%)</label>
                            <input
                                type="number"
                                id="discount_percent"
                                v-model="form.discount_percent"
                                min="0"
                                max="100"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                placeholder="0"
                            />
                            <div v-if="form.errors.discount_percent" class="mt-1 text-sm text-red-600">{{ form.errors.discount_percent }}</div>
                        </div>
                        <div>
                            <label for="discount_valid_until" class="block text-sm font-medium text-gray-700">Descuento válido hasta</label>
                            <input
                                type="date"
                                id="discount_valid_until"
                                v-model="form.discount_valid_until"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="form.errors.discount_valid_until" class="mt-1 text-sm text-red-600">{{ form.errors.discount_valid_until }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Limits -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Límites y configuración</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label for="max_users" class="block text-sm font-medium text-gray-700">Usuarios máximos</label>
                            <input
                                type="number"
                                id="max_users"
                                v-model="form.max_users"
                                min="1"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                placeholder="Sin límite"
                            />
                            <div v-if="form.errors.max_users" class="mt-1 text-sm text-red-600">{{ form.errors.max_users }}</div>
                        </div>
                        <div>
                            <label for="trial_days" class="block text-sm font-medium text-gray-700">Días de prueba</label>
                            <input
                                type="number"
                                id="trial_days"
                                v-model="form.trial_days"
                                min="0"
                                max="90"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                placeholder="0"
                            />
                            <div v-if="form.errors.trial_days" class="mt-1 text-sm text-red-600">{{ form.errors.trial_days }}</div>
                        </div>
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700">Orden de aparición</label>
                            <input
                                type="number"
                                id="sort_order"
                                v-model="form.sort_order"
                                min="0"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                placeholder="0"
                            />
                            <div v-if="form.errors.sort_order" class="mt-1 text-sm text-red-600">{{ form.errors.sort_order }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Features -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Funcionalidades incluidas</h2>
                    <div class="max-w-lg space-y-3">
                        <div v-if="form.features.length" class="space-y-2">
                            <div
                                v-for="(feature, index) in form.features"
                                :key="index"
                                class="flex items-center gap-2 rounded-md bg-slate-50 px-3 py-2 ring-1 ring-slate-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="flex-1 text-sm text-slate-700">{{ feature }}</span>
                                <button
                                    type="button"
                                    @click="removeFeature(index)"
                                    class="text-slate-400 hover:text-rose-500 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                v-model="featureInput"
                                @keydown="handleFeatureKeydown"
                                placeholder="Ej: Hasta 5 usuarios"
                                class="flex-1 rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <button
                                type="button"
                                @click="addFeature"
                                class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Agregar
                            </button>
                        </div>
                        <div v-if="form.errors.features" class="mt-1 text-sm text-red-600">{{ form.errors.features }}</div>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Toggles -->
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 mb-4">Visibilidad y estado</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between max-w-lg">
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Plan activo</span>
                                <span class="text-xs text-gray-400">Los talleres pueden suscribirse a este plan.</span>
                            </div>
                            <button
                                type="button"
                                @click="form.is_active = !form.is_active"
                                :class="form.is_active ? 'bg-orange-500' : 'bg-slate-200'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                role="switch"
                                :aria-checked="form.is_active"
                            >
                                <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between max-w-lg">
                            <div>
                                <span class="block text-sm font-medium text-gray-700">⭐ Marcar como popular</span>
                                <span class="text-xs text-gray-400">Muestra una insignia destacada en la página de precios.</span>
                            </div>
                            <button
                                type="button"
                                @click="form.is_popular = !form.is_popular"
                                :class="form.is_popular ? 'bg-orange-500' : 'bg-slate-200'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                role="switch"
                                :aria-checked="form.is_popular"
                            >
                                <span :class="form.is_popular ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.plans.index')"
                        class="inline-flex justify-center rounded-md border border-gray-200 bg-white py-2 px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                    >
                        {{ isEditing ? 'Guardar Cambios' : 'Crear Plan' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
