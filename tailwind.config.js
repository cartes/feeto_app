import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

const brandOrange = {
    50: "#FFF4EB",
    100: "#FFE7D1",
    200: "#FFC999",
    300: "#FFAB61",
    400: "#FF8D29",
    500: "#FF7A00",
    600: "#CC6200",
    700: "#994900",
    800: "#663100",
    900: "#331800",
    950: "#1A0C00",
    DEFAULT: "#FF7A00",
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                "--ink": "#fff",
                "app-bg": "#0B0C10", // Negro profundo / Carbón
                "panel-bg": "#1F2833", // Gris metálico oscuro
                "tech-accent": "#66FCF1", // Un toque cian eléctrico para detalles
                "tech-red": "#C3073F", // Rojo profundo industrial
                "tech-orange": brandOrange.DEFAULT,
                "brand-orange": brandOrange,
                orange: brandOrange,
                amber: brandOrange,
                "text-main": "#F5F5F5", // Off-white premium
                "text-muted": "#C5C6C7", // Gris plata suave
            },
        },
    },

    plugins: [forms],
};
