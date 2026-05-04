<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class InternalNoteController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $client->internalNotes()->create([
            'content' => $validated['content'],
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Nota interna agregada correctamente.');
    }
}
