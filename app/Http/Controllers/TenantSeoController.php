<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\SeoDescriptionAgent;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantSeoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_address' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'website_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
        ]);

        $tenant = Tenant::current();
        $tenant->update($validated);

        return back()->with('success', 'Perfil SEO actualizado correctamente.');
    }

    public function generateDescription(Request $request, SeoDescriptionAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'workshop_name' => ['required', 'string', 'max:100'],
            'concepts' => ['required', 'array', 'min:1', 'max:3'],
            'concepts.*' => ['required', 'string', 'max:60'],
        ]);

        $conceptsText = implode(', ', $validated['concepts']);

        try {
            $result = $agent->prompt(
                "Taller: {$validated['workshop_name']}. Conceptos clave: {$conceptsText}."
            );

            return response()->json([
                'description' => $result['description'] ?? '',
                'long_description' => $result['long_description'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('SEO description generation failed: '.$e->getMessage());

            return response()->json(['error' => 'No se pudo generar la descripción. Intenta de nuevo.'], 500);
        }
    }
}
