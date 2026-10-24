import plugin from "tailwindcss/plugin";

const neutrals = ["gray", "slate"];
const accents = ["red", "orange", "amber", "yellow", "lime", "green", "emerald", "teal", "cyan", "sky", "blue", "indigo", "violet", "purple", "fuchsia", "pink", "rose"];

const neutralBackgrounds = { 50: "#19243a", 100: "#202c44", 200: "#2b3a53", 300: "#3a4a64", 400: "#4a5a75", 500: "#64748b", 600: "#566680", 700: "#475569", 800: "#3d4d68", 900: "#334155", 950: "#1e293b" };
const neutralTexts = { 50: "#e2e8f0", 100: "#cbd5e1", 200: "#a3b1c6", 300: "#8696ac", 400: "#8696ac", 500: "#94a3b8", 600: "#b6c2d3", 700: "#cbd5e1", 800: "#e2e8f0", 900: "#f1f5f9", 950: "#f8fafc" };
const neutralBorders = { 50: "#1c2740", 100: "#222f48", 200: "#2b3a53", 300: "#3a4a64", 400: "#51627d", 500: "#64748b", 600: "#7c8ca3", 700: "#475569", 800: "#334155", 900: "#94a3b8", 950: "#94a3b8" };

const surface = "#131c2e";
const surfaceBorder = "#2b3a53";

/**
 * Modo oscuro del panel: reasigna las utilidades de color neutras y de acento
 * (bg-white, text-gray-900, bg-red-50...) cuando existe un ancestro `.dark`.
 * Solo se genera el CSS de las clases que realmente se usan, incluidas sus
 * variantes (hover:, focus:, group-hover:) y modificadores de opacidad.
 */
export default plugin(function ({ matchUtilities, addBase, theme }) {
    const toRgb = (hex) => {
        const value = hex.replace("#", "");
        const full = value.length === 3 ? value.split("").map((c) => c + c).join("") : value;

        return [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16)).join(" ");
    };

    const withAlpha = (hex, modifier) => `rgb(${toRgb(hex)} / ${modifier ?? 1})`;

    const entries = (names, scale, transform = (hex) => hex) => Object.fromEntries(
        names.flatMap((name) => Object.entries(scale).map(([shade, hex]) => [`${name}-${shade}`, transform(hex)])),
    );

    const accentColor = (name, shade) => {
        const value = theme(`colors.${name}.${shade}`);

        return typeof value === "string" ? value : null;
    };

    const accentEntries = (shades) => Object.fromEntries(
        accents.flatMap((name) => Object.entries(shades).flatMap(([shade, source]) => {
            const [sourceShade, alpha] = Array.isArray(source) ? source : [source, null];
            const hex = accentColor(name, sourceShade);

            return hex ? [[`${name}-${shade}`, alpha === null ? hex : { hex, alpha }]] : [];
        })),
    );

    const resolve = (value, modifier) => {
        if (typeof value === "string") {
            return withAlpha(value, modifier);
        }

        return withAlpha(value.hex, modifier ?? value.alpha);
    };

    const backgrounds = {
        white: surface,
        ...entries(neutrals, neutralBackgrounds),
        ...accentEntries({ 50: [500, 0.12], 100: [500, 0.18], 200: [500, 0.28] }),
    };

    const texts = {
        ...entries(neutrals, neutralTexts),
        ...accentEntries({ 600: 400, 700: 300, 800: 200, 900: 100 }),
    };

    const borders = {
        white: surfaceBorder,
        ...entries(neutrals, neutralBorders),
        ...accentEntries({ 50: [500, 0.2], 100: [500, 0.2], 200: [500, 0.3], 300: [500, 0.4] }),
    };

    const options = (map) => ({
        values: Object.fromEntries(Object.keys(map).map((key) => [key, key])),
        type: "color",
        modifiers: theme("opacity"),
    });
    const dark = ":is(.dark) &";
    const overlays = new Set(["gray-900", "gray-950", "slate-900", "slate-950"]);

    const register = (name, map, build) => matchUtilities({
        [name]: (key, { modifier }) => (map[key] === undefined ? null : build(key, resolve(map[key], modifier), modifier)),
    }, options(map));

    register("bg", backgrounds, (key, color, modifier) => ({
        [dark]: { backgroundColor: modifier && overlays.has(key) ? withAlpha("#020617", modifier) : color },
    }));
    register("text", texts, (key, color) => ({ [dark]: { color } }));
    register("border", borders, (key, color) => ({ [dark]: { borderColor: color } }));
    register("ring", borders, (key, color) => ({ [dark]: { "--tw-ring-color": color } }));
    register("divide", borders, (key, color) => ({ ":is(.dark) &> :not([hidden]) ~ :not([hidden])": { borderColor: color } }));
    register("placeholder", texts, (key, color) => ({ ":is(.dark) &::placeholder": { color } }));

    addBase({
        "html.dark": { colorScheme: "dark" },
        ".dark input:not([type='checkbox']):not([type='radio']):not([type='range']):not([type='color']), .dark select, .dark textarea": {
            backgroundColor: "#0f1829",
            borderColor: "#3a4a64",
            color: "#e2e8f0",
        },
        ".dark input::placeholder, .dark textarea::placeholder": { color: "#64748b" },
        ".dark option": { backgroundColor: "#0f1829", color: "#e2e8f0" },
    });
});
