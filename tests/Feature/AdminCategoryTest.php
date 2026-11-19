<?php

declare(strict_types=1);

use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->adminUser = User::factory()->create(['is_verified' => true]);
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can fetch psychology sections with search and type filter', function (): void {
    $sec1 = IndustrySections::create([
        'name' => 'Diagnostic Brain Imaging',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
    ]);

    $sec2 = IndustrySections::create([
        'name' => 'Mood Psychotropics',
        'industry_type' => 'psychotropics',
        'network_type' => 'psychology',
    ]);

    IndustryCategory::create([
        'section_id' => $sec1->id,
        'category_name' => 'fMRI Scanners',
    ]);

    // Test Biotechnology filter
    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/categories/psychology?type=biotechnology');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $data = $response->json('data.sections');
    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('Diagnostic Brain Imaging');
    expect($data[0]['categories_count'])->toBe(1);

    // Test Search filter
    $searchResponse = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/categories/psychology?search=Mood');

    $searchResponse->assertStatus(200);
    $searchData = $searchResponse->json('data.sections');
    expect(count($searchData))->toBe(1);
    expect($searchData[0]['name'])->toBe('Mood Psychotropics');
});

test('admin can create and update a psychology section', function (): void {
    $createResponse = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/categories/psychology/create', [
            'name' => 'Cognitive Lab Gear',
            'industry_type' => 'biotechnology',
        ]);

    $createResponse->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Cognitive Lab Gear',
                'industry_type' => 'biotechnology',
            ],
        ]);

    $sectionId = $createResponse->json('data.id');

    $updateResponse = $this->actingAs($this->adminUser, 'api')
        ->putJson("/api/admin/categories/psychology/update/{$sectionId}", [
            'name' => 'Advanced Cognitive Lab Gear',
            'industry_type' => 'biotechnology',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Advanced Cognitive Lab Gear',
            ],
        ]);
});

test('admin can create, update, and delete a subcategory', function (): void {
    $section = IndustrySections::create([
        'name' => 'Behavioral Lab',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
    ]);

    // Create subcategory
    $createCatResponse = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/categories/sub-category/create', [
            'section_id' => $section->id,
            'category_name' => 'Eye Trackers',
        ]);

    $createCatResponse->assertStatus(201);
    $catId = $createCatResponse->json('data.id');

    // Duplicate prevention
    $dupResponse = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/categories/sub-category/create', [
            'section_id' => $section->id,
            'category_name' => 'Eye Trackers',
        ]);
    $dupResponse->assertStatus(422);

    // Update subcategory
    $updateCatResponse = $this->actingAs($this->adminUser, 'api')
        ->putJson("/api/admin/categories/sub-category/update/{$catId}", [
            'category_name' => 'High-Speed Eye Trackers',
        ]);
    $updateCatResponse->assertStatus(200);

    // Delete subcategory
    $delCatResponse = $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/categories/sub-category/delete/{$catId}");
    $delCatResponse->assertStatus(200);
});

test('active products protect subcategory from accidental deletion', function (): void {
    $section = IndustrySections::create([
        'name' => 'Clinical Apparatus',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
    ]);

    $category = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'EEG Headsets',
    ]);

    $industry = \App\Models\Industry::create([
        'name' => 'NeuroTech Corp',
        'slug' => 'neurotech-corp',
        'industry' => 'biotechnology',
        'created_by' => $this->adminUser->id,
    ]);

    IndustryProduct::create([
        'product_id' => 'PRD-12345',
        'creator_id' => $this->adminUser->id,
        'industry_id' => $industry->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $section->id,
        'category_id' => $category->id,
        'product_name' => 'Pro EEG 64-Channel',
        'slug' => 'pro-eeg-64-channel',
        'description' => 'Test EEG description',
        'status' => 'approved',
    ]);

    // Try deleting category with linked products
    $response = $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/categories/sub-category/delete/{$category->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    $this->assertDatabaseHas('industry_categories', [
        'id' => $category->id,
    ]);
});

