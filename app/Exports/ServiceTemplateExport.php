<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ServiceTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        return [
            [
                'SERV-MAN-01',
                'Cambio de aceite y filtro',
                'Servicio de mantencion preventiva con cambio de aceite y filtro',
                15000,
                35000,
                'con_iva',
                45,
                'si',
            ],
            [
                'SERV-ALIN-02',
                'Alineacion y balanceo 4 ruedas',
                'Alineacion laser computarizada y balanceo',
                8000,
                22000,
                'mas_iva',
                60,
                'si',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'codigo',
            'nombre',
            'descripcion',
            'costo',
            'precio_venta',
            'impuesto',
            'minutos_estimados',
            'activo',
        ];
    }

    public function title(): string
    {
        return 'Servicios';
    }
}
