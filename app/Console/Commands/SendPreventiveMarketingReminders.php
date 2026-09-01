<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('marketing:preventive-reminders')]
#[Description('Sends preventive marketing reminders to customers whose last visit was 6 months ago.')]
class SendPreventiveMarketingReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // En una app multitenant, iteraríamos por los tenants activos
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            // Buscar vehículos cuya última orden de trabajo fue hace 6 meses
            $sixMonthsAgo = Carbon::now()->subMonths(6)->toDateString();

            $vehicles = Vehicle::whereHas('workOrders', function ($query) use ($sixMonthsAgo) {
                $query->whereDate('created_at', $sixMonthsAgo);
            })->with(['client', 'workOrders' => function ($query) {
                $query->latest();
            }])->get();

            foreach ($vehicles as $vehicle) {
                $client = $vehicle->client;

                if (! $client || ! $client->phone) {
                    continue;
                }

                // Aquí iría la integración real con WhatsApp API.
                // Ejemplo de lo que haría:
                $message = sprintf(
                    'Hola %s, hace 6 meses que visitaste nuestro taller con tu vehículo %s %s. ¡Es un buen momento para una revisión preventiva!',
                    $client->name,
                    $vehicle->brand,
                    $vehicle->model
                );

                $this->info("Mensaje simulado enviado a {$client->phone}: {$message}");
            }

            Tenant::forgetCurrent();
        }

        $this->info('Recordatorios preventivos enviados correctamente.');
    }
}
