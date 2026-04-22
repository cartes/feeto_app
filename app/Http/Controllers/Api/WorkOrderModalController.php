<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Tenant;

class WorkOrderModalController extends Controller
{
    /**
     * Devuelve el detalle de la OT para el Modal.
     */
    public function show(int $id): JsonResponse
    {
        $workOrder = WorkOrder::with(['quote.items.product', 'quote.items.service', 'images', 'vehicle.client'])
            ->findOrFail($id);

        $payload = $workOrder->toArray();
        $payload['items'] = $workOrder->quote?->items?->values()->all() ?? [];
        $payload['quote'] = $workOrder->quote;
        $payload['total_amount'] = (float) ($workOrder->quote?->subtotal_amount ?? $workOrder->total_amount);

        return response()->json($payload);
    }

    /**
     * Sube una imagen de evidencia fotográfica.
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
            'notes' => 'nullable|string',
        ]);

        $workOrder = WorkOrder::findOrFail($id);

        // Verificar que el usuario pertenezca al tenant de la work order
        $user = $request->user();
        if ($user->tenant_id !== $workOrder->tenant_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($request->hasFile('image')) {
            $tenant = Tenant::current();

            if (! $tenant) {
                return response()->json(['message' => 'Tenant no identificado.'], 403);
            }

            // Almacenar en disco local (privado) en lugar de público
            $path = $request->file('image')->store("tenants/{$tenant->id}/work_orders/imagenes", 'local');

            $image = $workOrder->images()->create([
                'image_path' => $path,
                'notes' => $request->input('notes'),
            ]);

            return response()->json([
                'message' => 'Imagen subida con éxito',
                'image' => $image,
                'url' => route('storage.serve', ['path' => $path]),
            ]);
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }

    /**
     * Elimina una imagen de evidencia.
     */
    public function destroyImage(int $imageId, Request $request): JsonResponse
    {
        $image = WorkOrderImage::with('workOrder')->findOrFail($imageId);

        // Verificar que el usuario pertenezca al tenant de la work order
        $user = $request->user();
        if ($user->tenant_id !== $image->workOrder->tenant_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Eliminar archivo físico del disco local
        if (Storage::disk('local')->exists($image->image_path)) {
            Storage::disk('local')->delete($image->image_path);
        }

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada con éxito']);
    }
}
