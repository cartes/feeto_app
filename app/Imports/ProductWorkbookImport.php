<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Throwable;

class ProductWorkbookImport extends StringValueBinder implements ToCollection, WithCustomValueBinder, WithHeadingRow
{
    /**
     * @var array{
     *     kind: string,
     *     processed_rows: int,
     *     skipped_rows: int,
     *     error_rows: int,
     *     created_products: int,
     *     updated_products: int,
     *     created_categories: int,
     *     errors: array<int, array{row: int, message: string}>
     * }
     */
    private array $summary = [
        'kind' => 'products',
        'processed_rows' => 0,
        'skipped_rows' => 0,
        'error_rows' => 0,
        'created_products' => 0,
        'updated_products' => 0,
        'created_categories' => 0,
        'errors' => [],
    ];

    public function __construct(
        private readonly ?StockService $stockService = null,
    ) {}

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
     *     created_products: int,
     *     updated_products: int,
     *     created_categories: int,
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
        if ($row['sku'] === null) {
            throw new InvalidArgumentException('La columna SKU es obligatoria.');
        }

        if ($row['name'] === null) {
            throw new InvalidArgumentException('La columna nombre es obligatoria.');
        }

        DB::transaction(function () use ($row): void {
            $sku = strtoupper($row['sku']);
            $product = Product::withTrashed()->where('sku', $sku)->first() ?? new Product;
            $isNewProduct = ! $product->exists;
            $oldStock = (int) $product->physical_stock;

            if ($product->trashed()) {
                $product->restore();
            }

            $product->fill([
                'name' => $row['name'],
                'sku' => $sku,
                'category_id' => $this->resolveCategoryId($row['category']),
                'type' => $this->normalizeType($row['type']),
                'description' => $row['description'],
                'cost_price' => $this->parseDecimal($row['cost_price']),
                'selling_price' => $this->parseDecimal($row['selling_price']),
                'physical_stock' => $this->parseInteger($row['physical_stock']),
                'min_stock' => $this->parseInteger($row['min_stock']),
            ]);
            $product->save();

            $this->summary[$isNewProduct ? 'created_products' : 'updated_products']++;

            $this->stockService()
                ->recordManualAdjustment($product, $oldStock, (int) $product->physical_stock);
        });
    }

    private function stockService(): StockService
    {
        return $this->stockService ?? app(StockService::class);
    }

    private function resolveCategoryId(?string $categoryName): ?int
    {
        if ($categoryName === null || $categoryName === '') {
            return null;
        }

        $existingCategory = ProductCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
            ->first();

        if ($existingCategory instanceof ProductCategory) {
            return $existingCategory->id;
        }

        $category = ProductCategory::create([
            'name' => $categoryName,
            'slug' => ProductCategory::generateUniqueSlug($categoryName, (int) auth()->user()?->tenant_id),
        ]);

        $this->summary['created_categories']++;

        return $category->id;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     sku: ?string,
     *     name: ?string,
     *     category: ?string,
     *     type: ?string,
     *     description: ?string,
     *     cost_price: ?string,
     *     selling_price: ?string,
     *     physical_stock: ?string,
     *     min_stock: ?string
     * }
     */
    private function normalizeRow(array $row): array
    {
        return [
            'sku' => $this->firstValue($row, ['sku', 'codigo', 'codigo_interno']),
            'name' => $this->firstValue($row, ['nombre', 'repuesto', 'producto', 'name']),
            'category' => $this->firstValue($row, ['categoria', 'familia', 'category']),
            'type' => $this->firstValue($row, ['tipo', 'type']),
            'description' => $this->firstValue($row, ['descripcion', 'detalle', 'description']),
            'cost_price' => $this->firstValue($row, ['costo', 'precio_costo', 'cost_price']),
            'selling_price' => $this->firstValue($row, ['precio_venta', 'venta', 'selling_price']),
            'physical_stock' => $this->firstValue($row, ['stock', 'stock_fisico', 'physical_stock']),
            'min_stock' => $this->firstValue($row, ['stock_minimo', 'min_stock']),
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

    private function normalizeType(?string $value): string
    {
        $normalized = strtolower((string) str_replace(['-', ' '], '_', trim((string) $value)));

        return match ($normalized) {
            '', 'repuesto_nacional', 'nacional' => 'repuesto_nacional',
            'repuesto_internacional', 'internacional' => 'repuesto_internacional',
            'insumo' => 'insumo',
            default => throw new InvalidArgumentException('El tipo debe ser repuesto_nacional, repuesto_internacional o insumo.'),
        };
    }

    private function parseDecimal(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $normalized = preg_replace('/\s+/', '', $value);

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
            throw new InvalidArgumentException('Los valores de stock no pueden ser negativos.');
        }

        return (int) round($number);
    }
}
