/**
 * Composable de debounce.
 *
 * Reemplaza las 5+ copias idénticas de la función debounce
 * que viven en WorkOrders/Index, Reception/Create, Quotes/Index,
 * Quotes/Create y Clients/Index.
 */
export function useDebounce() {
    /**
     * Crea una versión "debounced" de una función.
     *
     * @param {Function} fn    — función a ejecutar
     * @param {number}   delay — milisegundos de espera (por defecto 300)
     * @returns {Function}
     */
    const debounce = (fn, delay = 300) => {
        let timeoutId;

        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn(...args), delay);
        };
    };

    return { debounce };
}
