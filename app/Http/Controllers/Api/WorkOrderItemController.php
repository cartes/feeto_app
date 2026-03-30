<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $workOrder) {
            $product = Product::findOrFail($validated['product_id']);

            // Verificar stock físico
            if ($product->physical_stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stock insuficiente. Disponible: ' . $product->physical_stock],
                ]);
            }

            $unitPrice = $product->selling_price;
            $totalPrice = $unitPrice * $validated['quantity'];

            // Crear el item
            $item = $workOrder->items()->create([
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            // Descontar stock
            $product->decrement('physical_stock', $validated['quantity']);

            // Actualizar total de la OT
            $workOrder->increment('total_amount', $totalPrice);

            return response()->json([
                'message' => 'Producto agregado con éxito',
                'item' => $item->load('product'),
                'total_amount' => $workOrder->fresh()->total_amount,
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkOrder $workOrder, WorkOrderItem $item): JsonResponse
    {
        return DB::transaction(function () use ($workOrder, $item) {
            // Revertir stock si hay producto asociado
            if ($item->product_id) {
                Product::where('id', $item->product_id)->increment('physical_stock', $item->quantity);
            }

            // Revertir total de la OT
            $workOrder->decrement('total_amount', $item->total_price);

            $item->delete();

            return response()->json([
                'message' => 'Item eliminado con éxito',
                'total_amount' => $workOrder->fresh()->total_amount,
            ]);
        });
    }
}
