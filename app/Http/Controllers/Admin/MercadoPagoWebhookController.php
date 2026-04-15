<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = Setting::get('mp_webhook_secret');

        if ($secret) {
            $signature = $request->header('x-signature');
            $requestId = $request->header('x-request-id');

            if (! $this->isValidSignature($request, $signature, $requestId, $secret)) {
                Log::warning('MP webhook signature mismatch', ['headers' => $request->headers->all()]);

                return response('Unauthorized', 401);
            }
        }

        $topic = $request->input('topic') ?? $request->input('type');

        if ($topic !== 'payment') {
            return response('OK', 200);
        }

        $mpPaymentId = $request->input('data.id') ?? $request->input('id');

        if (! $mpPaymentId) {
            return response('OK', 200);
        }

        try {
            $accessToken = Setting::get('mp_access_token');
            MercadoPagoConfig::setAccessToken($accessToken);

            $client = new PaymentClient;
            $mpPayment = $client->get((int) $mpPaymentId);

            $externalRef = $mpPayment->external_reference;

            if (! $externalRef) {
                return response('OK', 200);
            }

            // external_reference format: tenant_{id}_plan_{id}
            preg_match('/tenant_(\d+)_plan_(\d+)/', $externalRef, $matches);

            if (count($matches) < 3) {
                return response('OK', 200);
            }

            $tenantId = (int) $matches[1];

            $payment = Payment::where('mp_preference_id', $request->input('data.id'))
                ->orWhere('tenant_id', $tenantId)
                ->whereNull('transaction_id')
                ->latest()
                ->first();

            if ($payment) {
                // Calcular comisiones desde fee_details de MP
                $totalMpFee = 0.0;
                if (! empty($mpPayment->fee_details)) {
                    foreach ($mpPayment->fee_details as $fee) {
                        $totalMpFee += (float) ($fee->amount ?? 0);
                    }
                }

                $payment->calculateFees($totalMpFee);
                $payment->status = $mpPayment->status;
                $payment->transaction_id = (string) $mpPaymentId;
                $payment->mp_payment_type = $mpPayment->payment_type_id ?? null;
                $payment->mp_installments = $mpPayment->installments > 1
                    ? (string) $mpPayment->installments
                    : null;
                $payment->paid_at = $mpPayment->status === 'approved' ? now() : null;
                $payment->save();

                if ($mpPayment->status === 'approved') {
                    Tenant::where('id', $tenantId)->update([
                        'subscription_ends_at' => now()->addMonth(),
                        'is_active' => true,
                    ]);

                    AuditLog::record(
                        'payment.approved',
                        "Pago aprobado MP #{$mpPaymentId} para tenant #{$tenantId}",
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('MP webhook error', ['error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }

    private function isValidSignature(Request $request, ?string $signature, ?string $requestId, string $secret): bool
    {
        if (! $signature || ! $requestId) {
            return false;
        }

        $dataId = $request->input('data.id', '');
        $manifest = "id:{$dataId};request-id:{$requestId};ts:".explode(',', $signature)[0] ?? '';

        $hash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hash, explode('=', $signature, 2)[1] ?? '');
    }
}
