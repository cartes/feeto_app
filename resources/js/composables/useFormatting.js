/**
 * Composable de formato para locale chileno (es-CL / CLP).
 *
 * Centraliza Intl.NumberFormat y Date.toLocale* para evitar
 * duplicación en 22+ componentes Vue.
 */
export function useFormatting() {
    /**
     * Formatea un valor numérico como moneda CLP ($XX.XXX).
     *
     * @param {number|string} value
     * @returns {string}
     */
    const formatCurrency = (value) =>
        new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));

    /**
     * Formatea un número sin símbolo de moneda (XX.XXX).
     *
     * @param {number|string} value
     * @returns {string}
     */
    const formatNumber = (value) =>
        new Intl.NumberFormat('es-CL').format(Number(value || 0));

    /**
     * Convierte un monto CLP a UF con 2 decimales.
     *
     * @param {number|string} clpValue
     * @param {number|null}   ufValue  — valor actual de la UF en CLP
     * @returns {string|null}
     */
    const formatUf = (clpValue, ufValue) => {
        if (!ufValue || !clpValue) {
            return null;
        }

        const uf = Number(clpValue) / ufValue;

        return new Intl.NumberFormat('es-CL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(uf);
    };

    /**
     * Formatea una fecha como "dd mmm yyyy" (ej: 05 jun 2026).
     *
     * @param {string|Date|null} value
     * @param {string}           fallback — texto cuando value es nulo
     * @returns {string}
     */
    const formatDate = (value, fallback = 'Sin fecha') => {
        if (!value) {
            return fallback;
        }

        return new Date(value).toLocaleDateString('es-CL', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    /**
     * Formatea una fecha+hora como "dd mmm yyyy, HH:mm".
     *
     * @param {string|Date|null} value
     * @param {string}           fallback — texto cuando value es nulo
     * @returns {string}
     */
    const formatDateTime = (value, fallback = 'Sin fecha') => {
        if (!value) {
            return fallback;
        }

        return new Date(value).toLocaleString('es-CL', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    /**
     * Formatea año+mes como nombre completo del mes y año (ej: "junio 2026").
     *
     * @param {string|number} year
     * @param {string|number} month — 1-indexed
     * @returns {string}
     */
    const formatMonthYear = (year, month) =>
        new Date(+year, +month - 1).toLocaleString('es-CL', {
            month: 'long',
            year: 'numeric',
        });

    /**
     * Formatea una fecha+hora en estilo corto (dateStyle: 'short', timeStyle: 'short').
     *
     * @param {string|Date|null} value
     * @returns {string}
     */
    const formatDateTimeShort = (value) => {
        if (!value) {
            return 'Sin fecha';
        }

        return new Date(value).toLocaleString('es-CL', {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    };

    return {
        formatCurrency,
        formatNumber,
        formatUf,
        formatDate,
        formatDateTime,
        formatMonthYear,
        formatDateTimeShort,
    };
}