test('admin can reorder and toggle status of subcategories', function (): void {
    $section = IndustrySections::create([
        'name' => 'Diagnostics',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
    ]);

    $c1 = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'First Cat',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $c2 = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'Second Cat',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    // Reorder: c2 first, c1 second
    $reorderResponse = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/categories/sub-category/reorder', [
            'category_ids' => [$c2->id, $c1->id],
        ]);

    $reorderResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(IndustryCategory::find($c2->id)->sort_order)->toBe(1);
    expect(IndustryCategory::find($c1->id)->sort_order)->toBe(2);

    // Toggle active status
    $toggleResponse = $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/categories/sub-category/toggle-status/{$c1->id}", [
            'is_active' => false,
        ]);

    $toggleResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(IndustryCategory::find($c1->id)->is_active)->toBeFalse();
});

test('inactive subcategory hides subcategory and its products from public frontend api', function (): void {
    $section = IndustrySections::create([
        'name' => 'Brain Devices',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
    ]);

    $activeCat = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'Active Scanners',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $hiddenCat = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'Hidden Gadgets',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $industry = \App\Models\Industry::create([
        'name' => 'NeuroTech Corp',
        'slug' => 'neurotech-corp',
        'industry' => 'biotechnology',
        'created_by' => $this->adminUser->id,
    ]);

    $prodInActiveCat = IndustryProduct::create([
        'product_id' => 'PRD-101',
        'creator_id' => $this->adminUser->id,
        'industry_id' => $industry->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $section->id,
        'category_id' => $activeCat->id,
        'product_name' => 'Visible Scanner',
        'slug' => 'visible-scanner',
        'description' => 'Test visible',
        'status' => 'active',
    ]);

    $prodInHiddenCat = IndustryProduct::create([
        'product_id' => 'PRD-102',
        'creator_id' => $this->adminUser->id,
        'industry_id' => $industry->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $section->id,
        'category_id' => $hiddenCat->id,
        'product_name' => 'Hidden Scanner',
        'slug' => 'hidden-scanner',
        'description' => 'Test hidden',
        'status' => 'active',
    ]);

    $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    $normalUser = User::factory()->create(['is_verified' => true]);
    $normalUser->assignRole($userRole);
    \App\Models\UserProfile::create(['user_id' => $normalUser->id]);

    // 1. Verify public product feed only shows products from active categories
    $feedResponse = $this->actingAs($normalUser, 'api')
        ->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology');
    $feedResponse->assertStatus(200);
    $feedData = $feedResponse->json('data');
    expect(count($feedData))->toBe(1);
    expect($feedData[0]['total_products'])->toBe(1);
    expect($feedData[0]['products'][0]['id'])->toBe($prodInActiveCat->id);

    // 2. Verify subcategories list in the feed only contains active subcategories
    $feedCats = $feedData[0]['categories'];
    expect(count($feedCats))->toBe(1);
    expect($feedCats[0]['id'])->toBe($activeCat->id);

    // 3. Verify public product details 404s for product in inactive category
    $showHiddenResponse = $this->actingAs($normalUser, 'api')
        ->getJson("/api/industry/products/{$prodInHiddenCat->id}/details");
    $showHiddenResponse->assertStatus(404);

    // 4. Verify admin CAN still see the product in admin products api
    $adminShowResponse = $this->actingAs($this->adminUser, 'api')
        ->getJson("/api/admin/products/{$prodInHiddenCat->id}");
    $adminShowResponse->assertStatus(200);
});

