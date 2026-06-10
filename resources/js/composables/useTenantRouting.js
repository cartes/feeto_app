import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable de enrutamiento multi-tenant.
 *
 * Provee el computed `tenantRouteParams` y un helper `tenantRoute`
 * que agrega automáticamente el slug del tenant activo a las rutas.
 * Reemplaza la línea idéntica que se repite en 28+ archivos.
 */
export function useTenantRouting() {
    const page = usePage();

    /**
     * Parámetros de ruta con el slug del tenant activo.
     * Retorna { tenantBySlug: '...' } o {} si no hay tenant.
     */
    const tenantRouteParams = computed(() =>
        page.props.tenant?.slug
            ? { tenantBySlug: page.props.tenant.slug }
            : {},
    );

    /**
     * Genera una URL de ruta fusionando automáticamente el slug del tenant.
     *
     * @param {string} name      — nombre de la ruta (Ziggy)
     * @param {Object} params    — parámetros adicionales
     * @returns {string}
     */
    const tenantRoute = (name, params = {}) =>
        route(name, { ...tenantRouteParams.value, ...params });

    return {
        page,
        tenantRouteParams,
        tenantRoute,
    };
}
