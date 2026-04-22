<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $clients = Client::query()
            ->when($search, function ($query, $search) {
                // Escapar caracteres wildcard de LIKE para evitar inyección de patrones
                $escaped = addcslashes($search, '%_');
                $query->where('name', 'like', "%{$escaped}%")
                    ->orWhere('rut', 'like', "%{$escaped}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): Response
    {
        $client->load('vehicles.workOrders.quote.items');

        $quotes = $client->vehicles
            ->flatMap(fn ($vehicle) => $vehicle->workOrders)
            ->map(fn ($workOrder) => $workOrder->quote)
            ->filter();

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'quoteSummary' => [
                'total_quotes' => $quotes->count(),
                'accepted_quotes' => $quotes->where('status', Quote::STATUS_ACCEPTED)->count(),
                'rejected_quotes' => $quotes->where('status', Quote::STATUS_REJECTED)->count(),
                'pending_quotes' => $quotes->where('status', Quote::STATUS_PENDING_CUSTOMER)->count(),
                'quoted_amount' => (float) $quotes->sum('subtotal_amount'),
                'accepted_amount' => (float) $quotes
                    ->where('status', Quote::STATUS_ACCEPTED)
                    ->sum('subtotal_amount'),
            ],
        ]);
    }
}
