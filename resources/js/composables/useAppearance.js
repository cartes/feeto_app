import { computed, onBeforeUnmount, ref } from "vue";

export const APPEARANCE_STORAGE_KEY = "appearance";
export const APPEARANCE_OPTIONS = [
    { id: "light", label: "Claro" },
    { id: "dark", label: "Oscuro" },
    { id: "auto", label: "Auto" },
];

const isBrowser = typeof window !== "undefined";

const readPreference = () => {
    if (!isBrowser) {
        return "light";
    }

    try {
        const stored = window.localStorage.getItem(APPEARANCE_STORAGE_KEY);

        return APPEARANCE_OPTIONS.some((option) => option.id === stored)
            ? stored
            : "light";
    } catch {
        return "light";
    }
};

const preference = ref(readPreference());
const systemPrefersDark = ref(
    isBrowser && window.matchMedia("(prefers-color-scheme: dark)").matches,
);
const isDark = computed(
    () =>
        preference.value === "dark" ||
        (preference.value === "auto" && systemPrefersDark.value),
);
let activeLayouts = 0;

const applyClass = () => {
    if (isBrowser) {
        document.documentElement.classList.toggle(
            "dark",
            activeLayouts > 0 && isDark.value,
        );
    }
};

if (isBrowser) {
    window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", (event) => {
            systemPrefersDark.value = event.matches;
            applyClass();
        });
}

/**
 * Preferencia de apariencia (claro, oscuro o auto según el navegador).
 * Solo se aplica mientras haya un layout del panel montado; las páginas públicas siguen en claro.
 */
export function useAppearance({ applyToLayout = false } = {}) {
    if (applyToLayout && isBrowser) {
        activeLayouts += 1;
        applyClass();

        onBeforeUnmount(() => {
            activeLayouts -= 1;
            applyClass();
        });
    }

    const setPreference = (value) => {
        preference.value = value;

        try {
            window.localStorage.setItem(APPEARANCE_STORAGE_KEY, value);
        } catch {
            //
        }

        applyClass();
    };

    const toggleAppearance = () => {
        setPreference(isDark.value ? "light" : "dark");
    };

    return { preference, isDark, setPreference, toggleAppearance };
}
