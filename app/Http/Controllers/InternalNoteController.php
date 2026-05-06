<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInternalNoteRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;

class InternalNoteController extends Controller
{
    public function store(StoreInternalNoteRequest $request, Client $client): RedirectResponse
    {
        $client->internalNotes()->create([
            'content' => $request->validated('content'),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Nota interna agregada correctamente.');
    }
}
