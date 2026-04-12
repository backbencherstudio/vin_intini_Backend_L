<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Experience;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserExperienceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_experiences_are_grouped_by_company(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-12 00:00:00'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        $oldCompany = Company::factory()->create();
        $newCompany = Company::factory()->create();

        Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $oldCompany->id,
            'employment_type' => 'Full-time',
            'start_date' => Carbon::parse('2023-03-01'),
            'end_date' => Carbon::parse('2024-03-01'),
            'is_current' => false,
        ]);

        Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $newCompany->id,
            'employment_type' => 'Full-time',
            'start_date' => Carbon::parse('2025-03-01'),
            'end_date' => null,
            'is_current' => true,
        ]);

        Experience::factory()->create([
            'user_id' => $user->id,
            'company_id' => $oldCompany->id,
            'employment_type' => 'Part-time',
            'start_date' => Carbon::parse('2022-03-01'),
            'end_date' => Carbon::parse('2022-09-01'),
            'is_current' => false,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/experience/list');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.company.id', $newCompany->id)
            ->assertJsonPath('data.1.company.id', $oldCompany->id)
            ->assertJsonPath('data.0.job_type', 'Full-time')
            ->assertJsonPath('data.0.period', '1 year 1 month')
            ->assertJsonPath('data.0.summary', 'Full-time • 1 year 1 month')
            ->assertJsonCount(1, 'data.0.experiences')
            ->assertJsonCount(2, 'data.1.experiences')
            ->assertJsonMissingPath('data.0.experiences.0.company')
            ->assertJsonMissingPath('data.1.experiences.0.company')

            ->assertJsonPath('data.0.experiences.0.company_id', $newCompany->id)
            ->assertJsonPath('data.0.experiences.0.start_date', 'Mar 2025')
            ->assertJsonPath('data.0.experiences.0.starting_date', 'Mar 2025')
            ->assertJsonPath('data.0.experiences.0.end_date', 'Present')
            ->assertJsonPath('data.0.experiences.0.ending_date', 'Present')
            ->assertJsonPath('data.0.experiences.0.status', 'Present')
            ->assertJsonPath('data.0.experiences.0.status_label', 'Still working')
            ->assertJsonPath('data.0.experiences.0.duration', '1 year 1 month')
            ->assertJsonPath('data.0.experiences.0.total_time', '1 year 1 month')
            ->assertJsonPath('data.0.experiences.0.timeline', 'Mar 2025 • Still working • 1 year 1 month')

            ->assertJsonPath('data.1.experiences.0.company_id', $oldCompany->id)
            ->assertJsonPath('data.1.experiences.0.start_date', 'Mar 2023')
            ->assertJsonPath('data.1.experiences.0.end_date', 'Mar 2024')
            ->assertJsonPath('data.1.experiences.0.status', 'Ended')
            ->assertJsonPath('data.1.experiences.0.status_label', 'Completed')
            ->assertJsonPath('data.1.experiences.0.duration', '1 year');

        Carbon::setTestNow();
    }
}