test('admin can reorder sections and toggle section status, affecting public feed', function (): void {
    $secA = IndustrySections::create([
        'name' => 'Section A',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $secB = IndustrySections::create([
        'name' => 'Section B',
        'industry_type' => 'biotechnology',
        'network_type' => 'psychology',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $industry = \App\Models\Industry::create([
        'name' => 'BioTech Hub',
        'slug' => 'biotech-hub',
        'industry' => 'biotechnology',
        'created_by' => $this->adminUser->id,
    ]);

    $catA = IndustryCategory::create([
        'section_id' => $secA->id,
        'category_name' => 'Cat A',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $catB = IndustryCategory::create([
        'section_id' => $secB->id,
        'category_name' => 'Cat B',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $prodA = IndustryProduct::create([
        'product_id' => 'PRD-SEC-A',
        'creator_id' => $this->adminUser->id,
        'industry_id' => $industry->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $secA->id,
        'category_id' => $catA->id,
        'product_name' => 'Product in Sec A',
        'slug' => 'prod-sec-a',
        'description' => 'Test A',
        'status' => 'active',
    ]);

    $prodB = IndustryProduct::create([
        'product_id' => 'PRD-SEC-B',
        'creator_id' => $this->adminUser->id,
        'industry_id' => $industry->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $secB->id,
        'category_id' => $catB->id,
        'product_name' => 'Product in Sec B',
        'slug' => 'prod-sec-b',
        'description' => 'Test B',
        'status' => 'active',
    ]);

    $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    $normalUser = User::factory()->create(['is_verified' => true]);
    $normalUser->assignRole($userRole);
    \App\Models\UserProfile::create(['user_id' => $normalUser->id]);

    // 1. Reorder sections so Sec B is first [secB, secA]
    $reorderResp = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/categories/section/reorder', [
            'section_ids' => [$secB->id, $secA->id],
        ]);
    $reorderResp->assertStatus(200);

    // Verify admin psychology list reflects new order
    $adminList = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/categories/psychology?type=biotechnology');
    $adminList->assertStatus(200);
    $adminSecs = $adminList->json('data.sections');
    expect($adminSecs[0]['id'])->toBe($secB->id);
    expect($adminSecs[1]['id'])->toBe($secA->id);

    // Verify public product feed reflects new order
    $feedResp = $this->actingAs($normalUser, 'api')
        ->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology');
    $feedResp->assertStatus(200);
    $feedSecs = $feedResp->json('data');
    expect(count($feedSecs))->toBe(2);
    expect($feedSecs[0]['section_id'])->toBe($secB->id);
    expect($feedSecs[1]['section_id'])->toBe($secA->id);

    // 2. Toggle status of Section B to Inactive
    $toggleResp = $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/categories/section/toggle-status/{$secB->id}", [
            'is_active' => false,
        ]);
    $toggleResp->assertStatus(200);
    expect($toggleResp->json('data.is_active'))->toBeFalse();

    // Verify public product feed now ONLY shows Sec A (Sec B and its product are hidden!)
    $feedAfterHide = $this->actingAs($normalUser, 'api')
        ->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology');
    $feedAfterHide->assertStatus(200);
    $feedAfterSecs = $feedAfterHide->json('data');
    expect(count($feedAfterSecs))->toBe(1);
    expect($feedAfterSecs[0]['section_id'])->toBe($secA->id);

    // Verify public dropdown sections only includes Sec A
    $dropdownResp = $this->actingAs($normalUser, 'api')
        ->getJson('/api/industry/products/sections?network_type=psychology&industry_type=biotechnology');
    $dropdownResp->assertStatus(200);
    $dropdownSecs = $dropdownResp->json('data');
    expect(count($dropdownSecs))->toBe(1);
    expect($dropdownSecs[0]['id'])->toBe($secA->id);

    // Verify public product details for prodB (in inactive section) returns 404
    $showResp = $this->actingAs($normalUser, 'api')
        ->getJson("/api/industry/products/{$prodB->id}/details");
    $showResp->assertStatus(404);

    // Toggle Section B back to Active
    $toggleBackResp = $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/categories/section/toggle-status/{$secB->id}", [
            'is_active' => true,
        ]);
    $toggleBackResp->assertStatus(200);
    expect($toggleBackResp->json('data.is_active'))->toBeTrue();
});
