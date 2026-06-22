<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Entry = 'entry';
    case Exit = 'exit';
    case Adjustment = 'adjustment';
    case Reservation = 'reservation';
    case Release = 'release';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entrada',
            self::Exit => 'Salida',
            self::Adjustment => 'Ajuste',
            self::Reservation => 'Reserva',
            self::Release => 'Liberación',
        };
    }
}
