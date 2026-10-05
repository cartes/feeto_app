<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTenantSetupChecklistRequest;
use App\Models\Tenant;
use App\Services\TenantSetupChecklistService;
use Illuminate\Http\RedirectResponse;

class TenantSetupChecklistController extends Controller
{
    public function __invoke(UpdateTenantSetupChecklistRequest $request, TenantSetupChecklistService $checklist): RedirectResponse
    {
        $checklist->updateStep(Tenant::current(), $request->validated('step'), $request->boolean('completed'));

        return back()->with('success', 'Progreso de preparación guardado.');
    }
}
