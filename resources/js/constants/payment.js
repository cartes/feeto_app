export const STATUS_CONFIG = {
    approved:  { label: 'Aprobado',    classes: 'bg-emerald-100 text-emerald-800' },
    pending:   { label: 'Pendiente',   classes: 'bg-amber-100 text-amber-800' },
    rejected:  { label: 'Rechazado',   classes: 'bg-rose-100 text-rose-800' },
    cancelled: { label: 'Cancelado',   classes: 'bg-slate-100 text-slate-600' },
    refunded:  { label: 'Reembolsado', classes: 'bg-purple-100 text-purple-800' },
};

export const PAYMENT_TYPE_LABEL = {
    credit_card:   'Tarjeta Crédito',
    debit_card:    'Tarjeta Débito',
    account_money: 'Billetera MP',
    ticket:        'Cupón/Ticket',
    bank_transfer: 'Transferencia',
};
