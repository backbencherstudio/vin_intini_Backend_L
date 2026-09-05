<?php

namespace Tests\Feature\Api\Admin;

use App\Models\IntegrationSetting;
use App\Models\User;
use App\Services\IntegrationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IntegrationSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    public function test_admin_can_view_integration_settings(): void
    {
        IntegrationSetting::create(['key' => 'stripe_secret_key', 'value' => 'sk_live_1234567890abcdef', 'section' => 'Stripe']);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/integrations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Stripe.0.key', 'stripe_secret_key')
            ->assertJsonPath('data.Stripe.0.value', 'sk_l••••••••cdef');
    }

    public function test_unauthorized_users_cannot_view_integration_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/admin/integrations')
            ->assertForbidden();
    }

    public function test_admin_can_update_integration_settings(): void
    {
        IntegrationSetting::create(['key' => 'stripe_secret_key', 'value' => 'old', 'section' => 'Stripe']);
        IntegrationSetting::create(['key' => 'google_client_id', 'value' => 'old-id', 'section' => 'Google OAuth']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/integrations', [
                'settings' => [
                    'stripe_secret_key' => 'sk_live_new',
                    'google_client_id' => 'new-id',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('integration_settings', ['key' => 'stripe_secret_key', 'value' => 'sk_live_new']);
        $this->assertDatabaseHas('integration_settings', ['key' => 'google_client_id', 'value' => 'new-id']);
    }

    public function test_blank_secret_value_keeps_existing_value(): void
    {
        IntegrationSetting::create(['key' => 'stripe_secret_key', 'value' => 'sk_live_keep', 'section' => 'Stripe']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/integrations', [
                'settings' => [
                    'stripe_secret_key' => '',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('integration_settings', ['key' => 'stripe_secret_key', 'value' => 'sk_live_keep']);
    }

    public function test_non_secret_blank_value_clears_the_setting(): void
    {
        IntegrationSetting::create(['key' => 'google_redirect_uri', 'value' => 'http://old', 'section' => 'Google OAuth']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/integrations', [
                'settings' => [
                    'google_redirect_uri' => '',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('integration_settings', ['key' => 'google_redirect_uri', 'value' => '']);
    }

    public function test_stored_settings_override_config_at_boot(): void
    {
        IntegrationSetting::create(['key' => 'google_client_id', 'value' => 'db-client-id', 'section' => 'Google OAuth']);

        Cache::forget('integration_settings');

        app(IntegrationSettingsService::class)->applyOverrides();

        $this->assertEquals('db-client-id', config('services.google.client_id'));
    }

    public function test_revenuecat_settings_override_config_at_boot(): void
    {
        IntegrationSetting::create(['key' => 'revenuecat_app_id', 'value' => 'app_db_123', 'section' => 'RevenueCat']);
        IntegrationSetting::create(['key' => 'revenuecat_api_key', 'value' => 'rc_live_db', 'section' => 'RevenueCat']);

        Cache::forget('integration_settings');

        app(IntegrationSettingsService::class)->applyOverrides();

        $this->assertEquals('app_db_123', config('revenuecat.app_id'));
        $this->assertEquals('rc_live_db', config('revenuecat.api_key'));
    }

    public function test_facebook_is_fully_removed(): void
    {
        $reflection = new \ReflectionClass(IntegrationSettingsService::class);
        $map = $reflection->getConstant('CONFIG_MAP');

        foreach (array_keys($map) as $key) {
            $this->assertStringNotContainsString('facebook', $key);
        }

        $this->assertDatabaseMissing('integration_settings', ['key' => 'facebook_client_id']);
        $this->assertDatabaseMissing('integration_settings', ['key' => 'facebook_client_secret']);
    }
}
