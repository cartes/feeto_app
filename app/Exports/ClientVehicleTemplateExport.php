<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientVehicleTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            [
                'Juan Perez',
                '12345678-5',
                '+56911111111',
                '+56922222222',
                'juan@cliente.cl',
                'Av. Apoquindo 1234, Las Condes',
                'ABCD12',
                'Toyota',
                'Yaris',
                'Rojo',
                'JT123456789012345',
            ],
            [
                'Juan Perez',
                '12345678-5',
                '+56911111111',
                '',
                'juan@cliente.cl',
                'Av. Apoquindo 1234, Las Condes',
                'BCDE23',
                'Mazda',
                'CX-5',
                'Gris',
                'JM123456789012346',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'nombre',
            'rut',
            'telefono',
            'telefono_secundario',
            'email',
            'direccion',
            'patente',
            'marca',
            'modelo',
            'color',
            'vin',
        ];
    }

    public function title(): string
    {
        return 'Clientes y vehiculos';
    }
}
