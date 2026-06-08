<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantLead;
use App\Models\TenantNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicWhatsAppInquiryController extends Controller
{
    public function store(Request $request, Tenant $tenantBySlug): JsonResponse
    {
        $validated = $request->validate([
            'visitor_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        TenantLead::create([
            'tenant_id' => $tenantBySlug->id,
            'source' => TenantLead::SOURCE_WHATSAPP,
            'channel' => 'landing_page',
            'visitor_name' => $validated['visitor_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'metadata' => [
                'landing_path' => $request->path() ?: '/',
                'referer' => $request->headers->get('referer'),
            ],
            'occurred_at' => now(),
        ]);

        TenantNotification::create([
            'tenant_id' => $tenantBySlug->id,
            'type' => 'whatsapp_inquiry',
            'title' => 'Nueva consulta por WhatsApp',
            'body' => 'Un cliente inició una conversación de WhatsApp desde tu página web.',
            'data' => [
                'visitor_name' => $validated['visitor_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'source' => 'landing_page',
                'landing_path' => $request->path() ?: '/',
            ],
        ]);

        return response()->json(['ok' => true]);
    }
}
