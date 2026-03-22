import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

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
                'app-bg': '#0B0C10',     // Negro profundo / Carbón
                'panel-bg': '#1F2833',   // Gris metálico oscuro
                'tech-accent': '#66FCF1', // Un toque cian eléctrico para detalles
                'tech-red': '#C3073F',    // Rojo profundo industrial
                'tech-orange': '#F9A826', // Naranja/Ambar vibrante de la referencia
                'text-main': '#F5F5F5',   // Off-white premium
                'text-muted': '#C5C6C7',  // Gris plata suave
            },
        },
    },

    plugins: [forms],
};
