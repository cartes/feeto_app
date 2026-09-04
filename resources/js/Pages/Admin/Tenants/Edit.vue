<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { useIdentification } from '@/composables/useIdentification';

const props = defineProps({
    tenant: Object,
    plans: Array,
});

const {
    formatIdentification,
    validateIdentification,
    getCountryConfig,
    COUNTRY_CONFIGS,
} = useIdentification();

const activeTab = ref('details');

const adminUser = props.tenant.users && props.tenant.users.length > 0 ? props.tenant.users[0] : null;

const TRIAL_PERIOD_OPTIONS = [
    { label: '3 meses', months: 3 },
    { label: '6 meses', months: 6 },
    { label: '1 año', months: 12 },
];

const formatDateInputValue = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const trialEndDateForMonths = (months) => {
    const date = new Date();

    date.setHours(12, 0, 0, 0);
    date.setMonth(date.getMonth() + months);

    return formatDateInputValue(date);
};

const tenantForm = useForm({
    name: props.tenant.name || '',
    country: props.tenant.country || 'CL',
    rut_taller: props.tenant.rut_taller ? formatIdentification(props.tenant.rut_taller, props.tenant.country || 'CL') : '',
    domain: props.tenant.domain || '',
    plan_id: props.tenant.plan_id ?? props.plans[0]?.id ?? null,
    status: props.tenant.status || 'active',
    phone: props.tenant.phone || '',
    seo_address: props.tenant.seo_address || '',
    comuna: props.tenant.comuna || '',
    whatsapp_number: props.tenant.whatsapp_number || '',
    subscription_ends_at: props.tenant.subscription_ends_at || '',
});

const countryConfig = computed(() => getCountryConfig(tenantForm.country));
const docLabel = computed(() => countryConfig.value.docName);
const docPlaceholder = computed(() => countryConfig.value.placeholder);

const isRutValid = computed(() => {
    if (!tenantForm.rut_taller) return null;
    return validateIdentification(tenantForm.rut_taller, tenantForm.country);
});

watch(() => tenantForm.rut_taller, (newVal) => {
    if (!newVal) return;
    const formatted = formatIdentification(newVal, tenantForm.country);
    if (formatted !== newVal) {
        tenantForm.rut_taller = formatted;
    }
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

const selectedTrialPeriod = computed(() => {
    if (!tenantForm.subscription_ends_at) {
        return null;
    }

    const activeOption = TRIAL_PERIOD_OPTIONS.find(({ months }) => {
        return tenantForm.subscription_ends_at === trialEndDateForMonths(months);
    });

    return activeOption ? activeOption.months : null;
});

const setTrialPeriod = (months) => {
    tenantForm.subscription_ends_at = trialEndDateForMonths(months);
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nombre del Taller</label>
                                <input type="text" id="name" v-model="tenantForm.name" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                                <div v-if="tenantForm.errors.name" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.name }}</div>
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700">País</label>
                                <select id="country" v-model="tenantForm.country" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                    <option v-for="(cfg, code) in COUNTRY_CONFIGS" :key="code" :value="code">
                                        {{ cfg.flag }} {{ cfg.name }} ({{ cfg.docName }})
                                    </option>
                                </select>
                                <div v-if="tenantForm.errors.country" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.country }}</div>
                            </div>
                        </div>

                        <div>
                            <label for="rut_taller" class="block text-sm font-medium text-gray-700">
                                {{ docLabel }} del Taller
                            </label>
                            <div class="relative mt-2 rounded-md shadow-sm">
                                <input
                                    type="text"
                                    id="rut_taller"
                                    v-model="tenantForm.rut_taller"
                                    :placeholder="docPlaceholder"
                                    class="block w-full rounded-md border-gray-200 pr-10 text-gray-900 focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span v-if="isRutValid === true" class="text-emerald-600" title="Dígito verificador válido">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span v-else-if="isRutValid === false" class="text-rose-500" title="Dígito verificador inválido">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <p v-if="isRutValid === false" class="mt-1 text-xs text-rose-500">
                                El dígito verificador no es válido para {{ countryConfig.name }}.
                            </p>
                            <div v-if="tenantForm.errors.rut_taller" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.rut_taller }}</div>
                        </div>

                        <div>
                            <label for="domain" class="block text-sm font-medium text-gray-700">Dominio</label>
                            <div class="relative mt-2 rounded-md shadow-sm">
                                <span class="text-gray-500 sm:text-sm absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">http://</span>
                                <input type="text" id="domain" v-model="tenantForm.domain" class="block w-full rounded-md border-gray-200 pl-14 text-gray-900 focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="mitaller.tallerflow.cl" />
                            </div>
                            <div v-if="tenantForm.errors.domain" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.domain }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="plan" class="block text-sm font-medium text-gray-700">Plan de Suscripción</label>
                                <select id="plan" v-model="tenantForm.plan_id" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                    <option v-if="plans.length === 0" disabled value="">No hay planes registrados</option>
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                        {{ plan.name }}{{ plan.is_active ? '' : ' (inactivo)' }}
                                    </option>
                                </select>
                                <p v-if="plans.length === 0" class="mt-1 text-xs text-amber-600">No se encontraron planes. Ejecuta: <code>php artisan db:seed --class=PlanSeeder</code></p>
                                <div v-if="tenantForm.errors.plan_id" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.plan_id }}</div>
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

                        <div>
                            <label for="subscription_ends_at" class="block text-sm font-medium text-gray-700">Fecha término suscripción de prueba</label>
                            <input type="date" id="subscription_ends_at" v-model="tenantForm.subscription_ends_at" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="option in TRIAL_PERIOD_OPTIONS"
                                    :key="option.months"
                                    type="button"
                                    @click="setTrialPeriod(option.months)"
                                    :class="[
                                        selectedTrialPeriod === option.months
                                            ? 'border-orange-500 bg-orange-50 text-orange-700 ring-1 ring-orange-500/30'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                                        'inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium transition-colors',
                                    ]"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Estos botones completan la fecha automáticamente desde hoy.</p>
                            <div v-if="tenantForm.errors.subscription_ends_at" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.subscription_ends_at }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                <input type="text" id="phone" v-model="tenantForm.phone" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="+56 9 1234 5678" />
                                <div v-if="tenantForm.errors.phone" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.phone }}</div>
                            </div>
                            <div>
                                <label for="whatsapp_number" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                                <input type="text" id="whatsapp_number" v-model="tenantForm.whatsapp_number" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="+56 9 1234 5678" />
                                <div v-if="tenantForm.errors.whatsapp_number" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.whatsapp_number }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="seo_address" class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" id="seo_address" v-model="tenantForm.seo_address" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="Av. Siempre Viva 123, Santiago" />
                                <div v-if="tenantForm.errors.seo_address" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.seo_address }}</div>
                            </div>
                            <div>
                                <label for="comuna" class="block text-sm font-medium text-gray-700">Comuna</label>
                                <input type="text" id="comuna" v-model="tenantForm.comuna" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="Las Condes" />
                                <p class="mt-1 text-xs text-gray-500">Se usa para el Directorio de Talleres público.</p>
                                <div v-if="tenantForm.errors.comuna" class="mt-1 text-sm text-red-600">{{ tenantForm.errors.comuna }}</div>
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
                            <PasswordInput id="admin_password" v-model="adminForm.password" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" placeholder="Deja en blanco para mantener la actual" />
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
