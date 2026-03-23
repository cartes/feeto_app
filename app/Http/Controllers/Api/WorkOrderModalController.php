<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\WorkOrderImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkOrderModalController extends Controller
{
    /**
     * Devuelve el detalle de la OT para el Modal.
     */
    public function show(int $id): JsonResponse
    {
        $workOrder = WorkOrder::with(['items', 'images', 'vehicle.client'])
            ->findOrFail($id);

        return response()->json($workOrder);
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

        if ($request->hasFile('image')) {
            $tenantId = \Spatie\Multitenancy\Models\Tenant::current()->id;
            $path = $request->file('image')->store("tenants/{$tenantId}/work_orders/imagenes", 'public');

            $image = $workOrder->images()->create([
                'image_path' => $path,
                'notes'      => $request->input('notes'),
            ]);

            return response()->json([
                'message' => 'Imagen subida con éxito',
                'image'   => $image,
                'url'     => Storage::url($path),
            ]);
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }

    /**
     * Elimina una imagen de evidencia.
     */
    public function destroyImage(int $imageId): JsonResponse
    {
        $image = WorkOrderImage::findOrFail($imageId);

        // Eliminar archivo físico
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada con éxito']);
    }
}
