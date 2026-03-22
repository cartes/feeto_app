<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\PatentReaderAgent;
use App\Events\PatentRecognized;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Tenant;
use Inertia\Inertia;
use Laravel\Ai\Files\Image;

class ReceptionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Reception/Create', [
            'tenantId' => Tenant::current() ? Tenant::current()->id : 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $path = $request->file('image')->store('reception/temp', 'public');
        $fullUrl = Storage::disk('public')->url($path);
        
        // Asignamos la ruta absoluta real del disco para el agente
        $absolutePath = Storage::disk('public')->path($path);

        $agent = new PatentReaderAgent();
        $agent->with(Image::fromPath($absolutePath));

        // Background processing queue
        $agent->queue()->then(function (array $result) use ($fullUrl) {
            $rawPatente = $result['patente'] ?? '';

            // Limpieza estricta: Solo mayúsculas, O->0, I->1
            $cleanPatente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawPatente));
            $cleanPatente = str_replace(['O', 'I'], ['0', '1'], $cleanPatente);

            // Validación (Nuevo o Antiguo formato)
            $isValid = preg_match('/^[BCDFGHJKLPRSTVWXYZ]{4}\d{2}$/', $cleanPatente) ||
                       preg_match('/^[A-Z]{2}\d{4}$/', $cleanPatente);

            if ($isValid) {
                // Emitir evento por WebSockets
                broadcast(new PatentRecognized($cleanPatente, $fullUrl));
            } else {
                // Emitir error (opcional para el front, enviamos ERROR para la demo)
                broadcast(new PatentRecognized("ERROR_FORMATO", $fullUrl));
            }
        });

        return response()->json([
            'message' => 'Image received and queued for analysis.',
            'queue' => true,
        ]);
    }
}
