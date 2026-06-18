<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VehicleBrand;
use App\Services\VehicleCatalogService;
use Illuminate\Http\JsonResponse;

class VehicleCatalogController extends Controller
{
    public function models(VehicleBrand $vehicleBrand, VehicleCatalogService $vehicleCatalogService): JsonResponse
    {
        return response()->json([
            'models' => $vehicleCatalogService->modelOptionsForBrand($vehicleBrand),
        ]);
    }
}
