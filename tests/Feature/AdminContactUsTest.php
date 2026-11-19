<?php

use App\Models\ContactUs;
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

    $this->regularUser = User::factory()->create(['is_verified' => true]);
    $this->regularUser->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->regularUser->id]);
});

test('guest cannot access admin contact-us endpoints', function (): void {
    $this->getJson('/api/admin/contact-us')->assertStatus(401);
});

test('non-admin user is forbidden from admin contact-us endpoints', function (): void {
    $this->actingAs($this->regularUser, 'api')
        ->getJson('/api/admin/contact-us')
        ->assertStatus(403);
});

test('admin can list contact messages with summary and status filtering', function (): void {
    ContactUs::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Partnership inquiry',
        'message' => 'Hello, I want to partner with MindUnite.',
        'status' => 'unread',
    ]);

    ContactUs::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'subject' => 'Technical question',
        'message' => 'How does the billing work?',
        'status' => 'replied',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/contact-us');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect($response->json('summary.total_messages'))->toBe(2);
    expect($response->json('summary.unread_messages'))->toBe(1);
    expect($response->json('summary.replied_messages'))->toBe(1);

    // Filter by unread
    $unreadRes = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/contact-us?status=unread');

    $unreadRes->assertStatus(200);
    expect(count($unreadRes->json('data')))->toBe(1);
    expect($unreadRes->json('data.0.name'))->toBe('John Doe');
    expect($unreadRes->json('data.0.created_at_formatted'))->not->toBeNull();
});

test('viewing an unread message auto-marks it as read', function (): void {
    $msg = ContactUs::create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'subject' => 'Feature request',
        'message' => 'Please add dark mode.',
        'status' => 'unread',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson("/api/admin/contact-us/{$msg->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $msg->id,
                'status' => 'read',
            ],
        ]);

    $this->assertDatabaseHas('contact_us', [
        'id' => $msg->id,
        'status' => 'read',
    ]);
});

test('admin can mark message status to replied', function (): void {
    $msg = ContactUs::create([
        'name' => 'Bob Builder',
        'email' => 'bob@example.com',
        'subject' => 'Can we fix it?',
        'message' => 'Yes we can!',
        'status' => 'read',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/contact-us/{$msg->id}/status", [
            'status' => 'replied',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'replied',
            ],
        ]);

    $this->assertDatabaseHas('contact_us', [
        'id' => $msg->id,
        'status' => 'replied',
    ]);
});

test('admin can delete a spam contact message', function (): void {
    $msg = ContactUs::create([
        'name' => 'Spam Bot',
        'email' => 'bot@spammer.net',
        'subject' => 'Buy cheap stuff',
        'message' => 'Click this link immediately!',
        'status' => 'unread',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/contact-us/{$msg->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseMissing('contact_us', [
        'id' => $msg->id,
    ]);
});

test('admin can update internal note for a contact message', function (): void {
    $msg = ContactUs::create([
        'name' => 'Sara Smith',
        'email' => 'sara@example.com',
        'subject' => 'Partnership Inquiry',
        'message' => 'We want to collaborate.',
        'status' => 'read',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/contact-us/{$msg->id}/note", [
            'admin_note' => 'Followed up via phone on Thursday, waiting for proposal.',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $msg->id,
                'admin_note' => 'Followed up via phone on Thursday, waiting for proposal.',
            ],
        ]);

    $this->assertDatabaseHas('contact_us', [
        'id' => $msg->id,
        'admin_note' => 'Followed up via phone on Thursday, waiting for proposal.',
    ]);
});
