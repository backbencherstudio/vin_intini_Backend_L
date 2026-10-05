<?php

namespace Tests\Feature\Api\Admin;

use App\Models\IndustryCategory;
use App\Models\IndustrySections;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    public function test_get_psychology_sections(): void
    {
        $section = IndustrySections::create([
            'name' => 'Psych Section 1',
            'industry_type' => 'biotechnology',
            'network_type' => 'psychology',
        ]);

        IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Research',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/categories/psychology');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.network', 'psychology')
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.name', 'Psych Section 1')
            ->assertJsonPath('data.sections.0.categories.0.category_name', 'Research');
    }

    public function test_get_psychology_validates_type_filter(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/categories/psychology?type=invalid_type')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_psychology_section_does_not_create_all_category(): void
    {
        $payload = [
            'name' => 'New Psych Section',
            'industry_type' => 'biotechnology',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories/psychology/create', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Psych Section')
            ->assertJsonPath('data.network_type', 'psychology')
            ->assertJsonCount(0, 'data.categories');

        $sectionId = $response->json('data.id');

        $this->assertDatabaseHas('industry_sections', [
            'id' => $sectionId,
            'name' => 'New Psych Section',
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
        ]);

        $this->assertDatabaseMissing('industry_categories', [
            'section_id' => $sectionId,
            'category_name' => 'All',
        ]);
    }

    public function test_update_psychology_section(): void
    {
        $section = IndustrySections::create([
            'name' => 'Old Name',
            'industry_type' => 'biotechnology',
            'network_type' => 'psychology',
        ]);

        $payload = [
            'name' => 'Updated Name',
            'industry_type' => 'psychotropics',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/categories/psychology/update/{$section->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.industry_type', 'psychotropics');

        $this->assertDatabaseHas('industry_sections', [
            'id' => $section->id,
            'name' => 'Updated Name',
            'industry_type' => 'psychotropics',
        ]);
    }

    public function test_get_neuroscience_sections(): void
    {
        $section = IndustrySections::create([
            'name' => 'Neuro Section 1',
            'industry_type' => 'biotechnology',
            'network_type' => 'neuroscience',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/categories/neuroscience');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.network', 'neuroscience')
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.name', 'Neuro Section 1');
    }

    public function test_store_neuroscience_section_does_not_create_all_category(): void
    {
        $payload = [
            'name' => 'New Neuro Section',
            'industry_type' => 'psychotropics',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories/neuroscience/create', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Neuro Section')
            ->assertJsonPath('data.network_type', 'neuroscience')
            ->assertJsonCount(0, 'data.categories');

        $sectionId = $response->json('data.id');

        $this->assertDatabaseMissing('industry_categories', [
            'section_id' => $sectionId,
            'category_name' => 'All',
        ]);
    }

    public function test_update_neuroscience_section(): void
    {
        $section = IndustrySections::create([
            'name' => 'Neuro Old',
            'industry_type' => 'biotechnology',
            'network_type' => 'neuroscience',
        ]);

        $payload = [
            'name' => 'Neuro Updated',
            'industry_type' => 'psychotropics',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/categories/neuroscience/update/{$section->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Neuro Updated');
    }

    public function test_destroy_section(): void
    {
        $section = IndustrySections::create([
            'name' => 'To Delete',
            'industry_type' => 'biotechnology',
            'network_type' => 'psychology',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Custom Category',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/categories/section/delete/{$section->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('industry_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('industry_categories', ['id' => $category->id]);
    }

    public function test_sub_category_crud_with_both_routes(): void
    {
        $section = IndustrySections::create([
            'name' => 'Section For SubCat',
            'industry_type' => 'biotechnology',
            'network_type' => 'psychology',
        ]);

        // Create SubCategory using correct spelling sub-category
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories/sub-category/create', [
                'category_name' => 'SubCat 1',
                'section_id' => $section->id,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category_name', 'SubCat 1');

        $subCatId = $createResponse->json('data.id');

        // Update SubCategory
        $updateResponse = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/categories/sub-category/update/{$subCatId}", [
                'category_name' => 'SubCat 1 Updated',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category_name', 'SubCat 1 Updated');

        // Delete SubCategory
        $deleteResponse = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/categories/sub-category/delete/{$subCatId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('industry_categories', ['id' => $subCatId]);
    }

    public function test_admin_can_manually_create_and_update_sub_category_named_all(): void
    {
        $section = IndustrySections::create([
            'name' => 'Section For All Check',
            'industry_type' => 'biotechnology',
            'network_type' => 'psychology',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories/sub-category/create', [
                'category_name' => 'All',
                'section_id' => $section->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category_name', 'All');

        $this->assertDatabaseHas('industry_categories', [
            'category_name' => 'All',
            'section_id' => $section->id,
        ]);

        $categoryId = $response->json('data.id');

        $updateResponse = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/categories/sub-category/update/{$categoryId}", [
                'category_name' => 'All Updated',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category_name', 'All Updated');
    }

    public function test_guest_cannot_access_routes(): void
    {
        $this->getJson('/api/admin/categories/psychology')->assertStatus(401);
        $this->postJson('/api/admin/categories/psychology/create', [])->assertStatus(401);
    }

    public function test_non_admin_cannot_access_routes(): void
    {
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $regularUser = User::factory()->create();
        $regularUser->assignRole($userRole);

        $this->actingAs($regularUser, 'api')
            ->getJson('/api/admin/categories/psychology')
            ->assertStatus(403);
    }

    public function test_validation_fails_on_invalid_data(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories/psychology/create', [
                'name' => '',
                'industry_type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'industry_type']);
    }

    public function test_update_returns_404_for_non_existent_section(): void
    {
        $this->actingAs($this->admin, 'api')
            ->putJson('/api/admin/categories/psychology/update/999999', [
                'name' => 'Valid Name',
                'industry_type' => 'biotechnology',
            ])
            ->assertStatus(404);
    }
}
