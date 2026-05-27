<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
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
                'email' => 'updated@feeto.cl',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Updated Name', 'email' => 'updated@feeto.cl']);
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
}
