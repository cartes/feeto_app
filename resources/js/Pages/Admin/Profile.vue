<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    ai_settings: Object,
    integration_settings: Object,
    payment_settings: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash);
const user = computed(() => page.props.auth.user);

const activeTab = ref('profile');

// --- Mi Perfil form ---
const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
});

const submitProfile = () => {
    profileForm.put(route('admin.profile.update'), { preserveScroll: true });
};

// --- Seguridad form ---
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put(route('admin.profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// --- API Keys form ---
const mpSandboxRaw = props.payment_settings?.mp_sandbox?.value;

const apiForm = useForm({
    ai_provider: props.ai_settings?.ai_provider?.value || 'gemini',
    ai_image_provider: props.ai_settings?.ai_image_provider?.value || 'gemini',
    gemini_api_key: '',
    openai_api_key: '',
    anthropic_api_key: '',
    boostr_api_key: '',
    boostr_base_url: props.integration_settings?.boostr_base_url?.value || '',
    mp_sandbox: mpSandboxRaw === true || mpSandboxRaw === 'true' || mpSandboxRaw === '1',
    mp_access_token: '',
    mp_public_key: '',
    mp_webhook_secret: '',
});

const submitApiKeys = () => {
    apiForm.put(route('admin.profile.api_keys'), { preserveScroll: true });
};

const hasSetting = (group, key) => group?.[key]?.has_value ?? false;
</script>

<template>
    <Head title="Mi Perfil" />

    <AdminLayout>
        <!-- Flash success -->
        <div v-if="flash?.success" class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-inset ring-emerald-600/20 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {{ flash.success }}
        </div>

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Mi Perfil</h1>
            <p class="mt-1 text-sm text-slate-500">Gestiona tu cuenta y credenciales de la plataforma.</p>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-200 sm:rounded-xl overflow-hidden">
            <!-- Tabs Nav -->
            <div class="border-b border-gray-200 bg-gray-50/50 px-4 sm:px-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeTab = 'profile'"
                        :class="activeTab === 'profile' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm transition-colors"
                    >
                        Mi Perfil
                    </button>
                    <button
                        @click="activeTab = 'security'"
                        :class="activeTab === 'security' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm transition-colors"
                    >
                        Seguridad
                    </button>
                    <button
                        @click="activeTab = 'api'"
                        :class="activeTab === 'api' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm transition-colors"
                    >
                        API Keys &amp; Credenciales
                    </button>
                </nav>
            </div>

            <div class="p-6 sm:p-8">
                <!-- Tab: Mi Perfil -->
                <div v-show="activeTab === 'profile'">
                    <form @submit.prevent="submitProfile" class="space-y-6 max-w-lg">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input
                                type="text"
                                id="name"
                                v-model="profileForm.name"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                            <input
                                type="email"
                                id="email"
                                v-model="profileForm.email"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</div>
                        </div>
                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            >
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Seguridad -->
                <div v-show="activeTab === 'security'">
                    <form @submit.prevent="submitPassword" class="space-y-6 max-w-lg">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Contraseña actual</label>
                            <input
                                type="password"
                                id="current_password"
                                v-model="passwordForm.current_password"
                                autocomplete="current-password"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</div>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
                            <input
                                type="password"
                                id="password"
                                v-model="passwordForm.password"
                                autocomplete="new-password"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</div>
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nueva contraseña</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                autocomplete="new-password"
                                class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                            />
                            <div v-if="passwordForm.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password_confirmation }}</div>
                        </div>
                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            >
                                Actualizar Contraseña
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: API Keys & Credenciales -->
                <div v-show="activeTab === 'api'">
                    <form @submit.prevent="submitApiKeys" class="space-y-10">

                        <!-- Sub-group: IA -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 mb-1">IA (Lectura de Patentes)</h3>
                            <p class="text-xs text-slate-500 mb-4">Proveedores y claves para los modelos de inteligencia artificial.</p>
                            <div class="space-y-5 max-w-lg">
                                <div>
                                    <label for="ai_provider" class="block text-sm font-medium text-gray-700">Proveedor de texto activo</label>
                                    <select id="ai_provider" v-model="apiForm.ai_provider" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                        <option value="gemini">Google Gemini</option>
                                        <option value="openai">OpenAI</option>
                                        <option value="anthropic">Anthropic</option>
                                    </select>
                                    <div v-if="apiForm.errors.ai_provider" class="mt-1 text-sm text-red-600">{{ apiForm.errors.ai_provider }}</div>
                                </div>
                                <div>
                                    <label for="ai_image_provider" class="block text-sm font-medium text-gray-700">Proveedor para imágenes (OCR)</label>
                                    <select id="ai_image_provider" v-model="apiForm.ai_image_provider" class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                        <option value="gemini">Google Gemini</option>
                                        <option value="openai">OpenAI</option>
                                        <option value="anthropic">Anthropic</option>
                                    </select>
                                    <div v-if="apiForm.errors.ai_image_provider" class="mt-1 text-sm text-red-600">{{ apiForm.errors.ai_image_provider }}</div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="gemini_api_key" class="block text-sm font-medium text-gray-700">Google Gemini API Key</label>
                                        <span v-if="hasSetting(ai_settings, 'gemini_api_key')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurada</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurada</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="gemini_api_key"
                                        v-model="apiForm.gemini_api_key"
                                        :placeholder="hasSetting(ai_settings, 'gemini_api_key') ? '••••••••' : 'Ingrese la API Key'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.gemini_api_key" class="mt-1 text-sm text-red-600">{{ apiForm.errors.gemini_api_key }}</div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="openai_api_key" class="block text-sm font-medium text-gray-700">OpenAI API Key</label>
                                        <span v-if="hasSetting(ai_settings, 'openai_api_key')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurada</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurada</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="openai_api_key"
                                        v-model="apiForm.openai_api_key"
                                        :placeholder="hasSetting(ai_settings, 'openai_api_key') ? '••••••••' : 'Ingrese la API Key'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.openai_api_key" class="mt-1 text-sm text-red-600">{{ apiForm.errors.openai_api_key }}</div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="anthropic_api_key" class="block text-sm font-medium text-gray-700">Anthropic API Key</label>
                                        <span v-if="hasSetting(ai_settings, 'anthropic_api_key')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurada</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurada</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="anthropic_api_key"
                                        v-model="apiForm.anthropic_api_key"
                                        :placeholder="hasSetting(ai_settings, 'anthropic_api_key') ? '••••••••' : 'Ingrese la API Key'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.anthropic_api_key" class="mt-1 text-sm text-red-600">{{ apiForm.errors.anthropic_api_key }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <!-- Sub-group: Integraciones -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 mb-1">Integraciones</h3>
                            <p class="text-xs text-slate-500 mb-4">Credenciales para servicios externos integrados.</p>
                            <div class="space-y-5 max-w-lg">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="boostr_api_key" class="block text-sm font-medium text-gray-700">Boostr API Key</label>
                                        <span v-if="hasSetting(integration_settings, 'boostr_api_key')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurada</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurada</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="boostr_api_key"
                                        v-model="apiForm.boostr_api_key"
                                        :placeholder="hasSetting(integration_settings, 'boostr_api_key') ? '••••••••' : 'Ingrese la API Key'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.boostr_api_key" class="mt-1 text-sm text-red-600">{{ apiForm.errors.boostr_api_key }}</div>
                                </div>
                                <div>
                                    <label for="boostr_base_url" class="block text-sm font-medium text-gray-700">Boostr Base URL</label>
                                    <input
                                        type="text"
                                        id="boostr_base_url"
                                        v-model="apiForm.boostr_base_url"
                                        placeholder="https://api.boostr.cl"
                                        class="mt-2 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <div v-if="apiForm.errors.boostr_base_url" class="mt-1 text-sm text-red-600">{{ apiForm.errors.boostr_base_url }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <!-- Sub-group: Mercado Pago -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 mb-1">Mercado Pago</h3>
                            <p class="text-xs text-slate-500 mb-4">Credenciales para el procesamiento de pagos.</p>
                            <div class="space-y-5 max-w-lg">
                                <!-- Sandbox toggle -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700">Modo sandbox</span>
                                        <span class="text-xs text-gray-400">Activa el entorno de pruebas de Mercado Pago.</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="apiForm.mp_sandbox = !apiForm.mp_sandbox"
                                        :class="apiForm.mp_sandbox ? 'bg-orange-500' : 'bg-slate-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                                        role="switch"
                                        :aria-checked="apiForm.mp_sandbox"
                                    >
                                        <span :class="apiForm.mp_sandbox ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                                    </button>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="mp_access_token" class="block text-sm font-medium text-gray-700">Access Token</label>
                                        <span v-if="hasSetting(payment_settings, 'mp_access_token')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurado</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurado</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="mp_access_token"
                                        v-model="apiForm.mp_access_token"
                                        :placeholder="hasSetting(payment_settings, 'mp_access_token') ? '••••••••' : 'APP_USR-...'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.mp_access_token" class="mt-1 text-sm text-red-600">{{ apiForm.errors.mp_access_token }}</div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="mp_public_key" class="block text-sm font-medium text-gray-700">Public Key</label>
                                        <span v-if="hasSetting(payment_settings, 'mp_public_key')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurada</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurada</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="mp_public_key"
                                        v-model="apiForm.mp_public_key"
                                        :placeholder="hasSetting(payment_settings, 'mp_public_key') ? '••••••••' : 'APP_USR-...'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.mp_public_key" class="mt-1 text-sm text-red-600">{{ apiForm.errors.mp_public_key }}</div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="mp_webhook_secret" class="block text-sm font-medium text-gray-700">Webhook Secret</label>
                                        <span v-if="hasSetting(payment_settings, 'mp_webhook_secret')" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Configurado</span>
                                        <span v-else class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">No configurado</span>
                                    </div>
                                    <input
                                        type="password"
                                        id="mp_webhook_secret"
                                        v-model="apiForm.mp_webhook_secret"
                                        :placeholder="hasSetting(payment_settings, 'mp_webhook_secret') ? '••••••••' : 'Ingrese el secreto'"
                                        autocomplete="off"
                                        class="block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">Deja en blanco para conservar el valor actual.</p>
                                    <div v-if="apiForm.errors.mp_webhook_secret" class="mt-1 text-sm text-red-600">{{ apiForm.errors.mp_webhook_secret }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button
                                type="submit"
                                :disabled="apiForm.processing"
                                class="inline-flex justify-center rounded-md border border-transparent bg-orange-500 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            >
                                Guardar Credenciales
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
