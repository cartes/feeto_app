<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ReportExportsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_all_reports_can_be_downloaded_as_pdf(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        foreach (['sales', 'supervisors', 'inventory', 'customers', 'collections'] as $report) {
            $this->actingAs($supervisor)
                ->get(route('reports.export.pdf', ['tenantBySlug' => $tenant->slug, 'report' => $report]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertHeader('content-disposition');
        }
    }

    public function test_all_reports_can_be_downloaded_as_excel(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['plan' => 'profesional']);

        $supervisor = User::factory()->create(['tenant_id' => $tenant->id]);
        $supervisor->assignRole('Supervisor');

        foreach (['sales', 'supervisors', 'inventory', 'customers', 'collections'] as $report) {
            $response = $this->actingAs($supervisor)
                ->get(route('reports.export.excel', ['tenantBySlug' => $tenant->slug, 'report' => $report]));

            $response->assertOk();
            $response->assertHeader('content-disposition');
            $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        }
    }
}
