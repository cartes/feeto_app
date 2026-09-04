<?php

declare(strict_types=1);

namespace App\Support;

class SupportFaqCatalog
{
    /**
     * FAQ por sección/vista del taller. Las keys coinciden con las de
     * TallerLayout::SECTION_TOUR_ROUTES (resources/js/Layouts/TallerLayout.vue)
     * para mantener un único mapeo vista → sección en todo el sistema.
     *
     * @return array<string, array<int, array{id: string, question: string, answer: string, selector: string|null}>>
     */
    public static function all(): array
    {
        return [
            'dashboard' => [
                [
                    'id' => 'dashboard-summary',
                    'question' => '¿Dónde veo el resumen del día?',
                    'answer' => 'En la parte superior del dashboard hay un bloque con las citas del día, los movimientos del tablero y las facturas atrasadas del taller, todo de un vistazo.',
                    'selector' => '[data-tour="dashboard-summary"]',
                ],
                [
                    'id' => 'dashboard-quicklinks',
                    'question' => '¿Cómo llego rápido a recepción, órdenes o clientes?',
                    'answer' => 'Usa la sección "Accesos rápidos" del dashboard: son tarjetas que te llevan directo a recepción, órdenes, inventario, servicios, clientes y reportes sin pasar por el menú.',
                    'selector' => '[data-tour="dashboard-quicklinks"]',
                ],
                [
                    'id' => 'dashboard-agenda',
                    'question' => '¿Cómo veo o agendo una cita?',
                    'answer' => 'En la sección "Agenda del taller" del dashboard puedes revisar el calendario y hacer clic en "Ver agenda" para ir al módulo completo de citas.',
                    'selector' => '[data-tour="dashboard-agenda"]',
                ],
            ],

            'reception' => [
                [
                    'id' => 'reception-new-manual-ot',
                    'question' => '¿Cómo creo una orden de trabajo (OT) manualmente?',
                    'answer' => 'Presiona el botón "Nueva Recepción" (o "Ingresar manualmente" si usas el escáner con IA). Completa la patente y los datos del vehículo y cliente en el formulario que se abre, y confirma para crear la OT.',
                    'selector' => '[data-tour="reception-new-entry"]',
                ],
                [
                    'id' => 'reception-ai-scanner',
                    'question' => '¿Cómo funciona el escáner de patente con IA?',
                    'answer' => 'Si tu plan incluye recepción con IA, apunta la cámara a la patente del vehículo: el sistema la reconoce automáticamente y busca el vehículo y cliente en tu base de datos. Puedes confirmar los datos o corregirlos antes de crear la OT.',
                    'selector' => '[data-tour="reception-new-entry"]',
                ],
            ],

            'work-orders' => [
                [
                    'id' => 'work-orders-create-manual',
                    'question' => '¿Cómo creo una nueva orden de trabajo (OT) manualmente?',
                    'answer' => 'Presiona el botón "Nueva OT" en la parte superior del tablero. Se abrirá el formulario para ingresar la patente, datos del vehículo, cliente y checklist de recepción.',
                    'selector' => '[data-support="work-orders-create-manual"]',
                ],
                [
                    'id' => 'work-orders-status-change',
                    'question' => '¿Cómo cambio el estado de una orden de trabajo?',
                    'answer' => 'En la vista Tablero, arrastra la tarjeta de la orden desde su columna actual hasta la columna del nuevo estado (drag-and-drop). El cambio se guarda automáticamente al soltarla.',
                    'selector' => '[data-tour="work-orders-view-switch"]',
                ],
                [
                    'id' => 'work-orders-search',
                    'question' => '¿Cómo busco una orden por patente o cliente?',
                    'answer' => 'Usa el campo de búsqueda sobre el tablero o listado: escribe la patente, el número de OT, el cliente o la marca y los resultados se filtran al instante.',
                    'selector' => '[data-tour="work-orders-search"]',
                ],
                [
                    'id' => 'work-orders-add-item',
                    'question' => '¿Cómo agrego un repuesto o servicio a una orden?',
                    'answer' => 'Entra al detalle de la orden y usa el formulario "Agregar" en la sección de cotización para elegir Repuesto, Servicio o un ítem manual. Luego presiona "Agregar a Cotización".',
                    'selector' => '[data-support="work-order-add-item"]',
                ],
            ],

            'quotes' => [
                [
                    'id' => 'quotes-new',
                    'question' => '¿Cómo creo una cotización sin una cita agendada?',
                    'answer' => 'Presiona "Nueva cotización" en el listado, busca el vehículo por patente, elige el cliente y pulsa "Crear cotización" para generarla sin necesidad de una orden previa.',
                    'selector' => '[data-tour="quotes-new"]',
                ],
                [
                    'id' => 'quotes-send',
                    'question' => '¿Cómo envío una cotización al cliente?',
                    'answer' => 'Dentro del detalle de la cotización, elige el canal (WhatsApp, email o ambos) y presiona el botón de enviar.',
                    'selector' => '[data-support="quotes-send"]',
                ],
                [
                    'id' => 'quotes-approve',
                    'question' => '¿Cómo se convierte una cotización en orden de trabajo?',
                    'answer' => 'Al aprobar la cotización con el botón "Aprobar manualmente" (o cuando el cliente la aprueba desde su link), el sistema genera automáticamente la Orden de Trabajo y te lleva a su detalle.',
                    'selector' => '[data-support="quotes-approve"]',
                ],
            ],

            'inventory' => [
                [
                    'id' => 'inventory-add',
                    'question' => '¿Cómo agrego un repuesto nuevo al inventario?',
                    'answer' => 'Presiona el botón "Agregar" arriba a la derecha del listado, completa nombre, SKU, precios, stock actual y stock mínimo, y guarda.',
                    'selector' => '[data-tour="inventory-add"]',
                ],
                [
                    'id' => 'inventory-movements',
                    'question' => '¿Cómo veo el historial de entradas y salidas de un repuesto?',
                    'answer' => 'Pasa el cursor sobre la fila del repuesto y haz clic en el ícono "Historial de movimientos" para ver entradas, salidas y ajustes de stock.',
                    'selector' => '[data-support="inventory-movements"]',
                ],
                [
                    'id' => 'inventory-low-stock',
                    'question' => '¿Cómo sé si un repuesto tiene stock bajo?',
                    'answer' => 'En la tabla, los productos con poco stock muestran una etiqueta "Stock Crítico" (rojo) o "Bajo" (ámbar). También puedes filtrar por "Estado Stock" para ver solo esos productos.',
                    'selector' => '[data-support="inventory-stock-filter"]',
                ],
            ],

            'services' => [
                [
                    'id' => 'services-add',
                    'question' => '¿Cómo creo un nuevo servicio para cotizar?',
                    'answer' => 'Presiona el botón naranja "Agregar" y completa nombre, código, precio de venta y duración estimada en el modal.',
                    'selector' => '[data-tour="services-add"]',
                ],
                [
                    'id' => 'services-import',
                    'question' => '¿Cómo importo varios servicios a la vez?',
                    'answer' => 'Usa el botón "Importar" para subir un Excel con tus servicios. Puedes descargar la "Plantilla" primero para llenarla con el formato correcto.',
                    'selector' => '[data-support="services-import"]',
                ],
            ],

            'clients' => [
                [
                    'id' => 'clients-add',
                    'question' => '¿Cómo agrego un cliente nuevo?',
                    'answer' => 'Haz clic en el botón "Nuevo Cliente" y completa nombre, RUT, teléfono y correo en el modal.',
                    'selector' => '[data-tour="clients-add"]',
                ],
                [
                    'id' => 'clients-history',
                    'question' => '¿Cómo veo el historial de vehículos y visitas de un cliente?',
                    'answer' => 'Entra a la ficha del cliente desde la tabla y usa las pestañas "Timeline CRM" y "Vehículos" para ver su historial y las unidades registradas a su nombre.',
                    'selector' => '[data-support="client-tabs"]',
                ],
                [
                    'id' => 'clients-search',
                    'question' => '¿Cómo busco un cliente?',
                    'answer' => 'Escribe el nombre o RUT en el buscador ubicado junto al botón "Nuevo Cliente"; los resultados se filtran automáticamente.',
                    'selector' => '[data-support="clients-search"]',
                ],
            ],

            'reports' => [
                [
                    'id' => 'reports-grid',
                    'question' => '¿Qué reportes tiene el taller?',
                    'answer' => 'En el Centro de Reportes hay tarjetas para Ventas, Supervisión, Inventario, Clientes y Cobranza. Haz clic en "Abrir" en la tarjeta que necesites.',
                    'selector' => '[data-tour="reports-grid"]',
                ],
                [
                    'id' => 'reports-export',
                    'question' => '¿Cómo exporto un reporte a PDF o Excel?',
                    'answer' => 'Dentro de cualquier reporte, usa los botones "Descargar PDF" o "Descargar Excel" junto a los filtros; se descarga con los filtros que tengas aplicados en ese momento.',
                    'selector' => '[data-support="report-export-actions"]',
                ],
            ],

            'settings' => [
                [
                    'id' => 'settings-users-add',
                    'question' => '¿Cómo invito o agrego un nuevo usuario al taller?',
                    'answer' => 'En Configuración, pestaña "Usuarios", haz clic en "Nuevo Usuario" y completa nombre, correo, contraseña y rol. El usuario queda con acceso inmediato.',
                    'selector' => '[data-support="users-add"]',
                ],
                [
                    'id' => 'settings-branding',
                    'question' => '¿Cómo cambio el logo o color de marca del taller?',
                    'answer' => 'Ve a Configuración → pestaña "Apariencia": sube tu logo con "Subir Logo" y ajusta el color con el selector de color.',
                    'selector' => '[data-support="branding-logo-upload"]',
                ],
                [
                    'id' => 'settings-roles',
                    'question' => '¿Cómo administro roles y permisos?',
                    'answer' => 'En Configuración, entra a la pestaña de Roles (visible si tu plan incluye roles personalizados) y presiona "Nuevo Rol" para definir qué puede hacer cada perfil.',
                    'selector' => '[data-support="roles-add"]',
                ],
                [
                    'id' => 'settings-branches',
                    'question' => '¿Cómo agrego una sucursal?',
                    'answer' => 'En Configuración → pestaña "Sucursales", presiona "Nueva Sucursal" y completa nombre, dirección y correo de contacto.',
                    'selector' => '[data-support="branch-add"]',
                ],
            ],

            'subscription-plans' => [
                [
                    'id' => 'subscription-plans-toggle',
                    'question' => '¿Cómo cambio entre pago mensual y anual?',
                    'answer' => 'Usa el interruptor sobre la grilla de planes para alternar entre pago mensual o anual y ver el ahorro del plan anual.',
                    'selector' => '[data-tour="subscription-plans-toggle"]',
                ],
                [
                    'id' => 'subscription-plans-compare',
                    'question' => '¿Cómo comparo los planes disponibles?',
                    'answer' => 'En la grilla de planes puedes comparar límites de usuarios y características incluidas en cada uno, y elegir el que mejor se ajuste a tu taller.',
                    'selector' => '[data-tour="subscription-plans-grid"]',
                ],
            ],

            'subscription-billing' => [
                [
                    'id' => 'billing-summary',
                    'question' => '¿Dónde veo cuánto he pagado y cuándo se renueva mi plan?',
                    'answer' => 'Arriba de la sección de facturación hay un resumen con el total pagado y la fecha de renovación de tu suscripción.',
                    'selector' => '[data-tour="billing-summary"]',
                ],
                [
                    'id' => 'billing-transactions',
                    'question' => '¿Cómo reviso el historial de mis pagos?',
                    'answer' => 'Más abajo encontrarás el historial completo de transacciones con su estado (pagado, pendiente o fallido).',
                    'selector' => '[data-tour="billing-transactions"]',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, question: string, answer: string, selector: string|null}>
     */
    public static function forSection(?string $section): array
    {
        if ($section === null) {
            return [];
        }

        return static::all()[$section] ?? [];
    }

    /**
     * @return array{id: string, question: string, answer: string, selector: string|null}|null
     */
    public static function find(?string $section, string $faqId): ?array
    {
        foreach (static::forSection($section) as $faq) {
            if ($faq['id'] === $faqId) {
                return $faq;
            }
        }

        return null;
    }
}
