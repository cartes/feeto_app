<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_access_own_media_file(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenantPrefix = "tenants/{$tenant->id}/";
        $fileName = 'work_orders/test-image.jpg';
        $relativePath = $tenantPrefix.$fileName;

        Storage::disk('local')->put($relativePath, 'fake-binary-image-data');

        $tenant->makeCurrent();

        $response = $this->actingAs($admin)
            ->get(route('storage.serve', [
                'tenantBySlug' => $tenant->slug,
                'path' => $relativePath,
            ]));

        $response->assertOk();

        Tenant::forgetCurrent();
    }

    public function test_media_file_access_rejects_path_traversal(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        app(TenantSetupService::class)->provisionTenant($tenant, $admin);

        $tenant->makeCurrent();

        $response = $this->actingAs($admin)
            ->get(route('storage.serve', [
                'tenantBySlug' => $tenant->slug,
                'path' => "tenants/{$tenant->id}/../another_file.txt",
            ]));

        $this->assertTrue(in_array($response->getStatusCode(), [400, 403, 404], true));

        Tenant::forgetCurrent();
    }

    public function test_tenant_cannot_access_another_tenants_media_file(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        app(TenantSetupService::class)->provisionTenant($tenantA, $adminA);

        $relativePathB = "tenants/{$tenantB->id}/secret.jpg";
        Storage::disk('local')->put($relativePathB, 'secret-content');

        $tenantA->makeCurrent();

        $response = $this->actingAs($adminA)
            ->get(route('storage.serve', [
                'tenantBySlug' => $tenantA->slug,
                'path' => $relativePathB,
            ]));

        $response->assertForbidden();

        Tenant::forgetCurrent();
    }
}
