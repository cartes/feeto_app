<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_profile_page_is_accessible_to_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.profile'));

        $response->assertOk();
    }

    public function test_profile_page_is_not_accessible_to_regular_user(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.profile'));

        $response->assertForbidden();
    }

    public function test_profile_can_be_updated(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.update'), [
                'name' => 'Updated Name',
                'email' => 'updated@tallerflow.cl',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Updated Name', 'email' => 'updated@tallerflow.cl']);
    }

    public function test_password_can_be_changed(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.password'), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response->assertRedirect();
    }

    public function test_api_keys_can_be_updated(): void
    {
        Setting::create([
            'key' => 'gemini_api_key',
            'value' => null,
            'group' => 'ai',
            'description' => 'Google Gemini API Key',
            'is_secret' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.api-keys'), [
                'gemini_api_key' => 'test-gemini-key-12345',
            ]);

        $response->assertRedirect();
        $this->assertEquals('test-gemini-key-12345', Setting::get('gemini_api_key'));
    }

    public function test_profile_page_reflects_runtime_ai_configuration_when_settings_are_missing(): void
    {
        config([
            'ai.default' => 'gemini',
            'ai.default_for_images' => 'gemini',
            'ai.providers.gemini.key' => 'runtime-gemini-key',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('ai_settings.ai_provider.value', 'gemini')
            ->where('ai_settings.ai_image_provider.value', 'gemini')
            ->where('ai_settings.gemini_api_key.has_value', true)
        );
    }

    public function test_profile_page_returns_analytics_settings_props_to_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.profile'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('analytics_settings')
            ->has('analytics_settings.analytics_google_analytics_code')
            ->has('analytics_settings.analytics_google_search_console_code')
        );
    }

    public function test_analytics_settings_can_be_updated_from_profile(): void
    {
        $gaCode = "<!-- Google tag (gtag.js) -->\n<script>console.log('profile-test-ga');</script>";
        $gscCode = '<meta name="google-site-verification" content="profile-test-gsc-1234" />';

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.analytics'), [
                'analytics_google_analytics_code' => $gaCode,
                'analytics_google_search_console_code' => $gscCode,
            ]);

        $response->assertRedirect();
        $this->assertEquals($gaCode, Setting::get('analytics_google_analytics_code'));
        $this->assertEquals($gscCode, Setting::get('analytics_google_search_console_code'));
    }

    public function test_vat_rate_setting_can_be_updated_from_profile(): void
    {
        // Update vat_rate via profile API keys endpoint
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.api-keys'), [
                'vat_rate' => 0.15,
            ]);

        $response->assertRedirect();
        $this->assertEquals('0.15', Setting::get('vat_rate'));

        // Manually run bootstrapping to simulate request cycle reload of config
        $appServiceProvider = new AppServiceProvider(app());
        $method = new \ReflectionMethod($appServiceProvider, 'bootstrapRuntimeConfiguration');
        $method->setAccessible(true);
        $method->invoke($appServiceProvider);

        $this->assertEquals(0.15, config('billing.vat_rate'));
    }

    public function test_mp_sandbox_can_be_updated_from_profile_with_boolean_payload(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.profile.api-keys'), [
                'mp_sandbox' => false,
            ]);

        $response->assertRedirect();
        $this->assertSame('false', Setting::get('mp_sandbox'));
    }
}
