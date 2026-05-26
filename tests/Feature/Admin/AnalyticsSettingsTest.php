<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_analytics_settings_page_returns_props_to_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('analytics_settings')
            ->has('analytics_settings.analytics_google_analytics_code')
            ->has('analytics_settings.analytics_google_search_console_code')
        );
    }

    public function test_analytics_settings_can_be_updated_by_super_admin(): void
    {
        $gaCode = "<!-- Google tag (gtag.js) -->\n<script>console.log('test-ga');</script>";
        $gscCode = '<meta name="google-site-verification" content="test-gsc-1234" />';

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.analytics'), [
                'analytics_google_analytics_code' => $gaCode,
                'analytics_google_search_console_code' => $gscCode,
            ]);

        $response->assertRedirect();
        $this->assertEquals($gaCode, Setting::get('analytics_google_analytics_code'));
        $this->assertEquals($gscCode, Setting::get('analytics_google_search_console_code'));
    }

    public function test_analytics_settings_cannot_be_updated_by_regular_user(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)
            ->put(route('admin.profile.analytics'), [
                'analytics_google_analytics_code' => 'test',
                'analytics_google_search_console_code' => 'test',
            ]);

        $response->assertForbidden();
    }

    public function test_analytics_scripts_are_injected_into_public_views(): void
    {
        $gaCode = '<!-- GA-TEST-TAG-DYNAMIC -->';
        $gscCode = '<!-- GSC-TEST-TAG-DYNAMIC -->';

        Setting::set('analytics_google_analytics_code', $gaCode);
        Setting::set('analytics_google_search_console_code', $gscCode);

        // Sin Vite para agilizar la carga del test
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee($gaCode, false);
        $response->assertSee($gscCode, false);
    }
}
