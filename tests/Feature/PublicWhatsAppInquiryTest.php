<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWhatsAppInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_inquiry_creates_notification_and_persistent_lead(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'taller-whatsapp',
        ]);

        $this->postJson(route('taller.whatsapp.inquiry', ['tenantBySlug' => $tenant->slug]), [
            'visitor_name' => 'María Cliente',
            'phone' => '+56998765432',
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('tenant_notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'whatsapp_inquiry',
            'title' => 'Nueva consulta por WhatsApp',
        ]);

        $this->assertDatabaseHas('tenant_leads', [
            'tenant_id' => $tenant->id,
            'source' => TenantLead::SOURCE_WHATSAPP,
            'channel' => 'landing_page',
            'visitor_name' => 'María Cliente',
            'phone' => '+56998765432',
        ]);
    }
}
