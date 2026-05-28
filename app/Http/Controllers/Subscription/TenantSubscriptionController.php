<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class TenantSubscriptionController extends Controller
{
    public function plans(PlanFeatureService $planFeatureService): Response
    {
        $tenant = Tenant::current();
        abort_if(! $tenant, 404);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_annual' => $plan->price_annual,
                'discounted_monthly_price' => $plan->discountedMonthlyPrice(),
                'features' => array_values(array_unique(array_merge(
                    $plan->features ?? [],
                    collect($plan->feature_keys ?? [])
                        ->map(fn (string $key): string => $planFeatureService->definition($key)['label'])
                        ->all(),
                ))),
                'feature_keys' => $plan->feature_keys ?? [],
                'max_users' => $plan->max_users,
                'is_popular' => (bool) $plan->is_popular,
                'has_discount' => $plan->hasActiveDiscount(),
                'discount_percent' => $plan->discount_percent,
                'discount_valid_until' => $plan->discount_valid_until?->toDateString(),
            ]);

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'currentPlanId' => $tenant->plan_id,
            'subscriptionEndsAt' => $tenant->subscription_ends_at?->toIso8601String(),
            'isActive' => $tenant->is_active,
        ]);
    }

    public function createPreference(Request $request): RedirectResponse
    {
        $tenant = Tenant::current();
        abort_if(! $tenant, 404);
        abort_if(auth()->user()->tenant_id !== $tenant->id, 403);

        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_period' => ['required', 'in:monthly,annual'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $billingPeriod = $request->billing_period;
        $amount = $billingPeriod === Payment::BILLING_ANNUAL
            ? $plan->price_annual
            : $plan->discountedMonthlyPrice();

        $accessToken = Setting::get('mp_access_token');
        $isSandbox = Setting::get('mp_sandbox', 'true') === 'true';

        if (! $accessToken) {
            return back()->with('error', 'No hay credenciales de Mercado Pago configuradas. Contacta al administrador.');
        }

        MercadoPagoConfig::setAccessToken($accessToken);

        if ($isSandbox) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }

        $periodLabel = $billingPeriod === Payment::BILLING_ANNUAL ? 'Anual' : 'Mensual';

        $client = new PreferenceClient;
        $preference = $client->create([
            'items' => [
                [
                    'title' => "Suscripción {$plan->name} ({$periodLabel}) - {$tenant->name}",
                    'quantity' => 1,
                    'unit_price' => (float) $amount,
                    'currency_id' => 'CLP',
                ],
            ],
            'back_urls' => [
                'success' => route('subscription.success', ['tenantBySlug' => $tenant->slug]),
                'failure' => route('subscription.failure', ['tenantBySlug' => $tenant->slug]),
                'pending' => route('subscription.pending', ['tenantBySlug' => $tenant->slug]),
            ],
            'auto_return' => 'approved',
            'external_reference' => "tenant_{$tenant->id}_plan_{$plan->id}",
            'notification_url' => route('admin.payments.webhook'),
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'currency' => 'CLP',
            'billing_period' => $billingPeriod,
            'status' => Payment::STATUS_PENDING,
            'method' => 'mercadopago',
            'mp_preference_id' => $preference->id,
        ]);

        AuditLog::record(
            'payment.preference_created',
            "Preferencia MP creada por tenant para {$tenant->name} ({$plan->name} {$periodLabel})",
            $payment
        );

        return redirect($isSandbox ? $preference->sandbox_init_point : $preference->init_point);
    }

    public function success(): RedirectResponse
    {
        return redirect()->route('subscription.plans', ['tenantBySlug' => Tenant::current()?->slug])
            ->with('success', 'Pago procesado correctamente. Tu suscripción será activada en breve.');
    }

    public function failure(): RedirectResponse
    {
        return redirect()->route('subscription.plans', ['tenantBySlug' => Tenant::current()?->slug])
            ->with('error', 'El pago fue rechazado o cancelado. Puedes intentarlo nuevamente.');
    }

    public function pending(): RedirectResponse
    {
        return redirect()->route('subscription.plans', ['tenantBySlug' => Tenant::current()?->slug])
            ->with('info', 'Tu pago está pendiente de confirmación. Te avisaremos cuando sea procesado.');
    }
}
