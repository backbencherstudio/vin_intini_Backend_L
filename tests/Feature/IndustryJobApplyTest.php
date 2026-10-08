<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\Plan;
use App\Models\Skill;
use App\Models\State;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryJobApplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createPremiumApplicant(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'first_name' => 'Sheikh Muhammad',
            'last_name' => 'Ashik',
            'title' => 'UI/UX Designer',
            'email' => 'ashik@example.com',
            'mobile' => '+8801234567890',
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        $plan = Plan::create([
            'name' => 'Premium Plan',
            'plan_type' => 'premium',
            'status' => 'active',
            'billing_rate' => 19.99,
            'billing_cycle' => 'monthly',
            'features' => ['unlimited_job_applications'],
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        return $user;
    }

    private function createJobPost(): IndustryJobPost
    {
        $creator = User::factory()->create(['is_verified' => true]);

        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => 'Betopia Group',
            'slug' => 'betopia-group',
            'status' => 'approved',
        ]);

        return IndustryJobPost::create([
            'job_id' => 'JOB-200',
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => 'Product Designer',
            'slug' => 'product-designer-job-200',
            'job_description' => 'Design great products.',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'jobs@betopia.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
        ]);
    }

    public function test_custom_apply_saves_manual_input_data(): void
    {
        $applicant = $this->createPremiumApplicant();
        $jobPost = $this->createJobPost();

        $pdfResume = UploadedFile::fake()->create('custom_resume.pdf', 200, 'application/pdf');

        $response = $this->actingAs($applicant, 'api')->postJson("/api/industry/job-post/{$jobPost->id}/apply", [
            'application_type' => 'custom',
            'full_name' => 'Custom Name',
            'email' => 'custom@example.com',
            'phone_number' => '+123456789',
            'experiences' => '3 years in design',
            'current_position' => 'Senior Designer',
            'expected_salary' => 85000,
            'location' => 'New York, USA',
            'linkedin_url' => 'https://linkedin.com/in/custom',
            'portfolio_url' => 'https://customportfolio.com',
            'cover_letter' => 'My custom cover letter.',
            'about_yourself' => 'About custom applicant.',
            'skills' => 'Figma, Adobe XD, Sketch',
            'resume' => $pdfResume,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application_type', 'custom');

        $this->assertDatabaseHas('industry_job_applications', [
            'job_id' => $jobPost->id,
            'applicant_id' => $applicant->id,
            'application_type' => 'custom',
            'full_name' => 'Custom Name',
            'email' => 'custom@example.com',
            'experiences' => '3 years in design',
            'current_position' => 'Senior Designer',
            'location' => 'New York, USA',
            'about_yourself' => 'About custom applicant.',
        ]);

        $application = IndustryJobApplication::where('applicant_id', $applicant->id)->first();
        $this->assertSame(['Figma', 'Adobe XD', 'Sketch'], $application->skills);
    }

    public function test_quick_apply_auto_populates_name_about_skills_and_meta_from_user_profile(): void
    {
        $applicant = $this->createPremiumApplicant();
        $jobPost = $this->createJobPost();

        $skill1 = Skill::create(['name' => 'Figma']);
        $skill2 = Skill::create(['name' => 'UI/UX Design']);
        $skill3 = Skill::create(['name' => 'Prototyping']);

        $state = State::create([
            'name' => 'Dhaka',
            'code' => 'DHA',
            'slug' => 'dhaka',
        ]);

        $applicant->profile->update([
            'country' => 'Bangladesh',
            'state_id' => $state->id,
            'about' => 'Passionate designer turning complex needs into accessible experiences.',
            'skills_id' => [$skill1->id, $skill2->id, $skill3->id],
        ]);

        $pdfResume = UploadedFile::fake()->create('quick_resume.pdf', 300, 'application/pdf');

        // In Quick apply mode, user doesn't submit full_name, about_yourself, skills, location, or current_position
        $response = $this->actingAs($applicant, 'api')->postJson("/api/industry/job-post/{$jobPost->id}/apply", [
            'application_type' => 'quick',
            'email' => 'ashik@example.com',
            'phone_number' => '+8801234567890',
            'expected_salary' => 95000,
            'resume' => $pdfResume,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application_type', 'quick');

        $this->assertDatabaseHas('industry_job_applications', [
            'job_id' => $jobPost->id,
            'applicant_id' => $applicant->id,
            'application_type' => 'quick',
            'full_name' => 'Sheikh Muhammad Ashik', // Populated from user's first_name + last_name
            'email' => 'ashik@example.com',
            'phone_number' => '+8801234567890',
            'current_position' => 'UI/UX Designer', // Fallback from user's title
            'location' => 'Dhaka, Bangladesh', // Fallback from state name and country
            'about_yourself' => 'Passionate designer turning complex needs into accessible experiences.', // From profile->about
        ]);

        $application = IndustryJobApplication::where('applicant_id', $applicant->id)->first();

        // Verify skills are saved as string names (not IDs)
        $this->assertEqualsCanonicalizing(['Figma', 'UI/UX Design', 'Prototyping'], $application->skills);

        // Verify application_id format
        $this->assertNotNull($application->application_id);
        $this->assertEquals(6, strlen($application->application_id));
        $this->assertStringStartsWith((string) $jobPost->id, $application->application_id);
    }

    public function test_generate_unique_application_id_format_and_lengths(): void
    {
        // 1-digit job ID: 1 + 5 = 6 characters
        $id1 = IndustryJobApplication::generateUniqueApplicationId(3);
        $this->assertEquals(6, strlen($id1));
        $this->assertStringStartsWith('3', $id1);
        $this->assertMatchesRegularExpression('/^3[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{5}$/', $id1);

        // 2-digit job ID: 2 + 4 = 6 characters
        $id2 = IndustryJobApplication::generateUniqueApplicationId(42);
        $this->assertEquals(6, strlen($id2));
        $this->assertStringStartsWith('42', $id2);
        $this->assertMatchesRegularExpression('/^42[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/', $id2);

        // 3-digit job ID: 3 + 3 = 6 characters
        $id3 = IndustryJobApplication::generateUniqueApplicationId(250);
        $this->assertEquals(6, strlen($id3));
        $this->assertStringStartsWith('250', $id3);
        $this->assertMatchesRegularExpression('/^250[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{3}$/', $id3);

        // 4-digit job ID: 4 + 3 = 7 characters
        $id4 = IndustryJobApplication::generateUniqueApplicationId(1024);
        $this->assertEquals(7, strlen($id4));
        $this->assertStringStartsWith('1024', $id4);
        $this->assertMatchesRegularExpression('/^1024[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{3}$/', $id4);
    }

    public function test_model_boot_creates_application_id_automatically_when_omitted(): void
    {
        $applicant = $this->createPremiumApplicant();
        $jobPost = $this->createJobPost();

        $app = IndustryJobApplication::create([
            'job_id' => $jobPost->id,
            'applicant_id' => $applicant->id,
            'full_name' => 'Auto ID Applicant',
            'email' => 'auto@example.com',
            'resume_path' => 'resumes/auto.pdf',
            'status' => 'pending',
        ]);

        $this->assertNotNull($app->application_id);
        $this->assertEquals(6, strlen($app->application_id));
        $this->assertStringStartsWith((string) $jobPost->id, $app->application_id);
    }
}
