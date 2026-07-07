<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        return [
            [
                'SKU-FA-100',
                'Filtro de aceite',
                'Filtros',
                'repuesto_nacional',
                'Filtro de aceite para mantencion general',
                5000,
                9990,
                12,
                3,
            ],
            [
                'SKU-BUJ-200',
                'Bujia iridium',
                'Encendido',
                'repuesto_internacional',
                'Bujia de alto rendimiento',
                8500,
                14990,
                8,
                2,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'sku',
            'nombre',
            'categoria',
            'tipo',
            'descripcion',
            'costo',
            'precio_venta',
            'stock',
            'stock_minimo',
        ];
    }

    public function title(): string
    {
        return 'Repuestos';
    }
}
