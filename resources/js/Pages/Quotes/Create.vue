<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import TallerLayout from '@/Layouts/TallerLayout.vue';

const page = usePage();
const tenantRouteParams = computed(() => page.props.tenant?.slug ? { tenantBySlug: page.props.tenant.slug } : {});

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
};

const clearSelectedClient = () => {
    selectedClient.value = null;
    form.client_id = null;
    clientSearch.value = '';
    clientMatches.value = [];
    vehicles.value = [];
    selectedVehicle.value = null;
    form.vehicle_id = null;
};

const selectVehicle = (vehicle) => {
    selectedVehicle.value = vehicle;
    form.vehicle_id = vehicle.id;
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
                <p class="mt-1 text-sm font-medium text-gray-500">Selecciona un cliente y su vehículo para comenzar a cotizar.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6 rounded-[2rem] border border-gray-100 bg-white p-8 shadow-sm">
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

                    <div v-if="selectedClient" class="flex items-start justify-between gap-4 rounded-3xl border border-sky-200 bg-sky-50/70 p-4">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black uppercase tracking-widest text-sky-600">Cliente seleccionado</p>
                            <p class="text-sm font-bold text-slate-900">{{ selectedClient.name }}</p>
                            <p class="text-xs font-semibold text-slate-500">
                                {{ selectedClient.rut }}<span v-if="selectedClient.phone"> · {{ selectedClient.phone }}</span>
                            </p>
                        </div>
                        <button type="button" @click="clearSelectedClient"
                            class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors hover:bg-slate-100">
                            Quitar
                        </button>
                    </div>

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

                <div v-if="selectedClient" class="space-y-3">
                    <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Vehículo</label>

                    <div v-if="isLoadingVehicles" class="flex items-center gap-2 text-xs font-semibold text-gray-400">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF7A00]"></div>
                        Cargando vehículos del cliente...
                    </div>

                    <div v-else-if="vehicles.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-4 text-xs font-semibold uppercase tracking-widest text-gray-400">
                        Este cliente no tiene vehículos registrados.
                    </div>

                    <div v-else class="grid gap-2 sm:grid-cols-2">
                        <button v-for="vehicle in vehicles" :key="vehicle.id" type="button" @click="selectVehicle(vehicle)"
                            class="rounded-2xl border px-5 py-4 text-left transition-colors"
                            :class="selectedVehicle?.id === vehicle.id ? 'border-[#FF7A00] bg-orange-50/60' : 'border-gray-200 bg-white hover:border-gray-300'">
                            <p class="font-mono text-sm font-black tracking-widest text-gray-900">{{ vehicle.plate }}</p>
                            <p class="text-xs font-semibold text-gray-500">{{ vehicle.brand }} {{ vehicle.model }}</p>
                        </button>
                    </div>

                    <p v-if="form.errors.vehicle_id" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.vehicle_id }}</p>
                </div>

                <div class="space-y-3">
                    <label class="ml-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Notas internas (opcional)</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full rounded-2xl border border-gray-300 bg-white px-5 py-4 text-sm font-medium text-gray-900 placeholder-gray-300 shadow-sm transition-all focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#FF7A00]"
                        placeholder="Contexto para el equipo del taller (no visible para el cliente)"></textarea>
                    <p v-if="form.errors.notes" class="ml-1 text-[10px] font-medium text-red-500">{{ form.errors.notes }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link :href="route('quotes.index', tenantRouteParams)"
                        class="rounded-2xl px-5 py-3 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing || !form.client_id || !form.vehicle_id"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-6 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-[#FF7A00] disabled:cursor-not-allowed disabled:opacity-40">
                        Crear cotización
                    </button>
                </div>
            </form>
        </div>
    </TallerLayout>
</template>
