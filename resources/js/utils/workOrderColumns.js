const columnConfig = {
    recepcion: { title: 'Recepción', color: 'bg-orange-100/50' },
    taller: { title: 'En taller', color: 'bg-blue-100/50' },
    aviso_cliente: { title: 'Avisar al cliente', color: 'bg-yellow-100/50' },
    diagnostico: { title: 'En diagnóstico', color: 'bg-blue-100/50' },
    esperando_repuestos: { title: 'Esperando repuestos', color: 'bg-yellow-100/50' },
    control_calidad: { title: 'Control de calidad', color: 'bg-cyan-100/50' },
    listo: { title: 'Listo para entrega', color: 'bg-green-100/50' },
};

export function workOrderColumns(statuses) {
    return statuses.map(id => ({
        id,
        ...(columnConfig[id] ?? { title: id.replaceAll('_', ' '), color: 'bg-gray-100/50' }),
    }));
}
