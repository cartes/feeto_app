<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(protected array $definition) {}

    public function sheets(): array
    {
        $sheets = [
            new ReportWorksheetExport(
                title: 'Resumen',
                headings: ['Metrica', 'Valor'],
                rows: collect($this->definition['summary'])
                    ->map(fn (array $metric): array => [$metric['label'], $metric['value']])
                    ->all(),
            ),
        ];

        foreach ($this->definition['sections'] as $section) {
            $sheets[] = new ReportWorksheetExport(
                title: $section['title'],
                headings: collect($section['columns'])->pluck('label')->all(),
                rows: collect($section['rows'])
                    ->map(function (array $row) use ($section): array {
                        return collect($section['columns'])
                            ->map(fn (array $column): mixed => $row[$column['key']] ?? null)
                            ->all();
                    })
                    ->all(),
            );
        }

        return $sheets;
    }
}
