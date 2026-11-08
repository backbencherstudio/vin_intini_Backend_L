<?php

declare(strict_types=1);

use App\Models\ContactUs;
use App\Models\Post;
use App\Models\Publication;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

    $this->adminUser = User::factory()->create(['is_verified' => true]);
    $this->adminUser->assignRole([$this->userRole, $this->adminRole]);
    UserProfile::create(['user_id' => $this->adminUser->id]);

    $this->regularUser = User::factory()->create(['is_verified' => true]);
    $this->regularUser->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->regularUser->id]);
});

test('unauthenticated guest cannot access admin dashboard', function (): void {
    $this->getJson('/api/admin/dashboard')->assertStatus(401);
});

test('regular non-admin user cannot access admin dashboard', function (): void {
    $this->actingAs($this->regularUser, 'api')
        ->getJson('/api/admin/dashboard')
        ->assertStatus(403);
});

test('admin can retrieve dashboard composite metrics', function (): void {
    // Create test records
    Post::create([
        'user_id' => $this->adminUser->id,
        'title' => 'Test Post',
        'content' => 'Test Content',
        'visibility' => 'public',
    ]);

    Publication::create([
        'creator_id' => $this->adminUser->id,
        'title' => 'Research on Cognition',
        'slug' => 'research-on-cognition',
        'abstract' => 'Sample abstract for cognition research',
        'website_url' => 'https://example.com/publication',
        'network_type' => 'neuroscience',
        'publication_type' => 'article',
        'status' => 'active',
    ]);

    ContactUs::create([
        'name' => 'Alice Walker',
        'email' => 'alice@example.com',
        'phone' => '+123456789',
        'subject' => 'Partnership Inquiry',
        'message' => 'Hello team, interested in partnering.',
    ]);

    Transaction::create([
        'user_id' => $this->adminUser->id,
        'amount' => 99.00,
        'currency' => 'usd',
        'status' => 'succeeded',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'status_cards',
                'revenue_report' => [
                    'totalRevenue',
                    'growthRate',
                    'direction',
                    'dateRange',
                    'chartData',
                ],
                'user_growth' => [
                    'totalUsers',
                    'growthRate',
                    'direction',
                    'chartData',
                ],
                'total_revenue_breakdown' => [
                    'total',
                    'items',
                ],
                'pending_approvals',
                'recent_activities',
            ],
        ]);

    $data = $response->json('data');
    expect($data['status_cards'])->toHaveCount(5);
    expect($data['pending_approvals'])->toHaveCount(4);
    expect($data['recent_activities'])->not->toBeEmpty();
});
