<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
    brandingRoutes: Object,
});

const colorForm = useForm({
    primary_color: props.tenant?.primary_color ?? '#FF7A00',
});

const logoFile = ref(null);
const logoPreview = ref(props.tenant?.logo_url ?? null);
const logoForm = useForm({ logo: null });

const onLogoSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    logoFile.value = file;
    logoPreview.value = URL.createObjectURL(file);
    logoForm.logo = file;
};

const submitColor = () => {
    colorForm.patch(props.brandingRoutes.color, { preserveScroll: true });
};

const submitLogo = () => {
    logoForm.post(props.brandingRoutes.logo, {
        preserveScroll: true,
        onSuccess: () => { logoFile.value = null; },
    });
};

const deleteLogo = () => {
    if (!confirm('¿Eliminar el logo del taller?')) return;
    router.delete(props.brandingRoutes.deleteLogo, { preserveScroll: true, onSuccess: () => { logoPreview.value = null; } });
};

// Watch for changes in props.tenant to update color form and logo preview reactively
watch(() => props.tenant, (newTenant) => {
    if (newTenant) {
        colorForm.primary_color = newTenant.primary_color ?? '#FF7A00';
        if (!logoFile.value) {
            logoPreview.value = newTenant.logo_url ?? null;
        }
    }
}, { deep: true });
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-300">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Color primario -->
            <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">Color del Taller</h3>
                    <p class="text-xs text-gray-400 mt-1 font-medium">Se usará en tu página pública para botones y acentos de color.</p>
                </div>
                <form @submit.prevent="submitColor" class="space-y-5">
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Color principal</label>
                        <div class="flex items-center gap-4">
                            <input
                                v-model="colorForm.primary_color"
                                type="color"
                                class="h-14 w-14 rounded-2xl border border-gray-200 cursor-pointer p-1 bg-gray-50"
                            />
                            <div>
                                <p class="text-lg font-black tracking-tight text-gray-900" :style="{ color: colorForm.primary_color }">{{ colorForm.primary_color.toUpperCase() }}</p>
                                <p class="text-xs text-gray-400 font-medium mt-0.5">Haz clic en el cuadro para elegir</p>
                            </div>
                        </div>
                        <p v-if="colorForm.errors.primary_color" class="text-red-500 text-xs">{{ colorForm.errors.primary_color }}</p>
                    </div>

                    <!-- Preview del color -->
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 space-y-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Vista previa</p>
                        <button type="button" class="inline-flex items-center gap-2 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-sm transition-all" :style="{ backgroundColor: colorForm.primary_color }">
                            Agendar mi Cita Ahora
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="colorForm.processing"
                            class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50">
                            {{ colorForm.processing ? 'Guardando...' : 'Guardar Color' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Logo del taller -->
            <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-5">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-widest text-gray-500">Logo del Taller</h3>
                    <p class="text-xs text-gray-400 mt-1 font-medium">Se mostrará en tu página pública con el nombre del taller como texto alternativo.</p>
                </div>

                <!-- Preview del logo actual -->
                <div class="flex items-center gap-4">
                    <div class="h-20 w-20 rounded-2xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                        <img v-if="logoPreview" :src="logoPreview" :alt="tenant?.name" class="h-full w-full object-contain p-1" />
                        <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-black text-gray-900">{{ logoPreview ? tenant?.name : 'Sin logo' }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">PNG, JPG o WebP. Máx. 2 MB.</p>
                        <button v-if="logoPreview && !logoFile" @click="deleteLogo" type="button"
                            class="mt-2 text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                            Eliminar logo
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitLogo" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Subir nuevo logo</label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="onLogoSelected"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#FF7A00]/10 file:text-[#FF7A00] hover:file:bg-[#FF7A00]/20 cursor-pointer"
                        />
                        <p v-if="logoForm.errors.logo" class="text-red-500 text-xs">{{ logoForm.errors.logo }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="!logoFile || logoForm.processing"
                            class="px-6 py-2.5 bg-[#FF7A00] text-white rounded-xl font-bold text-sm shadow-sm hover:bg-[#CC6200] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ logoForm.processing ? 'Subiendo...' : 'Subir Logo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
