<?php

namespace Database\Seeders;

use App\Enums\IndustryJobPostStatus;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobPost;
use App\Models\State;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IndustryJobPostTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(2)->toDateString();
        $endDate = $today->copy()->addDays(60)->toDateString();

        // 1. Update any existing expired jobs so they become active published jobs
        IndustryJobPost::where('status', IndustryJobPostStatus::EXPIRED)
            ->update([
                'status' => IndustryJobPostStatus::PUBLISHED,
                'announcement_start_date' => $startDate,
                'announcement_end_date' => $endDate,
                'salary_type' => 'Monthly',
                'category' => 'Research / Lab',
                'sub_category' => 'Lab Manager',
            ]);

        // Find or create default user and industry
        $user = User::first() ?? User::factory()->create([
            'email' => 'testuser@example.com',
            'is_verified' => true,
        ]);

        $industry = Industry::first() ?? Industry::create([
            'created_by' => $user->id,
            'name' => 'Apex Healthcare Innovations',
            'slug' => 'apex-healthcare-innovations',
            'status' => 'approved',
            'website' => 'https://apexhealth.example.com',
        ]);

        // Ensure State 1 (Alabama) and State 4 (Arizona) exist with cities
        $alabama = State::firstOrCreate(['id' => 1], ['name' => 'Alabama', 'code' => 'AL', 'slug' => 'alabama']);
        $arizona = State::firstOrCreate(['id' => 4], ['name' => 'Arizona', 'code' => 'AZ', 'slug' => 'arizona']);

        $alexanderCity = City::firstOrCreate(['name' => 'Alexander City', 'state_id' => $alabama->id]);
        $birmingham = City::firstOrCreate(['name' => 'Birmingham', 'state_id' => $alabama->id]);
        $phoenix = City::firstOrCreate(['name' => 'Phoenix', 'state_id' => $arizona->id]);
        $tucson = City::firstOrCreate(['name' => 'Tucson', 'state_id' => $arizona->id]);
        $mesa = City::firstOrCreate(['name' => 'Mesa', 'state_id' => $arizona->id]);

        $jobsData = [
            // --- ALABAMA JOBS (state_id = 1) ---
            [
                'job_title' => 'Lead Research Psychologist',
                'position' => 'Research Director',
                'category' => 'Research / Lab',
                'sub_category' => 'Lab Manager',
                'network_type' => 'psychology',
                'employment_offering' => 'private',
                'work_mode' => 'remote',
                'employment_type' => 'full_time',
                'level' => 'Senior-level',
                'experience' => '5+ years',
                'state_id' => $alabama->id,
                'city_id' => $alexanderCity->id,
                'salary_min' => 75000,
                'salary_max' => 95000,
                'salary_type' => 'Yearly',
                'job_description' => 'Oversee advanced psychological research and manage lab research staff across various active research projects.',
            ],
            [
                'job_title' => 'Senior Full Stack Laravel Engineer',
                'position' => 'Lead Software Architect',
                'category' => 'Technology',
                'sub_category' => 'Backend Developer',
                'network_type' => 'engineering',
                'employment_offering' => 'private',
                'work_mode' => 'hybrid',
                'employment_type' => 'full_time',
                'level' => 'Senior-level',
                'experience' => '5+ years',
                'state_id' => $alabama->id,
                'city_id' => $birmingham->id,
                'salary_min' => 85000,
                'salary_max' => 120000,
                'salary_type' => 'Yearly',
                'job_description' => 'Build high-throughput healthcare APIs, integrate modern frontend stacks, and optimize database architecture.',
            ],
            [
                'job_title' => 'Clinical Research Coordinator',
                'position' => 'Clinical Coordinator',
                'category' => 'Healthcare',
                'sub_category' => 'Clinical Researcher',
                'network_type' => 'medical',
                'employment_offering' => 'state',
                'work_mode' => 'on_site',
                'employment_type' => 'part_time',
                'level' => 'Mid-level',
                'experience' => '3 years',
                'state_id' => $alabama->id,
                'city_id' => $birmingham->id,
                'salary_min' => 35000,
                'salary_max' => 50000,
                'salary_type' => 'Yearly',
                'job_description' => 'Coordinate clinical trials, track patient protocols, and communicate with state healthcare regulatory boards.',
            ],
            [
                'job_title' => 'Mental Health Counselor',
                'position' => 'Counselor',
                'category' => 'Healthcare',
                'sub_category' => 'Therapist',
                'network_type' => 'psychology',
                'employment_offering' => 'public',
                'work_mode' => 'remote',
                'employment_type' => 'short_term',
                'level' => 'Entry-level',
                'experience' => '1-3 years',
                'state_id' => $alabama->id,
                'city_id' => $alexanderCity->id,
                'salary_min' => 4000,
                'salary_max' => 6000,
                'salary_type' => 'Monthly',
                'job_description' => 'Provide telehealth counseling and mental wellness consultations to individuals and community groups.',
            ],

            // --- ARIZONA JOBS (state_id = 4) ---
            [
                'job_title' => 'Director of Clinical Psychology',
                'position' => 'Clinical Director',
                'category' => 'Research / Lab',
                'sub_category' => 'Lab Manager',
                'network_type' => 'psychology',
                'employment_offering' => 'private',
                'work_mode' => 'on_site',
                'employment_type' => 'full_time',
                'level' => 'Director',
                'experience' => '5+ years',
                'state_id' => $arizona->id,
                'city_id' => $phoenix->id,
                'salary_min' => 110000,
                'salary_max' => 150000,
                'salary_type' => 'Yearly',
                'job_description' => 'Direct clinical research initiatives, mentor clinical psychologists, and publish findings in peer-reviewed journals.',
            ],
            [
                'job_title' => 'Junior Behavioral Data Analyst',
                'position' => 'Behavioral Analyst',
                'category' => 'Research / Lab',
                'sub_category' => 'Data Analyst',
                'network_type' => 'psychology',
                'employment_offering' => 'private',
                'work_mode' => 'remote',
                'employment_type' => 'full_time',
                'level' => 'Entry-level',
                'experience' => '1-3 years',
                'state_id' => $arizona->id,
                'city_id' => $phoenix->id,
                'salary_min' => 52000,
                'salary_max' => 68000,
                'salary_type' => 'Yearly',
                'job_description' => 'Analyze behavioral test metrics, clean clinical datasets, and generate statistical evaluation summaries.',
            ],
            [
                'job_title' => 'Neuroscience Lab Specialist',
                'position' => 'Lab Specialist',
                'category' => 'Healthcare',
                'sub_category' => 'Lab Manager',
                'network_type' => 'medical',
                'employment_offering' => 'state',
                'work_mode' => 'hybrid',
                'employment_type' => 'part_time',
                'level' => 'Mid-level',
                'experience' => '3 years',
                'state_id' => $arizona->id,
                'city_id' => $tucson->id,
                'salary_min' => 4200,
                'salary_max' => 5800,
                'salary_type' => 'Monthly',
                'job_description' => 'Prepare neural imaging samples, maintain lab safety compliance, and assist primary research investigators.',
            ],
        ];

        foreach ($jobsData as $item) {
            $uniqueSlug = Str::slug($item['job_title']).'-'.uniqid();

            IndustryJobPost::create(array_merge([
                'industry_id' => $industry->id,
                'created_by' => $user->id,
                'slug' => $uniqueSlug,
                'email' => 'careers@apexhealth.example.com',
                'phone_number' => '+1-555-839-2041',
                'website' => 'https://apexhealth.example.com',
                'location_url' => 'https://maps.google.com',
                'tags' => [$item['position'], $item['sub_category']],
                'announcement_start_date' => $startDate,
                'announcement_end_date' => $endDate,
                'status' => IndustryJobPostStatus::PUBLISHED,
                'information_confirmed' => true,
                'submitted_at' => Carbon::now(),
                'reviewed_at' => Carbon::now(),
            ], $item));
        }
    }
}
