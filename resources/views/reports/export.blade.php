<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $definition['title'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.4;
        }

        h1, h2, p {
            margin: 0;
        }

        .header {
            margin-bottom: 24px;
        }

        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-top: 8px;
        }

        .summary-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table {
            margin-bottom: 24px;
        }

        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            vertical-align: top;
        }

        .summary-label {
            color: #6b7280;
            display: block;
            font-size: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }

        .section {
            margin-top: 22px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
        }

        .data-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
        }

        .empty {
            border: 1px dashed #d1d5db;
            color: #6b7280;
            padding: 12px;
        }
    </style>
</head>
<body>
@php
    $formatValue = function (mixed $value, string $type): string {
        return match ($type) {
            'currency' => '$' . number_format((float) $value, 0, ',', '.'),
            'percent' => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%',
            'date' => filled($value) ? \Carbon\Carbon::parse($value)->format('d-m-Y') : 'Sin fecha',
            default => filled($value) ? (string) $value : '-',
        };
    };
@endphp

<div class="header">
    <h1>{{ $definition['title'] }}</h1>
    <p style="margin-top: 6px;">{{ $definition['description'] }}</p>
    <p class="meta">{{ $tenantName }} | Generado el {{ $generatedAt->format('d-m-Y H:i') }}</p>
</div>

<table class="summary-table">
    <tbody>
    @foreach (collect($definition['summary'])->chunk(2) as $chunk)
        <tr>
            @foreach ($chunk as $metric)
                <td>
                    <span class="summary-label">{{ $metric['label'] }}</span>
                    <span class="summary-value">{{ $formatValue($metric['value'], $metric['type']) }}</span>
                </td>
            @endforeach
            @if ($chunk->count() === 1)
                <td></td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>

@foreach ($definition['sections'] as $section)
    <div class="section">
        <h2 class="section-title">{{ $section['title'] }}</h2>

        @if (empty($section['rows']))
            <div class="empty">Sin datos para este bloque.</div>
        @else
            <table class="data-table">
                <thead>
                <tr>
                    @foreach ($section['columns'] as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        @foreach ($section['columns'] as $column)
                            <td>{{ $formatValue($row[$column['key']] ?? null, $column['type']) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endforeach
</body>
</html>
