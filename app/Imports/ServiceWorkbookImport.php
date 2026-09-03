<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Throwable;

class ServiceWorkbookImport extends StringValueBinder implements ToCollection, WithCustomValueBinder, WithHeadingRow
{
    /**
     * @var array{
     *     kind: string,
     *     processed_rows: int,
     *     skipped_rows: int,
     *     error_rows: int,
     *     created_services: int,
     *     updated_services: int,
     *     errors: array<int, array{row: int, message: string}>
     * }
     */
    private array $summary = [
        'kind' => 'services',
        'processed_rows' => 0,
        'skipped_rows' => 0,
        'error_rows' => 0,
        'created_services' => 0,
        'updated_services' => 0,
        'errors' => [],
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows->values() as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->normalizeRow($row->toArray());

            if ($this->isEmptyRow($payload)) {
                $this->summary['skipped_rows']++;

                continue;
            }

            try {
                $this->importRow($payload);
                $this->summary['processed_rows']++;
            } catch (Throwable $exception) {
                $this->summary['error_rows']++;
                $this->summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception instanceof InvalidArgumentException
                        ? $exception->getMessage()
                        : 'No se pudo importar la fila.',
                ];
            }
        }
    }

    /**
     * @return array{
     *     kind: string,
     *     processed_rows: int,
     *     skipped_rows: int,
     *     error_rows: int,
     *     created_services: int,
     *     updated_services: int,
     *     errors: array<int, array{row: int, message: string}>
     * }
     */
    public function summary(): array
    {
        return $this->summary;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(array $row): void
    {
        if ($row['name'] === null) {
            throw new InvalidArgumentException('La columna nombre es obligatoria.');
        }

        DB::transaction(function () use ($row): void {
            $code = $row['code'] !== null ? strtoupper((string) $row['code']) : null;
            $service = null;

            if ($code !== null) {
                $service = Service::query()->where('code', $code)->first();
            }

            if (! $service instanceof Service) {
                $service = Service::query()->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['name'])])->first();
            }

            $isNew = ! ($service instanceof Service);
            $service = $service ?? new Service;

            $service->fill([
                'name' => $row['name'],
                'code' => $code,
                'description' => $row['description'],
                'cost_price' => $this->parseDecimal($row['cost_price']),
                'selling_price' => $this->parseDecimal($row['selling_price']),
                'tax_included' => $this->parseBoolean($row['tax_included']),
                'estimated_minutes' => $this->parseInteger($row['estimated_minutes']),
                'is_active' => $this->parseIsActive($row['is_active']),
            ]);
            $service->save();

            $this->summary[$isNew ? 'created_services' : 'updated_services']++;
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     code: ?string,
     *     name: ?string,
     *     description: ?string,
     *     cost_price: ?string,
     *     selling_price: ?string,
     *     tax_included: ?string,
     *     estimated_minutes: ?string,
     *     is_active: ?string
     * }
     */
    private function normalizeRow(array $row): array
    {
        return [
            'code' => $this->firstValue($row, ['codigo', 'code', 'codigo_servicio', 'sku']),
            'name' => $this->firstValue($row, ['nombre', 'servicio', 'name', 'labor']),
            'description' => $this->firstValue($row, ['descripcion', 'detalle', 'description']),
            'cost_price' => $this->firstValue($row, ['costo', 'precio_costo', 'cost_price']),
            'selling_price' => $this->firstValue($row, ['precio_venta', 'venta', 'selling_price', 'precio']),
            'tax_included' => $this->firstValue($row, ['impuesto', 'iva', 'iva_incluido', 'con_iva', 'tipo_precio', 'tax_included']),
            'estimated_minutes' => $this->firstValue($row, ['minutos_estimados', 'minutos', 'duracion', 'estimated_minutes']),
            'is_active' => $this->firstValue($row, ['activo', 'is_active', 'estado']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function firstValue(array $row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (! array_key_exists($alias, $row)) {
                continue;
            }

            $value = is_string($row[$alias]) ? trim($row[$alias]) : $row[$alias];

            if ($value === null || $value === '') {
                continue;
            }

            return (string) $value;
        }

        return null;
    }

    /**
     * @param  array<string, ?string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDecimal(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = preg_replace('/\s+/', '', (string) $value);

        if ($normalized === null || $normalized === '') {
            return 0.0;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Los precios deben ser numericos.');
        }

        return round((float) $normalized, 2);
    }

    private function parseInteger(?string $value): int
    {
        $number = $this->parseDecimal($value);

        if ($number < 0) {
            throw new InvalidArgumentException('Los minutos estimados no pueden ser negativos.');
        }

        return (int) round($number);
    }

    private function parseBoolean(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['con_iva', 'con iva', 'si', 'sí', 'true', '1', 'incluido', 'iva_incluido', 'bruto'], true);
    }

    private function parseIsActive(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $normalized = mb_strtolower(trim((string) $value));

        if (in_array($normalized, ['no', 'false', '0', 'inactivo', 'desactivado'], true)) {
            return false;
        }

        return true;
    }
}
