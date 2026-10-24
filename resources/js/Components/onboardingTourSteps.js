/**
 * Contenido de los tours de bienvenida. Un paso sin `selectors` se muestra centrado
 * (bienvenida / cierre); el resto se omite si su elemento no está visible en pantalla
 * (por plan o permisos del usuario).
 */
export const TOUR_VARIANTS = {
    tenant: {
        desktop: [
            {
                title: '¡Bienvenido a TallerFlow!',
                description: 'En menos de un minuto te mostramos cómo se mueve un trabajo en tu taller: desde que llega el vehículo hasta que se entrega. Puedes omitir este recorrido cuando quieras y no volverá a aparecer.',
            },
            {
                selectors: ['[data-tour="tenant-navigation"]'],
                title: 'Menú principal',
                description: 'Aquí está todo el taller. El flujo normal es: registras el ingreso en Recepción, el trabajo avanza en Órdenes, cotizas con Cotizaciones y Servicios, y controlas repuestos en Inventario. Clientes y Reportes te dan el historial y los números.',
            },
            {
                selectors: ['[data-tour="tenant-search"]'],
                title: 'Búsqueda global',
                description: 'Escribe una patente, un número de orden o el nombre de un cliente y llega directo a su ficha, sin recorrer los menús.',
            },
            {
                selectors: ['[data-tour="tenant-notifications"]'],
                title: 'Notificaciones',
                description: 'La campana te avisa de nuevas solicitudes de hora, cambios en las órdenes y recordatorios. Si tiene un número, hay algo por revisar.',
            },
            {
                selectors: ['[data-tour="tenant-appearance"]'],
                title: 'Modo claro u oscuro',
                description: 'Cambia la apariencia de todo el panel: Claro, Oscuro o Auto, que sigue la configuración de tu navegador. Tu elección se recuerda.',
            },
            {
                selectors: ['[data-tour="tenant-settings"]'],
                title: 'Configuración del taller',
                description: 'Administra usuarios, sucursales y roles, y ajusta los datos de tu taller. Solo lo ven las personas con permiso de administración.',
            },
            {
                title: 'Listo para empezar',
                description: 'La primera vez que entres a cada sección verás una guía corta de lo más importante. Y si te pierdes, el botón de ayuda en la esquina inferior tiene tutoriales y respuestas.',
            },
        ],
        mobile: [
            {
                title: '¡Bienvenido a TallerFlow!',
                description: 'En menos de un minuto te mostramos cómo moverte por tu taller desde el celular. Puedes omitirlo cuando quieras y no volverá a aparecer.',
            },
            {
                selectors: ['[data-tour="tenant-mobile-search"]'],
                title: 'Búsqueda',
                description: 'Busca una patente, una orden o un cliente desde cualquier pantalla y llega directo a su ficha.',
            },
            {
                selectors: ['[data-tour="tenant-notifications"]'],
                title: 'Notificaciones',
                description: 'La campana te avisa de nuevas solicitudes de hora, cambios en las órdenes y recordatorios.',
            },
            {
                selectors: ['[data-tour="tenant-mobile-navigation"]'],
                title: 'Accesos rápidos',
                description: 'Esta barra siempre está a mano para cambiar de sección: Inicio, Recepción, Órdenes y más.',
            },
            {
                selectors: ['[data-tour="tenant-mobile-more"]'],
                title: 'Más secciones',
                description: 'Toca “Más” para ver todas las secciones del taller, tu página web y la configuración.',
            },
            {
                title: 'Listo para empezar',
                description: 'La primera vez que entres a cada sección verás una guía corta. Si necesitas ayuda, usa el botón de ayuda para ver tutoriales y respuestas.',
            },
        ],
    },
    dashboard: {
        common: [
            {
                selectors: ['[data-tour="dashboard-tabs"]'],
                title: 'Pizarrón y Resumen',
                description: 'Al entrar ves el Pizarrón con lo que importa hoy. La pestaña Resumen guarda lo demás: accesos rápidos, citas por revisar, agenda mensual, cobranza y la guía de configuración.',
            },
            {
                selectors: ['[data-tour="dashboard-board-workshop"]'],
                title: 'Vehículos en el taller',
                description: 'Cuántos vehículos hay en el taller y en qué etapa está cada uno. Toca una tarjeta para abrir su orden de trabajo.',
            },
            {
                selectors: ['[data-tour="dashboard-board-agenda"]'],
                title: 'Agenda de hoy',
                description: 'Las citas programadas para hoy, ordenadas por hora, para saber qué vehículos llegan y cuándo.',
            },
            {
                selectors: ['[data-tour="dashboard-board-stock"]'],
                title: 'Alertas de inventario',
                description: 'Los productos que llegaron a su stock mínimo o se agotaron, para reponerlos antes de que falten en una orden.',
            },
            {
                selectors: ['[data-tour="dashboard-summary"]'],
                title: 'Resumen del día',
                description: 'Lo primero que conviene mirar cada mañana: citas de hoy, movimiento del tablero de órdenes y facturas atrasadas.',
            },
            {
                selectors: ['[data-tour="dashboard-quicklinks"]'],
                title: 'Accesos rápidos',
                description: 'Atajos a las tareas más frecuentes: recibir un vehículo, revisar órdenes, inventario, servicios, clientes y reportes.',
            },
            {
                selectors: ['[data-tour="dashboard-agenda"]'],
                title: 'Agenda del taller',
                description: 'Revisa y agenda las citas de tus clientes para no perder ninguna hora.',
            },
        ],
    },
    reception: {
        common: [
            {
                selectors: ['[data-tour="reception-new-entry"]'],
                title: 'Nueva recepción',
                description: 'Todo trabajo parte aquí: ingresa la patente del vehículo y registra su estado al llegar. Al terminar se crea la orden de trabajo automáticamente.',
            },
        ],
    },
    'work-orders': {
        common: [
            {
                selectors: ['[data-tour="work-orders-create-manual"]'],
                title: 'Nueva OT',
                description: 'Crea una orden de trabajo directamente cuando el vehículo ya está registrado y no necesitas pasar por Recepción.',
            },
            {
                selectors: ['[data-tour="work-orders-view-switch"]'],
                title: 'Tablero o listado',
                description: 'El tablero muestra cada orden como una tarjeta que avanza por etapas; el listado es mejor para filtrar y revisar muchas órdenes. Elige la que te acomode.',
            },
            {
                selectors: ['[data-tour="work-orders-search"]'],
                title: 'Búsqueda rápida',
                description: 'Encuentra una orden por patente, número de OT o nombre del cliente.',
            },
        ],
    },
    quotes: {
        common: [
            {
                selectors: ['[data-tour="quotes-new"]'],
                title: 'Nueva cotización',
                description: 'Crea una cotización para un cliente sin necesidad de una cita ni de una orden de trabajo.',
            },
            {
                selectors: ['[data-tour="quotes-search"]'],
                title: 'Búsqueda de cotizaciones',
                description: 'Encuentra cotizaciones anteriores por cliente, RUT o patente.',
            },
        ],
    },
    inventory: {
        common: [
            {
                selectors: ['[data-tour="inventory-add"]'],
                title: 'Agregar repuesto',
                description: 'Registra un repuesto o insumo con su stock y precio para usarlo en cotizaciones y órdenes.',
            },
            {
                selectors: ['[data-tour="inventory-search"]'],
                title: 'Búsqueda de repuestos',
                description: 'Busca por nombre o SKU para revisar stock y precios al instante.',
            },
        ],
    },
    services: {
        common: [
            {
                selectors: ['[data-tour="services-add"]'],
                title: 'Nuevo servicio',
                description: 'Crea los servicios que ofreces (mano de obra, revisiones, mantenciones) para agregarlos rápido a tus cotizaciones y órdenes.',
            },
        ],
    },
    clients: {
        common: [
            {
                selectors: ['[data-tour="clients-add"]'],
                title: 'Nuevo cliente',
                description: 'Registra a un cliente con sus datos de contacto y sus vehículos.',
            },
            {
                selectors: ['[data-tour="clients-table"]'],
                title: 'Historial y CRM',
                description: 'Abre la ficha de un cliente para ver su historial de visitas y las señales CRM que te ayudan a hacer seguimiento.',
            },
        ],
    },
    reports: {
        common: [
            {
                selectors: ['[data-tour="reports-summary"]'],
                title: 'Resumen de actividad',
                description: 'Un vistazo rápido a la actividad comercial y operativa de tu taller.',
            },
            {
                selectors: ['[data-tour="reports-grid"]'],
                title: 'Reportes disponibles',
                description: 'Elige un reporte para profundizar en ventas, stock, clientes o cobranza.',
            },
        ],
    },
    'subscription-plans': {
        common: [
            {
                selectors: ['[data-tour="subscription-plans-toggle"]'],
                title: 'Mensual o anual',
                description: 'Cambia entre pago mensual o anual para ver cuánto ahorras.',
            },
            {
                selectors: ['[data-tour="subscription-plans-grid"]'],
                title: 'Compara planes',
                description: 'Compara lo que incluye cada plan y elige el que mejor se ajuste a tu taller.',
            },
        ],
    },
    'subscription-billing': {
        common: [
            {
                selectors: ['[data-tour="billing-summary"]'],
                title: 'Resumen de pagos',
                description: 'Aquí ves cuánto has pagado en total y cuándo se renueva tu plan.',
            },
            {
                selectors: ['[data-tour="billing-transactions"]'],
                title: 'Historial de transacciones',
                description: 'Revisa todos tus pagos y el estado de cada uno.',
            },
        ],
    },
    settings: {
        common: [
            {
                selectors: ['[data-tour="settings-tabs"]'],
                title: 'Secciones de configuración',
                description: 'Cambia entre usuarios, sucursales, roles y ajustes comerciales del taller.',
            },
        ],
    },
};
