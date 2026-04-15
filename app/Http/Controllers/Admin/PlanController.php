<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Form', [
            'plan' => null,
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->validated());

        AuditLog::record('plan.created', "Plan '{$plan->name}' creado", $plan);

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$plan->name}' creado correctamente.");
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Admin/Plans/Form', [
            'plan' => $plan,
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        AuditLog::record('plan.updated', "Plan '{$plan->name}' actualizado", $plan);

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$plan->name}' actualizado correctamente.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $name = $plan->name;
        $plan->delete();

        AuditLog::record('plan.deleted', "Plan '{$name}' eliminado");

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$name}' eliminado.");
    }
}
