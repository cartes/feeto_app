import { computed, ref } from 'vue';
import axios from 'axios';

const MANUAL_SELECTION = '__manual__';

const normalize = (value) =>
    String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '');

export function useVehicleCatalog({
    form,
    brands,
    tenantRouteParams,
    brandField,
    modelField,
    brandIdField,
    modelIdField,
}) {
    const brandSelection = ref(form[brandIdField] ? String(form[brandIdField]) : '');
    const modelSelection = ref(form[modelIdField] ? String(form[modelIdField]) : '');
    const modelOptions = ref([]);
    const loadingModels = ref(false);
    const brandOptions = computed(() => Array.isArray(brands) ? brands : (brands.value ?? []));

    const selectedBrand = computed(() =>
        brandOptions.value.find((brand) => String(brand.id) === brandSelection.value) ?? null,
    );

    const isManualBrand = computed(() => brandSelection.value === MANUAL_SELECTION);
    const isManualModel = computed(() => isManualBrand.value || modelSelection.value === MANUAL_SELECTION);

    const resetModel = () => {
        modelOptions.value = [];
        modelSelection.value = '';
        form[modelIdField] = null;
        form[modelField] = '';
    };

    const reset = () => {
        brandSelection.value = '';
        modelSelection.value = '';
        modelOptions.value = [];
        form[brandIdField] = null;
        form[modelIdField] = null;
        form[brandField] = '';
        form[modelField] = '';
    };

    const setManualBrand = () => {
        brandSelection.value = MANUAL_SELECTION;
        form[brandIdField] = null;
        resetModel();
        modelSelection.value = MANUAL_SELECTION;
    };

    const setManualModel = () => {
        modelSelection.value = MANUAL_SELECTION;
        form[modelIdField] = null;
    };

    const loadModels = async (brandId) => {
        loadingModels.value = true;

        try {
            const response = await axios.get(route('vehicle-catalog.models', {
                ...tenantRouteParams.value,
                vehicleBrand: brandId,
            }));

            modelOptions.value = response.data.models ?? [];
        } finally {
            loadingModels.value = false;
        }
    };

    const applyModelSelection = (selection) => {
        if (!selection) {
            modelSelection.value = '';
            form[modelIdField] = null;
            form[modelField] = '';

            return;
        }

        if (selection === MANUAL_SELECTION) {
            setManualModel();

            return;
        }

        const selectedModel = modelOptions.value.find((model) => String(model.id) === String(selection));

        modelSelection.value = String(selection);
        form[modelIdField] = selectedModel?.id ?? null;
        form[modelField] = selectedModel?.name ?? '';
    };

    const applyBrandSelection = async (selection) => {
        if (!selection) {
            reset();

            return;
        }

        if (selection === MANUAL_SELECTION) {
            setManualBrand();

            return;
        }

        const brand = brandOptions.value.find((item) => String(item.id) === String(selection));

        brandSelection.value = String(selection);
        form[brandIdField] = brand?.id ?? null;
        form[brandField] = brand?.name ?? '';
        resetModel();

        if (!brand) {
            return;
        }

        await loadModels(brand.id);

        if (form[modelField]) {
            const matchingModel = modelOptions.value.find((model) => normalize(model.name) === normalize(form[modelField]));

            if (matchingModel) {
                applyModelSelection(String(matchingModel.id));
            } else if (form[modelField] !== '') {
                setManualModel();
            }
        }
    };

    const hydrateFromCurrentValues = async () => {
        if (!form[brandField]) {
            reset();

            return;
        }

        const matchingBrand = brandOptions.value.find((brand) => normalize(brand.name) === normalize(form[brandField]));

        if (!matchingBrand) {
            setManualBrand();

            return;
        }

        await applyBrandSelection(String(matchingBrand.id));

        if (!form[modelField]) {
            return;
        }

        const matchingModel = modelOptions.value.find((model) => normalize(model.name) === normalize(form[modelField]));

        if (matchingModel) {
            applyModelSelection(String(matchingModel.id));
        } else {
            setManualModel();
        }
    };

    return {
        brandSelection,
        isManualBrand,
        isManualModel,
        loadingModels,
        modelOptions,
        modelSelection,
        applyBrandSelection,
        applyModelSelection,
        hydrateFromCurrentValues,
        reset,
        setManualBrand,
        setManualModel,
    };
}

export { MANUAL_SELECTION };
