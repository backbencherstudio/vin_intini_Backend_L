<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Industry;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PublicationManagementTest extends TestCase
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

    public function test_non_admin_cannot_access_publication_management(): void
    {
        $regularUser = User::factory()->create(['email' => 'user@test.com', 'is_verified' => true]);
        $regularUser->assignRole($this->userRole);

        $response = $this->actingAs($regularUser, 'api')
            ->getJson('/api/admin/publications');

        $response->assertForbidden();
    }

    public function test_admin_can_list_publications_with_overview_and_filters(): void
    {
        $authorUser = User::factory()->create(['first_name' => 'Albert', 'last_name' => 'Einstein']);
        $authorUser->assignRole($this->userRole);

        $industry = Industry::create([
            'created_by' => $authorUser->id,
            'name' => 'Quantum Neuro Labs',
            'slug' => 'quantum-neuro-labs',
            'industry' => 'biotechnology',
        ]);

        $pub1 = Publication::create([
            'creator_id' => $authorUser->id,
            'industry_id' => $industry->id,
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Neural Dynamics in Decision Making',
            'slug' => 'neural-dynamics-decision-making-1',
            'authors' => ['Albert Einstein', 'Niels Bohr'],
            'abstract' => 'In-depth study on computational neuroscience and synaptogenesis.',
            'website_url' => 'https://doi.org/10.1016/j.neuron.2026.01',
            'status' => 'active',
            'views_count' => 120,
            'likes_count' => 35,
        ]);

        $authorUser2 = User::factory()->create(['first_name' => 'Sigmund', 'last_name' => 'Freud']);
        $authorUser2->assignRole($this->userRole);

        $pub2 = Publication::create([
            'creator_id' => $authorUser2->id,
            'network_type' => 'psychology',
            'publication_type' => 'freelance',
            'title' => 'Behavioral Therapy Across Modern Age',
            'slug' => 'behavioral-therapy-modern-age-2',
            'authors' => ['Sigmund Freud'],
            'abstract' => 'Clinical analysis of behavioral therapy effectiveness.',
            'website_url' => 'https://doi.org/10.1016/j.psych.2026.02',
            'status' => 'inactive',
            'views_count' => 10,
            'likes_count' => 2,
        ]);

        // 1. General list
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/publications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('overview.total_publications', 2)
            ->assertJsonPath('overview.active_publications', 1)
            ->assertJsonPath('overview.inactive_publications', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonCount(2, 'data');

        // 2. Filter by status=active
        $activeResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/publications?status=active');

        $activeResponse->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $pub1->id)
            ->assertJsonPath('data.0.status', 'active');

        // 3. Filter by network_type=psychology
        $psychResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/publications?network_type=psychology');

        $psychResponse->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $pub2->id);

        // 4. Search by keyword
        $searchResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/publications?search=Einstein');

        $searchResponse->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $pub1->id);
    }

    public function test_admin_can_view_publication_details(): void
    {
        $creator = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'marie@curie.org',
            'title' => 'Lead Researcher',
        ]);

        $publication = Publication::create([
            'creator_id' => $creator->id,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'Radiation and Stress Response',
            'slug' => 'radiation-and-stress-response',
            'authors' => ['Marie Curie', 'Pierre Curie'],
            'abstract' => 'Detailed experimental research paper...',
            'website_url' => 'https://doi.org/10.1000/182',
            'status' => 'active',
            'views_count' => 200,
            'likes_count' => 45,
        ]);

        // Fetch by numeric id
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/publications/{$publication->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $publication->id)
            ->assertJsonPath('data.title', 'Radiation and Stress Response')
            ->assertJsonPath('data.creator.email', 'marie@curie.org')
            ->assertJsonPath('data.creator.full_name', 'Marie Curie')
            ->assertJsonPath('data.authors.0', 'Marie Curie')
            ->assertJsonPath('data.views_count', 200)
            ->assertJsonPath('data.likes_count', 45);

        // Fetch by publication_id string
        $codeResponse = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/publications/{$publication->publication_id}");

        $codeResponse->assertOk()
            ->assertJsonPath('data.id', $publication->id);
    }

    public function test_admin_can_toggle_publication_status(): void
    {
        $publication = Publication::create([
            'network_type' => 'psychology',
            'publication_type' => 'other',
            'title' => 'Sleep Pattern Analysis',
            'slug' => 'sleep-pattern-analysis',
            'abstract' => 'REM sleep evaluation.',
            'website_url' => 'https://example.com/pub',
            'status' => 'active',
        ]);

        // Toggle from active -> inactive
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/publications/{$publication->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'status' => 'inactive',
        ]);

        // Toggle from inactive -> active
        $response2 = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/publications/{$publication->id}/toggle-status");

        $response2->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_soft_delete_publication(): void
    {
        $publication = Publication::create([
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Paper To Delete',
            'slug' => 'paper-to-delete',
            'abstract' => 'Abstract for deletion test.',
            'website_url' => 'https://delete.com',
            'status' => 'active',
        ]);

        // Soft delete
        $deleteResponse = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/publications/{$publication->id}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Publication soft-deleted successfully.');

        $this->assertSoftDeleted('publications', ['id' => $publication->id]);
    }

    public function test_admin_can_force_delete_publication(): void
    {
        $publication = Publication::create([
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Permanent Delete Paper',
            'slug' => 'permanent-delete-paper',
            'abstract' => 'To be permanently purged.',
            'website_url' => 'https://purge.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/publications/{$publication->id}?force=true");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Publication permanently deleted successfully.');

        $this->assertDatabaseMissing('publications', ['id' => $publication->id]);
    }
}
