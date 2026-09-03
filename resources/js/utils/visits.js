export const PERIOD_OPTIONS = [
    { key: '7d', label: '7 días' },
    { key: '30d', label: '30 días' },
    { key: '90d', label: '90 días' },
    { key: '12m', label: '12 meses' },
];

export const SCOPE_META = {
    all: { label: 'Todo', description: 'Sitio público + landings de talleres + uso de la app', color: '#0f172a' },
    site: { label: 'Sitio web', description: 'Home, precios, blog, prueba gratis y login', color: '#f97316' },
    tenant: { label: 'Talleres', description: 'Landings públicas, checkout y cotizaciones de cada taller', color: '#6366f1' },
    app: { label: 'App', description: 'Usuarios autenticados usando la plataforma', color: '#10b981' },
};

export const DEVICE_LABELS = {
    desktop: 'Escritorio',
    mobile: 'Móvil',
    tablet: 'Tablet',
};

export const WEEKDAY_LABELS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

export const formatNumber = (value) => {
    if (value === null || value === undefined) return '—';
    return new Intl.NumberFormat('es-CL').format(value);
};

const parseDate = (dateStr) => {
    if (typeof dateStr !== 'string') return null;
    const parts = dateStr.split('-');
    if (parts.length !== 3) return null;
    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
};

export const formatDateLabel = (dateStr, options = { weekday: 'short', day: 'numeric', month: 'short' }) => {
    const date = parseDate(dateStr);
    if (!date) return dateStr ?? '';
    return date.toLocaleDateString('es-CL', options);
};

export const formatShortDate = (dateStr) => formatDateLabel(dateStr, { day: 'numeric', month: 'short' });

export const formatRange = (range) => {
    if (!range) return '';
    return `${formatShortDate(range.from)} – ${formatShortDate(range.to)}`;
};

/**
 * Describe la variación respecto al período anterior.
 * `change` viene en porcentaje (puede ser null cuando antes no había datos).
 */
export const changeMeta = (change, previous) => {
    if (change === null || change === undefined) {
        if (previous === 0) return { label: 'Nuevo', tone: 'neutral' };
        return { label: '—', tone: 'neutral' };
    }
    if (change === 0) return { label: '0%', tone: 'neutral' };
    const sign = change > 0 ? '+' : '';
    return {
        label: `${sign}${change.toFixed(change % 1 === 0 ? 0 : 1)}%`,
        tone: change > 0 ? 'up' : 'down',
    };
};

export const toneClasses = {
    up: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    down: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    neutral: 'bg-slate-100 text-slate-600 ring-slate-500/10',
};
