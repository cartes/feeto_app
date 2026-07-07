<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Throwable;

class ClientVehicleWorkbookImport extends StringValueBinder implements ToCollection, WithCustomValueBinder, WithHeadingRow
{
    /**
     * @var array{
     *     kind: string,
     *     processed_rows: int,
     *     skipped_rows: int,
     *     error_rows: int,
     *     created_clients: int,
     *     updated_clients: int,
     *     created_vehicles: int,
     *     updated_vehicles: int,
     *     errors: array<int, array{row: int, message: string}>
     * }
     */
    private array $summary = [
        'kind' => 'clients',
        'processed_rows' => 0,
        'skipped_rows' => 0,
        'error_rows' => 0,
        'created_clients' => 0,
        'updated_clients' => 0,
        'created_vehicles' => 0,
        'updated_vehicles' => 0,
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
     *     created_clients: int,
     *     updated_clients: int,
     *     created_vehicles: int,
     *     updated_vehicles: int,
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
        if ($row['rut'] === null) {
            throw new InvalidArgumentException('La columna RUT es obligatoria.');
        }

        if ($row['name'] === null) {
            throw new InvalidArgumentException('La columna nombre es obligatoria.');
        }

        if ($row['plate'] === null && $this->hasVehicleData($row)) {
            throw new InvalidArgumentException('Debes indicar la patente para importar un vehiculo.');
        }

        DB::transaction(function () use ($row): void {
            $client = $this->resolveClient($row);

            if ($row['plate'] !== null) {
                $this->resolveVehicle($client, $row);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveClient(array $row): Client
    {
        $normalizedRut = $this->normalizeRut($row['rut']);
        $existingClient = Client::query()
            ->where(function ($query) use ($normalizedRut): void {
                $query
                    ->where('rut', $normalizedRut)
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(UPPER(rut), '.', ''), '-', ''), ' ', '') = ?",
                        [str_replace('-', '', $normalizedRut)],
                    );
            })
            ->first();

        $client = $existingClient ?? new Client;
        $isNewClient = ! $client->exists;

        $client->fill(array_filter([
            'rut' => $normalizedRut,
            'name' => $row['name'],
            'phone' => $row['phone'],
            'secondary_phone' => $row['secondary_phone'],
            'email' => $row['email'],
            'address' => $row['address'],
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));

        $client->save();

        $this->summary[$isNewClient ? 'created_clients' : 'updated_clients']++;

        return $client;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveVehicle(Client $client, array $row): void
    {
        $plate = $this->normalizePlate($row['plate']);
        $vehicle = Vehicle::withTrashed()->where('plate', $plate)->first() ?? new Vehicle;
        $isNewVehicle = ! $vehicle->exists;

        if ($vehicle->trashed()) {
            $vehicle->restore();
        }

        $vehicle->fill(array_filter([
            'client_id' => $client->id,
            'plate' => $plate,
            'brand' => $row['brand'],
            'model' => $row['model'],
            'color' => $row['color'],
            'vin' => $row['vin'],
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));

        $vehicle->client()->associate($client);
        $vehicle->save();

        $this->summary[$isNewVehicle ? 'created_vehicles' : 'updated_vehicles']++;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     rut: ?string,
     *     name: ?string,
     *     phone: ?string,
     *     secondary_phone: ?string,
     *     email: ?string,
     *     address: ?string,
     *     plate: ?string,
     *     brand: ?string,
     *     model: ?string,
     *     color: ?string,
     *     vin: ?string
     * }
     */
    private function normalizeRow(array $row): array
    {
        return [
            'rut' => $this->firstValue($row, ['rut', 'rut_cliente', 'cliente_rut']),
            'name' => $this->firstValue($row, ['nombre', 'nombre_cliente', 'cliente', 'client_name']),
            'phone' => $this->firstValue($row, ['telefono', 'telefono_cliente', 'phone']),
            'secondary_phone' => $this->firstValue($row, ['telefono_secundario', 'telefono_2', 'secondary_phone']),
            'email' => $this->firstValue($row, ['email', 'correo', 'correo_cliente']),
            'address' => $this->firstValue($row, ['direccion', 'address']),
            'plate' => $this->firstValue($row, ['patente', 'ppu', 'plate']),
            'brand' => $this->firstValue($row, ['marca', 'brand']),
            'model' => $this->firstValue($row, ['modelo', 'model']),
            'color' => $this->firstValue($row, ['color']),
            'vin' => $this->firstValue($row, ['vin', 'chasis']),
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

    /**
     * @param  array<string, ?string>  $row
     */
    private function hasVehicleData(array $row): bool
    {
        return collect(['brand', 'model', 'color', 'vin'])
            ->contains(fn (string $field): bool => filled($row[$field] ?? null));
    }

    private function normalizeRut(string $value): string
    {
        $clean = strtoupper((string) preg_replace('/[^0-9K]/i', '', $value));

        if (strlen($clean) < 2) {
            throw new InvalidArgumentException('El RUT no es valido.');
        }

        return substr($clean, 0, -1).'-'.substr($clean, -1);
    }

    private function normalizePlate(string $value): string
    {
        $clean = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $value));

        if ($clean === '') {
            throw new InvalidArgumentException('La patente no es valida.');
        }

        return $clean;
    }
}
