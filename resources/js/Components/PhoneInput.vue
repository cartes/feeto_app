<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CountryFlagSvg from '@/Components/CountryFlagSvg.vue';
import {
    PHONE_COUNTRY_CONFIGS,
    PHONE_COUNTRY_LIST,
    cleanDigits,
    detectBrowserCountry,
    formatNationalNumber,
    parsePhoneNumber,
} from '@/composables/usePhone';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    defaultCountry: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    placeholder: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    errorMessage: {
        type: String,
        default: null,
    },
    helpText: {
        type: String,
        default: null,
    },
    inputClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change', 'blur', 'country-change']);

const page = usePage();

// 1. Detección del país predeterminado: Props -> Tenant -> Browser -> 'CL'
const resolvedDefaultCountry = computed(() => {
    if (props.defaultCountry) return props.defaultCountry.toUpperCase().trim();
    const tenantCountry = page.props.tenantContext?.country;
    if (tenantCountry && PHONE_COUNTRY_CONFIGS[tenantCountry.toUpperCase()]) {
        return tenantCountry.toUpperCase();
    }
    return detectBrowserCountry();
});

const selectedCountryCode = ref(resolvedDefaultCountry.value);
const localDigits = ref('');
const formattedDisplay = ref('');
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const currentCountryConfig = computed(() => {
    return PHONE_COUNTRY_CONFIGS[selectedCountryCode.value] || PHONE_COUNTRY_CONFIGS.CL;
});

const activePlaceholder = computed(() => {
    return props.placeholder || currentCountryConfig.value.placeholder;
});

// Sincronizar desde modelValue entrante
const syncFromModel = (val) => {
    const parsed = parsePhoneNumber(val, resolvedDefaultCountry.value);
    selectedCountryCode.value = parsed.countryCode;
    localDigits.value = parsed.nationalDigits;
    formattedDisplay.value = parsed.formattedNational;
};

// Inicializar
syncFromModel(props.modelValue);

// Vigilar cambios externos en modelValue
watch(() => props.modelValue, (newVal) => {
    const currentFull = localDigits.value
        ? `+${currentCountryConfig.value.dialCode}${localDigits.value}`
        : '';
    if (newVal !== currentFull) {
        syncFromModel(newVal);
    }
});

// Vigilar cambios en el país por defecto si cambia el tenant context
watch(resolvedDefaultCountry, (newDefault) => {
    if (!props.modelValue && !localDigits.value) {
        selectedCountryCode.value = newDefault;
    }
});

const emitValue = () => {
    const clean = cleanDigits(localDigits.value);
    const fullNumber = clean ? `+${currentCountryConfig.value.dialCode}${clean}` : '';
    emit('update:modelValue', fullNumber);
    emit('change', fullNumber);
};

const handleInput = (event) => {
    const raw = event.target.value;
    const digits = cleanDigits(raw);

    // Limitar longitud según el país para evitar desbordes accidentales
    const maxLen = currentCountryConfig.value.nationalLength + 2;
    const boundedDigits = digits.slice(0, maxLen);

    localDigits.value = boundedDigits;
    formattedDisplay.value = formatNationalNumber(boundedDigits, selectedCountryCode.value);
    emitValue();
};

const handleBlur = (event) => {
    emit('blur', event);
};

const selectCountry = (country) => {
    selectedCountryCode.value = country.code;
    isDropdownOpen.value = false;
    formattedDisplay.value = formatNationalNumber(localDigits.value, country.code);
    emitValue();
    emit('country-change', country);
};

const toggleDropdown = () => {
    if (!props.disabled) {
        isDropdownOpen.value = !isDropdownOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('pointerdown', handleClickOutside);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('pointerdown', handleClickOutside);
    }
});
</script>

<template>
    <div class="space-y-1 w-full" ref="dropdownRef">
        <!-- Label superior -->
        <div v-if="label" class="flex items-center justify-between">
            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                {{ label }}
                <span v-if="required" class="text-rose-500">*</span>
            </label>
            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                {{ currentCountryConfig.name }}
            </span>
        </div>

        <div class="relative flex rounded-2xl shadow-sm">
            <!-- Botón Selector de Prefijo -->
            <button
                type="button"
                @click="toggleDropdown"
                :disabled="disabled"
                :class="[
                    'relative inline-flex items-center gap-1.5 rounded-l-2xl border border-r-0 bg-gray-50 px-3 py-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-100 focus:z-10 focus:outline-none shrink-0',
                    errorMessage
                        ? 'border-rose-300 bg-rose-50/20'
                        : 'border-gray-200 focus:border-[#FF7A00]',
                    disabled ? 'cursor-not-allowed opacity-60' : '',
                ]"
                :title="`País seleccionado: ${currentCountryConfig.name} (${currentCountryConfig.prefix})`"
            >
                <CountryFlagSvg :country="currentCountryConfig.code" className="w-5 h-3.5" />
                <span class="font-extrabold text-gray-800">{{ currentCountryConfig.prefix }}</span>
                <svg
                    class="h-3 w-3 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isDropdownOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Menú Desplegable de Países -->
            <div
                v-if="isDropdownOpen"
                class="absolute left-0 top-full z-50 mt-1 max-h-60 w-64 overflow-y-auto rounded-2xl border border-gray-100 bg-white p-1.5 shadow-xl ring-1 ring-black/5"
            >
                <div class="px-2 py-1 text-[10px] font-black uppercase tracking-wider text-gray-400">
                    Seleccionar país
                </div>
                <button
                    v-for="c in PHONE_COUNTRY_LIST"
                    :key="c.code"
                    type="button"
                    @click="selectCountry(c)"
                    :class="[
                        'flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left text-xs font-bold transition-colors',
                        selectedCountryCode === c.code
                            ? 'bg-[#FF7A00]/10 text-[#FF7A00]'
                            : 'text-gray-700 hover:bg-gray-50',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <CountryFlagSvg :country="c.code" className="w-5 h-3.5" />
                        <span>{{ c.name }}</span>
                    </div>
                    <span class="font-mono text-[11px] text-gray-400">{{ c.prefix }}</span>
                </button>
            </div>

            <!-- Input de número local -->
            <input
                type="tel"
                inputmode="numeric"
                :value="formattedDisplay"
                :placeholder="activePlaceholder"
                :disabled="disabled"
                :required="required"
                @input="handleInput"
                @blur="handleBlur"
                autocomplete="tel-national"
                :class="[
                    'w-full rounded-r-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold text-gray-900 outline-none transition-all placeholder:text-gray-300 focus:border-[#FF7A00] focus:bg-white focus:ring-2 focus:ring-[#FF7A00]/20',
                    errorMessage
                        ? 'border-rose-300 bg-rose-50/20 focus:ring-rose-400'
                        : '',
                    disabled ? 'cursor-not-allowed bg-gray-100 text-gray-400' : '',
                    inputClass,
                ]"
            />
        </div>

        <!-- Mensaje de error -->
        <p v-if="errorMessage" class="text-xs text-rose-500 font-medium ml-1">
            {{ errorMessage }}
        </p>
        <p v-else-if="helpText" class="text-[10px] text-gray-400 font-medium ml-1">
            {{ helpText }}
        </p>
    </div>
</template>
