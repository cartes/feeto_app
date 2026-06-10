/**
 * Composable de configuración de estados.
 *
 * Centraliza los mapas de estado (label + clases CSS) que se
 * repiten en WorkOrders/Index, WorkOrders/Show, Quotes/Show
 * y Components/AppointmentList.
 *
 * También re-exporta las constantes de payment.js para ofrecer
 * un punto de acceso único.
 */
import { STATUS_CONFIG, PAYMENT_TYPE_LABEL } from '@/constants/payment';

/** Configuración de estados de cotización */
const QUOTE_STATUS_CONFIG = {
    draft: { label: 'Borrador', classes: 'bg-slate-100 text-slate-600 border-slate-200' },
    pending_customer: { label: 'Pendiente Cliente', classes: 'bg-amber-50 text-amber-600 border-amber-200' },
    accepted: { label: 'Aceptada', classes: 'bg-emerald-50 text-emerald-600 border-emerald-200' },
    rejected: { label: 'Rechazada', classes: 'bg-rose-50 text-rose-600 border-rose-200' },
};

/** Etiquetas de tipo de ítem de cotización */
const QUOTE_ITEM_TYPE_LABELS = {
    product: 'Repuesto',
    service: 'Servicio',
    manual: 'Manual',
};

/** Configuración de estados de orden de trabajo (Kanban) */
const WORK_ORDER_STATUS_CONFIG = {
    recepcion: { label: 'Recepción', classes: 'bg-blue-50 text-blue-600 border-blue-100' },
    diagnostico: { label: 'En Diagnóstico', classes: 'bg-yellow-50 text-yellow-600 border-yellow-100' },
    esperando_repuestos: { label: 'Esperando Repuestos', classes: 'bg-red-50 text-red-500 border-red-100' },
    control_calidad: { label: 'Control de Calidad', classes: 'bg-cyan-50 text-cyan-600 border-cyan-100' },
    listo: { label: 'Listo', classes: 'bg-green-50 text-green-600 border-green-100' },
};

/** Configuración de estados de cita (AppointmentList) */
const APPOINTMENT_STATUS_CONFIG = {
    pending: { label: 'Pendiente', classes: 'bg-amber-100 text-amber-800' },
    arrived: { label: 'Llegó', classes: 'bg-emerald-100 text-emerald-800' },
    cancelled: { label: 'Cancelado', classes: 'bg-red-100 text-red-800' },
};

export function useStatusConfig() {
    /**
     * Resuelve la configuración de un estado de cotización.
     *
     * @param {string|null} status
     * @returns {{ label: string, classes: string }}
     */
    const resolveQuoteStatus = (status) =>
        QUOTE_STATUS_CONFIG[status] ?? {
            label: status || 'Sin cotización',
            classes: 'border-slate-200 bg-slate-50 text-slate-500',
        };

    /**
     * Resuelve la configuración de un estado de orden de trabajo.
     *
     * @param {string|null} status
     * @returns {{ label: string, classes: string }}
     */
    const resolveWorkOrderStatus = (status) =>
        WORK_ORDER_STATUS_CONFIG[status] ?? {
            label: status || 'Desconocido',
            classes: 'bg-gray-50 text-gray-600 border-gray-200',
        };

    /**
     * Resuelve la configuración de un estado de cita.
     *
     * @param {string|null} status
     * @returns {{ label: string, classes: string }}
     */
    const resolveAppointmentStatus = (status) =>
        APPOINTMENT_STATUS_CONFIG[status] ?? {
            label: status || 'Desconocido',
            classes: 'bg-gray-100 text-gray-700',
        };

    return {
        QUOTE_STATUS_CONFIG,
        QUOTE_ITEM_TYPE_LABELS,
        WORK_ORDER_STATUS_CONFIG,
        APPOINTMENT_STATUS_CONFIG,
        PAYMENT_STATUS_CONFIG: STATUS_CONFIG,
        PAYMENT_TYPE_LABEL,
        resolveQuoteStatus,
        resolveWorkOrderStatus,
        resolveAppointmentStatus,
    };
}
