<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

const currentStep = ref(1);

const form = useForm({
    client_id: null,
    vehicle_id: null,
    notes: '',
});

const clientSearch = ref('');
const clientMatches = ref([]);
const isSearchingClients = ref(false);
const selectedClient = ref(null);

const vehicles = ref([]);
const isLoadingVehicles = ref(false);
const selectedVehicle = ref(null);

const debounce = (fn, delay) => {
    let timeoutId;

    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const fetchClientMatches = async (search) => {
    const normalizedSearch = search.trim();

    if (normalizedSearch.length < 2) {
        clientMatches.value = [];
        return;
    }

    isSearchingClients.value = true;

    try {
        const response = await axios.get(route('receptions.clients.search', tenantRouteParams.value), {
            params: { search: normalizedSearch },
        });

        clientMatches.value = response.data.clients || [];
    } catch (error) {
        clientMatches.value = [];
    } finally {
        isSearchingClients.value = false;
    }
};

const debouncedFetchClientMatches = debounce((value) => {
    void fetchClientMatches(value);
}, 300);

watch(clientSearch, (value) => {
    const selectedLabel = selectedClient.value
        ? `${selectedClient.value.name} · ${selectedClient.value.rut}`
        : null;

    if (selectedLabel !== null && value === selectedLabel) {
        return;
    }

    debouncedFetchClientMatches(value);
});

const fetchVehiclesForClient = async (client) => {
    isLoadingVehicles.value = true;
    vehicles.value = [];
    selectedVehicle.value = null;
    form.vehicle_id = null;

    try {
        const response = await axios.get(route('clients.vehicles', { ...tenantRouteParams.value, client: client.id }));
        vehicles.value = response.data.vehicles || [];

        if (vehicles.value.length === 1) {
            selectVehicle(vehicles.value[0]);
        }
    } catch (error) {
        vehicles.value = [];
    } finally {
        isLoadingVehicles.value = false;
    }
};

const selectClient = (client) => {
    selectedClient.value = client;
    form.client_id = client.id;
    clientSearch.value = `${client.name} · ${client.rut}`;
    clientMatches.value = [];
    void fetchVehiclesForClient(client);
    currentStep.value = 2; // Auto advance to Step 2
};

const clearSelectedClient = () => {
    selectedClient.value = null;
    form.client_id = null;
    clientSearch.value = '';
    clientMatches.value = [];
    vehicles.value = [];
    selectedVehicle.value = null;
    form.vehicle_id = null;
    currentStep.value = 1; // Reset to Step 1
};

const selectVehicle = (vehicle) => {
    selectedVehicle.value = vehicle;
    form.vehicle_id = vehicle.id;
};

const goToStep = (step) => {
    if (step === 1) {
        currentStep.value = 1;
    } else if (step === 2 && form.client_id) {
        currentStep.value = 2;
    } else if (step === 3 && form.client_id && form.vehicle_id) {
        currentStep.value = 3;
    }
};

const nextStep = () => {
    if (currentStep.value === 1 && form.client_id) {
        currentStep.value = 2;
    } else if (currentStep.value === 2 && form.vehicle_id) {
        currentStep.value = 3;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value -= 1;
    }
};

const submit = () => {
    form.post(route('quotes.store', tenantRouteParams.value));
};
</script>

<template>
    <Head title="Nueva cotización" />

    <TallerLayout>
        <div class="mx-auto max-w-2xl space-y-8">
            <div>
                <Link :href="route('quotes.index', tenantRouteParams)"
                    class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest text-gray-400 transition-colors hover:text-[#FF7A00]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a cotizaciones
                </Link>
                <h1 class="mt-3 text-3xl font-black uppercase tracking-tight text-gray-900">Nueva cotización</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">Completa los pasos para comenzar a cotizar.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-8 rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
                <!-- Stepper Visual Component -->
                <div class="border-b border-gray-100 pb-6">
                    <div class="flex items-center justify-between">
                        <!-- Step 1 Link/Button -->
                        <button type="button" @click="goToStep(1)" class="group flex flex-col items-center gap-2 focus:outline-none">
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                :class="[
                                    currentStep === 1
                                        ? 'border-[#FF7A00] bg-orange-50 text-[#FF7A00] ring-4 ring-orange-100'
                                        : (form.client_id ? 'border-[#FF7A00] bg-[#FF7A00] text-white' : 'border-gray-200 bg-white text-gray-400')
                                ]">
                                <svg v-if="form.client_id && currentStep !== 1" class="h-5 w-5 animate-scale-up" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else class="text-sm font-black">1</span>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="currentStep === 1 ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-600'">
                                Cliente
                            </span>
                        </button>

                        <!-- Line Connector 1-2 -->
                        <div class="h-0.5 flex-1 mx-4 transition-all duration-500"
                            :class="form.client_id ? 'bg-[#FF7A00]' : 'bg-gray-100'"></div>

                        <!-- Step 2 Link/Button -->
                        <button type="button" @click="goToStep(2)" :disabled="!form.client_id"
                            class="group flex flex-col items-center gap-2 focus:outline-none disabled:cursor-not-allowed">
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                :class="[
                                    currentStep === 2
                                        ? 'border-[#FF7A00] bg-orange-50 text-[#FF7A00] ring-4 ring-orange-100'
                                        : (form.vehicle_id ? 'border-[#FF7A00] bg-[#FF7A00] text-white' : 'border-gray-200 bg-white text-gray-400')
                                ]">
                                <svg v-if="form.vehicle_id && currentStep !== 2" class="h-5 w-5 animate-scale-up" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else class="text-sm font-black">2</span>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="currentStep === 2 ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-600 disabled:group-hover:text-gray-400'">
                                Vehículo
                            </span>
                        </button>

                        <!-- Line Connector 2-3 -->
                        <div class="h-0.5 flex-1 mx-4 transition-all duration-500"
                            :class="form.vehicle_id ? 'bg-[#FF7A00]' : 'bg-gray-100'"></div>

                        <!-- Step 3 Link/Button -->
                        <button type="button" @click="goToStep(3)" :disabled="!form.client_id || !form.vehicle_id"
                            class="group flex flex-col items-center gap-2 focus:outline-none disabled:cursor-not-allowed">
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                :class="[
                                    currentStep === 3
                                        ? 'border-[#FF7A00] bg-orange-50 text-[#FF7A00] ring-4 ring-orange-100'
                                        : 'border-gray-200 bg-white text-gray-400'
                                ]">
                                <span class="text-sm font-black">3</span>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest transition-colors"
                                :class="currentStep === 3 ? 'text-gray-900' : 'text-gray-400 group-hover:text-gray-600 disabled:group-hover:text-gray-400'">
                                Confirmación
                            </span>
                        </button>
                    </div>
                </div>

                <!-- STEP 1: CLIENT SELECTION -->
                <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                    <div class="space-y-3">
                        <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Cliente</label>

                        <div class="relative">
                            <input v-model="clientSearch" type="text" :disabled="!!selectedClient"
                                class="w-full rounded-2xl border border-gray-300 bg-white px-5 py-4 pr-12 text-sm font-semibold text-gray-900 placeholder-gray-300 shadow-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00] disabled:bg-gray-50 disabled:text-gray-500"
                                placeholder="Buscar por nombre o RUT" />
                            <div v-if="isSearchingClients" class="absolute inset-y-0 right-4 flex items-center text-gray-300">
                                <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF7A00]"></div>
                            </div>
                        </div>

                        <!-- Selected Client Card -->
                        <div v-if="selectedClient" class="flex items-start justify-between gap-4 rounded-3xl border border-orange-100 bg-orange-50/30 p-5 shadow-sm">
                            <div class="space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#FF7A00]">Cliente seleccionado</p>
                                <p class="text-base font-bold text-slate-900">{{ selectedClient.name }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                                    <span>{{ selectedClient.rut }}</span>
                                    <span v-if="selectedClient.phone" class="before:content-['·'] before:mr-2">{{ selectedClient.phone }}</span>
                                    <span v-if="selectedClient.email" class="before:content-['·'] before:mr-2">{{ selectedClient.email }}</span>
                                </div>
                            </div>
                            <button type="button" @click="clearSelectedClient"
                                class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-slate-100">
                                Cambiar
                            </button>
                        </div>

                        <!-- Search matches dropdown -->
                        <div v-else-if="clientMatches.length > 0" class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                            <button v-for="client in clientMatches" :key="client.id" type="button" @click="selectClient(client)"
                                class="w-full border-b border-gray-100 px-5 py-4 text-left transition-colors last:border-b-0 hover:bg-slate-50">
                                <p class="text-sm font-bold uppercase text-slate-900">{{ client.name }}</p>
                                <p class="text-xs font-semibold text-slate-500">
                                    {{ client.rut }}<span v-if="client.phone"> · {{ client.phone }}</span>
                                </p>
                            </button>
                        </div>

                        <p v-else-if="clientSearch.trim().length >= 2 && !isSearchingClients"
                            class="ml-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            No encontramos coincidencias en este taller.
                        </p>

                        <p v-if="form.errors.client_id" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.client_id }}</p>
                    </div>

                    <!-- Step 1 Navigation Footer -->
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                        <Link :href="route('quotes.index', tenantRouteParams)"
                            class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                            Cancelar
                        </Link>
                        <button type="button" :disabled="!form.client_id" @click="nextStep"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                            Siguiente
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: VEHICLE SELECTION -->
                <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                    <div class="space-y-4">
                        <!-- Client Header Banner -->
                        <div class="flex items-center justify-between rounded-2xl bg-gray-50 px-5 py-3 text-xs font-semibold text-gray-500">
                            <div>
                                Cliente: <span class="font-bold text-gray-900">{{ selectedClient?.name }}</span>
                            </div>
                            <button type="button" @click="goToStep(1)" class="text-xs font-black uppercase text-[#FF7A00] hover:underline">
                                Cambiar cliente
                            </button>
                        </div>

                        <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículo</label>

                        <div v-if="isLoadingVehicles" class="flex items-center gap-2 py-4 text-xs font-semibold text-gray-400">
                            <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF7A00]"></div>
                            Cargando vehículos del cliente...
                        </div>

                        <div v-else-if="vehicles.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 text-center text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Este cliente no tiene vehículos registrados.
                        </div>

                        <div v-else class="grid gap-3 sm:grid-cols-2">
                            <button v-for="vehicle in vehicles" :key="vehicle.id" type="button" @click="selectVehicle(vehicle)"
                                class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200 shadow-sm"
                                :class="selectedVehicle?.id === vehicle.id
                                    ? 'border-[#FF7A00] bg-orange-50/60 ring-2 ring-[#FF7A00]/20'
                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md'">
                                <div class="absolute right-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                    :class="selectedVehicle?.id === vehicle.id ? 'border-[#FF7A00] bg-[#FF7A00] text-white' : 'border-gray-300 bg-white'">
                                    <svg v-if="selectedVehicle?.id === vehicle.id" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="font-mono text-base font-black tracking-widest text-gray-900">{{ vehicle.plate }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ vehicle.brand }} {{ vehicle.model }}</p>
                            </button>
                        </div>

                        <p v-if="form.errors.vehicle_id" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.vehicle_id }}</p>
                    </div>

                    <!-- Step 2 Navigation Footer -->
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                        <button type="button" @click="prevStep"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 active:scale-[0.98]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Atrás
                        </button>
                        <button type="button" :disabled="!form.vehicle_id" @click="nextStep"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                            Siguiente
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: NOTES & CONFIRMATION -->
                <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                    <div class="space-y-5">
                        <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Resumen de la Cotización</label>

                        <!-- Summary Cards Grid -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Client Card -->
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Cliente</p>
                                    <button type="button" @click="goToStep(1)" class="text-[10px] font-bold uppercase text-[#FF7A00] hover:underline">
                                        Editar
                                    </button>
                                </div>
                                <p class="text-sm font-bold text-gray-900">{{ selectedClient?.name }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ selectedClient?.rut }}</p>
                                <p v-if="selectedClient?.phone" class="mt-0.5 text-xs font-semibold text-gray-500">{{ selectedClient?.phone }}</p>
                            </div>

                            <!-- Vehicle Card -->
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículo</p>
                                    <button type="button" @click="goToStep(2)" class="text-[10px] font-bold uppercase text-[#FF7A00] hover:underline">
                                        Editar
                                    </button>
                                </div>
                                <p class="font-mono text-sm font-black tracking-widest text-gray-900">{{ selectedVehicle?.plate }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ selectedVehicle?.brand }} {{ selectedVehicle?.model }}</p>
                            </div>
                        </div>

                        <!-- Notes Area -->
                        <div class="space-y-3 pt-2">
                            <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Notas internas (opcional)</label>
                            <textarea v-model="form.notes" rows="4"
                                class="w-full rounded-2xl border border-gray-300 bg-white px-5 py-4 text-sm font-medium text-gray-900 placeholder-gray-300 shadow-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                                placeholder="Contexto para el equipo del taller (no visible para el cliente)"></textarea>
                            <p v-if="form.errors.notes" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.notes }}</p>
                        </div>
                    </div>

                    <!-- Step 3 Navigation Footer -->
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                        <button type="button" @click="prevStep"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-600 shadow-sm transition-all hover:bg-gray-50 active:scale-[0.98]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Atrás
                        </button>
                        <button type="submit" :disabled="form.processing || !form.client_id || !form.vehicle_id"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-all hover:bg-[#FF7A00] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40">
                            {{ form.processing ? 'Creando...' : 'Crear cotización' }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </TallerLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scaleUp {
    from {
        transform: scale(0.9);
    }
    to {
        transform: scale(1);
    }
}

.animate-fade-in {
    animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-scale-up {
    animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
