<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    /**
     * Permite a los administradores generales alternar entre sucursales o ver todas.
     */
    public function __invoke(Request $request, BranchContext $branchContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->branch_id !== null) {
            abort(403, 'Los usuarios asignados a una sucursal única no pueden cambiar de sede.');
        }

        $branchId = $request->input('branch_id');
        $parsedId = ($branchId === null || $branchId === '' || $branchId === 'all') ? null : (int) $branchId;

        $success = $branchContext->switchBranch($parsedId);

        if (! $success && $parsedId !== null) {
            return back()->with('error', 'No se pudo cambiar a la sucursal seleccionada.');
        }

        return back()->with('success', $parsedId === null
            ? 'Visualizando todas las sucursales del taller.'
            : 'Sucursal activa actualizada.');
    }
}
