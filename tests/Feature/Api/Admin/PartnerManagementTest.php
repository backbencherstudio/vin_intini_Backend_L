<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Industry;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PartnerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Master',
            'email' => 'admin@gmail.com',
            'is_verified' => true,
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_non_admin_cannot_access_partners_api(): void
    {
        $regularUser = User::factory()->create(['email' => 'user@test.com', 'is_verified' => true]);
        $regularUser->assignRole($this->userRole);

        $response = $this->actingAs($regularUser, 'api')
            ->getJson('/api/admin/partners');

        $response->assertForbidden();
    }

    public function test_admin_can_list_partners_with_summary_and_owner(): void
    {
        $owner = User::factory()->create([
            'first_name' => 'Rachel',
            'last_name' => 'White',
            'email' => 'rachel.white@example.com',
        ]);
        $owner->assignRole($this->userRole);
        UserProfile::create(['user_id' => $owner->id, 'country' => 'United States']);

        $industry = Industry::create([
            'name' => 'MindTech Biotech Lab',
            'slug' => 'mindtech-biotech-lab',
            'industry' => 'Biotechnology',
            'website' => 'https://mindtech.example.com',
            'status' => 'active',
            'created_by' => $owner->id,
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/partners');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'summary' => [
                    'total_partners',
                    'active_partners',
                    'paused_partners',
                ],
                'data' => [
                    '*' => [
                        'sl_no',
                        'id',
                        'name',
                        'slug',
                        'industry',
                        'industry_type',
                        'logo_url',
                        'website',
                        'status',
                        'is_paused',
                        'owner' => [
                            'id',
                            'full_name',
                            'email',
                        ],
                    ],
                ],
                'pagination',
            ]);

        $response->assertJsonPath('data.0.id', $industry->id);
        $response->assertJsonPath('data.0.name', 'MindTech Biotech Lab');
        $response->assertJsonPath('data.0.owner.full_name', 'Rachel White');
        $response->assertJsonPath('data.0.status', 'active');
        $response->assertJsonPath('data.0.is_paused', false);
    }

    public function test_admin_can_filter_partners_by_status_and_industry_type(): void
    {
        $owner1 = User::factory()->create();
        $owner1->assignRole($this->userRole);

        $owner2 = User::factory()->create();
        $owner2->assignRole($this->userRole);

        Industry::create([
            'name' => 'Active Biotech Corp',
            'slug' => 'active-biotech-corp',
            'industry' => 'Biotechnology',
            'status' => 'active',
            'created_by' => $owner1->id,
        ]);

        Industry::create([
            'name' => 'Paused Pharma Group',
            'slug' => 'paused-pharma-group',
            'industry' => 'Psychotropics',
            'status' => 'paused',
            'paused_at' => now(),
            'created_by' => $owner2->id,
        ]);

        // Filter by paused status
        $resPaused = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/partners?status=paused');

        $resPaused->assertOk();
        $resPaused->assertJsonCount(1, 'data');
        $resPaused->assertJsonPath('data.0.name', 'Paused Pharma Group');
        $resPaused->assertJsonPath('data.0.is_paused', true);

        // Filter by industry type
        $resBio = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/partners?industry=Biotechnology');

        $resBio->assertOk();
        $resBio->assertJsonCount(1, 'data');
        $resBio->assertJsonPath('data.0.name', 'Active Biotech Corp');
    }

    public function test_admin_can_toggle_pause_partner(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole($this->userRole);

        $industry = Industry::create([
            'name' => 'BioGen Dynamics',
            'slug' => 'biogen-dynamics',
            'industry' => 'Biotechnology',
            'status' => 'active',
            'created_by' => $owner->id,
        ]);

        // Pause
        $pauseRes = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/partners/{$industry->id}/toggle-pause");

        $pauseRes->assertOk();
        $pauseRes->assertJsonPath('data.status', 'paused');
        $pauseRes->assertJsonPath('data.is_paused', true);
        $this->assertDatabaseHas('industries', ['id' => $industry->id, 'status' => 'paused']);

        // Resume / Unpause
        $resumeRes = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/partners/{$industry->id}/toggle-pause");

        $resumeRes->assertOk();
        $resumeRes->assertJsonPath('data.status', 'active');
        $resumeRes->assertJsonPath('data.is_paused', false);
        $this->assertDatabaseHas('industries', ['id' => $industry->id, 'status' => 'active']);
    }
}
