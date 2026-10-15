<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobPost;
use App\Models\State;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class JobsFeedSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }

    private function createJob(array $overrides = []): IndustryJobPost
    {
        $creator = User::factory()->create(['is_verified' => true]);

        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => 'Acme Labs',
            'slug' => 'acme-labs-'.uniqid(),
            'status' => 'approved',
        ]);

        $state = State::firstOrCreate(['name' => 'Arizona'], [
            'code' => 'AZ',
            'slug' => 'arizona',
        ]);

        $city = City::firstOrCreate(['name' => 'Phoenix', 'state_id' => $state->id]);

        return IndustryJobPost::create(array_merge([
            'job_id' => (string) random_int(100000, 999999),
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => 'Clinical Research Specialist',
            'slug' => 'clinical-research-specialist-'.uniqid(),
            'position' => 'Research Director',
            'category' => 'Research / Lab',
            'sub_category' => 'Lab Manager',
            'network_type' => 'psychology',
            'employment_offering' => 'private',
            'work_mode' => 'on_site',
            'employment_type' => 'full_time',
            'job_description' => 'Research lab description',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'email' => 'contact@acme.com',
            'phone_number' => '+1234567890',
            'salary_min' => 70000,
            'salary_max' => 95000,
            'salary_type' => 'Yearly',
            'announcement_start_date' => Carbon::yesterday()->toDateString(),
            'announcement_end_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => IndustryJobPostStatus::PUBLISHED->value,
        ], $overrides));
    }

    public function test_jobsfeed_returns_expected_backward_compatible_structure(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->createJob();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'success',
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'job_id',
                        'job_title',
                        'slug',
                        'position',
                        'status',
                        'work_mode',
                        'employment_type',
                        'location',
                        'applications_count',
                        'created_at_human',
                        'is_saved',
                        'is_liked',
                        'is_applied',
                        'application_status',
                        'network_type',
                        'employment_offering',
                        'category',
                        'sub_category',
                        'level',
                        'experience',
                        'salary_type',
                        'salary_min',
                        'salary_max',
                        'state_id',
                        'city_id',
                        'industry' => [
                            'id',
                            'name',
                            'slug',
                            'logo',
                            'website',
                        ],
                    ],
                ],
                'total_jobs',
                'stats' => ['total_jobs'],
                'pagination' => [
                    'limit',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                    'has_more_pages',
                ],
            ])
            ->assertJsonPath('data.0.status', 'published');
    }

    public function test_jobsfeed_supports_multi_city_and_wizard_filters(): void
    {
        $state = State::create(['name' => 'Arizona', 'code' => 'AZ', 'slug' => 'az']);
        $phoenix = City::create(['name' => 'Phoenix', 'state_id' => $state->id]);
        $tucson = City::create(['name' => 'Tucson', 'state_id' => $state->id]);
        $mesa = City::create(['name' => 'Mesa', 'state_id' => $state->id]);

        // Job 1 in Phoenix (Psychology, Research / Lab, Lab Manager, full_time)
        $this->createJob([
            'state_id' => $state->id,
            'city_id' => $phoenix->id,
            'network_type' => 'psychology',
            'category' => 'Research / Lab',
            'sub_category' => 'Lab Manager',
            'employment_type' => 'full_time',
        ]);

        // Job 2 in Tucson (Psychology, Research / Lab, Lab Manager, part_time)
        $this->createJob([
            'state_id' => $state->id,
            'city_id' => $tucson->id,
            'network_type' => 'psychology',
            'category' => 'Research / Lab',
            'sub_category' => 'Lab Manager',
            'employment_type' => 'part_time',
        ]);

        // Job 3 in Mesa (Neuroscience, Academia, Professor, contract)
        $this->createJob([
            'state_id' => $state->id,
            'city_id' => $mesa->id,
            'network_type' => 'neuroscience',
            'category' => 'Academia',
            'sub_category' => 'Professor',
            'employment_type' => 'contract',
        ]);

        $user = $this->createAuthenticatedUser();

        // Filter for both Phoenix and Tucson, Psychology, Research / Lab, Lab Manager, looking for full_time or part_time
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?'.http_build_query([
            'network_type' => 'psychology',
            'category' => 'Research / Lab',
            'sub_category' => 'Lab Manager',
            'employment_type' => 'full_time,part_time',
            'state_id' => $state->id,
            'city_ids' => [$phoenix->id, $tucson->id],
        ]));

        $response->assertOk();
        $this->assertSame(2, $response->json('total_jobs'));
        $this->assertCount(2, $response->json('data'));
    }

    public function test_jobsfeed_supports_optional_city_grouping(): void
    {
        $user = $this->createAuthenticatedUser();

        $state = State::create(['name' => 'Arizona', 'code' => 'AZ', 'slug' => 'az']);
        $phoenix = City::create(['name' => 'Phoenix', 'state_id' => $state->id]);
        $mesa = City::create(['name' => 'Mesa', 'state_id' => $state->id]);

        $this->createJob([
            'state_id' => $state->id,
            'city_id' => $phoenix->id,
        ]);

        // Query with Phoenix and Mesa, grouped by city (Mesa has 0 jobs)
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?'.http_build_query([
            'city_ids' => [$phoenix->id, $mesa->id],
            'group_by' => 'city',
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'total_jobs',
            'grouped_by_city' => [
                '*' => ['city_id', 'city_name', 'total', 'jobs'],
            ],
        ]);

        $grouped = collect($response->json('grouped_by_city'));
        $phoenixGroup = $grouped->firstWhere('city_name', 'Phoenix');
        $mesaGroup = $grouped->firstWhere('city_name', 'Mesa');

        $this->assertNotNull($phoenixGroup);
        $this->assertSame(1, $phoenixGroup['total']);

        $this->assertNotNull($mesaGroup);
        $this->assertSame(0, $mesaGroup['total']);
        $this->assertEmpty($mesaGroup['jobs']);
    }

    public function test_jobsfeed_filters_all_cities_of_a_given_state(): void
    {
        $user = $this->createAuthenticatedUser();

        $state1 = State::create(['name' => 'Arizona', 'code' => 'AZ', 'slug' => 'az']);
        $state2 = State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'tx']);

        $phoenix = City::create(['name' => 'Phoenix', 'state_id' => $state1->id]);
        $tucson = City::create(['name' => 'Tucson', 'state_id' => $state1->id]);
        $houston = City::create(['name' => 'Houston', 'state_id' => $state2->id]);

        $this->createJob(['state_id' => $state1->id, 'city_id' => $phoenix->id]);
        $this->createJob(['state_id' => $state1->id, 'city_id' => $tucson->id]);
        $this->createJob(['state_id' => $state2->id, 'city_id' => $houston->id]);

        // Query by state_id=1 with city="All Cities" and group_by=city
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?'.http_build_query([
            'state_id' => $state1->id,
            'city' => 'All Cities',
            'group_by' => 'city',
        ]));

        $response->assertOk();
        $this->assertSame(2, $response->json('total_jobs'));
        $this->assertCount(2, $response->json('data'));

        // All cities of State 1 appear in grouped_by_city
        $grouped = collect($response->json('grouped_by_city'));
        $this->assertNotNull($grouped->firstWhere('city_name', 'Phoenix'));
        $this->assertNotNull($grouped->firstWhere('city_name', 'Tucson'));
        $this->assertNull($grouped->firstWhere('city_name', 'Houston'));
    }

    public function test_jobsfeed_filters_salary_range_position_level_and_experience(): void
    {
        $user = $this->createAuthenticatedUser();

        $job1 = $this->createJob([
            'position' => 'Senior Scientist',
            'level' => 'Senior-level',
            'experience' => '5+ years',
            'salary_min' => 90000,
            'salary_max' => 120000,
        ]);

        $job2 = $this->createJob([
            'position' => 'Junior Analyst',
            'level' => 'Entry-level',
            'experience' => '1 year',
            'salary_min' => 40000,
            'salary_max' => 55000,
        ]);

        // Filter by position
        $resPosition = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?position=Senior Scientist');
        $resPosition->assertOk();
        $this->assertSame(1, $resPosition->json('total_jobs'));
        $this->assertSame($job1->id, $resPosition->json('data.0.id'));

        // Filter by level
        $resLevel = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?level=Entry-level');
        $resLevel->assertOk();
        $this->assertSame(1, $resLevel->json('total_jobs'));
        $this->assertSame($job2->id, $resLevel->json('data.0.id'));

        // Filter by salary_min
        $resSalary = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?salary_min=80000');
        $resSalary->assertOk();
        $this->assertSame(1, $resSalary->json('total_jobs'));
        $this->assertSame($job1->id, $resSalary->json('data.0.id'));

        // Filter by salary_max
        $resSalaryMax = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?salary_max=60000');
        $resSalaryMax->assertOk();
        $this->assertSame(1, $resSalaryMax->json('total_jobs'));
        $this->assertSame($job2->id, $resSalaryMax->json('data.0.id'));
    }

    public function test_jobsfeed_handles_user_exact_query_url(): void
    {
        $user = $this->createAuthenticatedUser();

        $state1 = State::create(['name' => 'Alabama', 'code' => 'AL', 'slug' => 'alabama']);
        $city1 = City::create(['name' => 'Alexander City', 'state_id' => $state1->id]);
        $city2 = City::create(['name' => 'Andalusia', 'state_id' => $state1->id]);

        $matchingJob = $this->createJob([
            'state_id' => $state1->id,
            'city_id' => $city1->id,
            'network_type' => 'psychology',
            'category' => 'Research / Lab',
            'sub_category' => 'Lab Manager',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
        ]);

        $nonMatchingJob = $this->createJob([
            'state_id' => $state1->id,
            'city_id' => $city2->id,
            'network_type' => 'engineering',
            'category' => 'Technology',
            'sub_category' => 'Backend Developer',
            'work_mode' => 'on_site',
            'employment_type' => 'part_time',
        ]);

        $url = '/api/industry/jobsfeed?search=&network_type=psychology&category=Research%20%2F%20Lab&sub_category=Lab%20Manager&work_mode=remote&employment_type=full_time&employment_offering=&state_id='.$state1->id.'&group_by=city&city_id%5B%5D='.$city1->id.'&city_id%5B%5D='.$city2->id.'&limit=100&cursor=&salary_min=&salary_max=';

        $response = $this->actingAs($user, 'api')->getJson($url);

        $response->assertOk();
        $this->assertSame(1, $response->json('total_jobs'));
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($matchingJob->id, $response->json('data.0.id'));

        $this->assertArrayHasKey('grouped_by_city', $response->json());
        $grouped = collect($response->json('grouped_by_city'));

        $city1Group = $grouped->firstWhere('city_name', 'Alexander City');
        $this->assertNotNull($city1Group);
        $this->assertSame(1, $city1Group['total']);
        $this->assertCount(1, $city1Group['jobs']);

        $city2Group = $grouped->firstWhere('city_name', 'Andalusia');
        $this->assertNotNull($city2Group);
        $this->assertSame(0, $city2Group['total']);
        $this->assertCount(0, $city2Group['jobs']);
    }

    public function test_jobsfeed_show_all_under_single_city_and_preview_with_has_more(): void
    {
        $user = $this->createAuthenticatedUser();

        $state = State::create(['name' => 'Arizona', 'code' => 'AZ', 'slug' => 'az']);
        $phoenix = City::create(['name' => 'Phoenix', 'state_id' => $state->id]);

        // Create 3 jobs in Phoenix
        $job1 = $this->createJob(['state_id' => $state->id, 'city_id' => $phoenix->id]);
        $job2 = $this->createJob(['state_id' => $state->id, 'city_id' => $phoenix->id]);
        $job3 = $this->createJob(['state_id' => $state->id, 'city_id' => $phoenix->id]);

        // 1. Grouped preview with per_city_limit=2 (Shows 2 preview cards and has_more = true)
        $previewRes = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?'.http_build_query([
            'state_id' => $state->id,
            'city_ids' => [$phoenix->id],
            'group_by' => 'city',
            'per_city_limit' => 2,
        ]));

        $previewRes->assertOk();
        $phoenixGroup = collect($previewRes->json('grouped_by_city'))->firstWhere('city_name', 'Phoenix');
        $this->assertSame(3, $phoenixGroup['total']); // Total in DB is 3
        $this->assertCount(2, $phoenixGroup['jobs']); // Preview contains 2 jobs
        $this->assertTrue($phoenixGroup['has_more']); // has_more is true, prompting "Show All ->" button

        // 2. When user clicks "Show All ->", frontend fetches all jobs of Phoenix as a list
        $showAllRes = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?'.http_build_query([
            'state_id' => $state->id,
            'city_id' => $phoenix->id,
        ]));

        $showAllRes->assertOk();
        $this->assertSame(3, $showAllRes->json('total_jobs'));
        $this->assertCount(3, $showAllRes->json('data')); // All 3 jobs are returned as a flat paginated list!
        $this->assertArrayNotHasKey('grouped_by_city', $showAllRes->json());
    }

    public function test_jobsfeed_filters_by_contract_and_internship_tabs(): void
    {
        $user = $this->createAuthenticatedUser();

        $contractJob = $this->createJob([
            'employment_type' => 'contract',
        ]);

        $internshipJob = $this->createJob([
            'employment_type' => 'internship',
        ]);

        $fullTimeJob = $this->createJob([
            'employment_type' => 'full_time',
        ]);

        // 1. Tab contract
        $resContract = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?tab=contract');
        $resContract->assertOk();
        $this->assertSame(1, $resContract->json('total_jobs'));
        $this->assertSame($contractJob->id, $resContract->json('data.0.id'));

        // 2. Tab internship
        $resInternship = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed?tab=internship');
        $resInternship->assertOk();
        $this->assertSame(1, $resInternship->json('total_jobs'));
        $this->assertSame($internshipJob->id, $resInternship->json('data.0.id'));
    }

    public function test_jobs_by_state_and_offering_endpoint(): void
    {
        $user = $this->createAuthenticatedUser();

        $state1 = State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'tx']);
        $state2 = State::create(['name' => 'Florida', 'code' => 'FL', 'slug' => 'fl']);

        // Job 1: Texas + private
        $job1 = $this->createJob([
            'job_title' => 'Texas Neuro Scientist',
            'state_id' => $state1->id,
            'employment_offering' => 'private',
        ]);

        // Job 2: Texas + state
        $job2 = $this->createJob([
            'state_id' => $state1->id,
            'employment_offering' => 'state',
        ]);

        // Job 3: Florida + private
        $job3 = $this->createJob([
            'state_id' => $state2->id,
            'employment_offering' => 'private',
        ]);

        // 1. Filter by Texas and private
        $res = $this->actingAs($user, 'api')->getJson("/api/industry/jobs-by-state-offering?state_id={$state1->id}&employment_offering=private");
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_jobs', 1)
            ->assertJsonPath('private_jobs_count', 1)
            ->assertJsonPath('state_jobs_count', 1)
            ->assertJsonPath('state.id', $state1->id)
            ->assertJsonPath('state.name', 'Texas')
            ->assertJsonPath('state.code', 'TX')
            ->assertJsonPath('data.0.id', $job1->id)
            ->assertJsonPath('data.0.status', 'published')
            ->assertJsonPath('filters.state_id', $state1->id)
            ->assertJsonPath('filters.employment_offering', 'private')
            ->assertJsonStructure([
                'pagination' => [
                    'limit',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                    'has_more_pages',
                ],
            ]);

        // 2. Filter by Texas only
        $resTexas = $this->actingAs($user, 'api')->getJson("/api/industry/jobs-by-state-offering?state_id={$state1->id}");
        $resTexas->assertOk()
            ->assertJsonPath('total_jobs', 2)
            ->assertJsonPath('private_jobs_count', 1)
            ->assertJsonPath('state_jobs_count', 1);

        // 3. Filter by Florida and private
        $resFlorida = $this->actingAs($user, 'api')->getJson("/api/industry/jobs-by-state-offering?state_id={$state2->id}&employment_offering=private");
        $resFlorida->assertOk()
            ->assertJsonPath('total_jobs', 1)
            ->assertJsonPath('private_jobs_count', 1)
            ->assertJsonPath('state_jobs_count', 0)
            ->assertJsonPath('data.0.id', $job3->id);

        // 4. Search by title in state
        $resSearchTitle = $this->actingAs($user, 'api')->getJson("/api/industry/jobs-by-state-offering?state_id={$state1->id}&search={$job1->job_title}");
        $resSearchTitle->assertOk()
            ->assertJsonPath('total_jobs', 1)
            ->assertJsonPath('data.0.id', $job1->id)
            ->assertJsonPath('filters.search', $job1->job_title);

        // 5. Search by job_id string
        $resSearchJobId = $this->actingAs($user, 'api')->getJson("/api/industry/jobs-by-state-offering?state_id={$state1->id}&search={$job2->job_id}");
        $resSearchJobId->assertOk()
            ->assertJsonPath('total_jobs', 1)
            ->assertJsonPath('data.0.id', $job2->id);
    }

    public function test_jobsfeed_identifies_own_industry_jobs(): void
    {
        $user = $this->createAuthenticatedUser();

        // Own industry
        $ownIndustry = Industry::create([
            'created_by' => $user->id,
            'name' => 'My Own Tech Company',
            'slug' => 'my-own-tech-company-'.uniqid(),
            'status' => 'approved',
        ]);

        $ownJob = $this->createJob([
            'created_by' => $user->id,
            'industry_id' => $ownIndustry->id,
            'job_title' => 'Own Company Developer',
        ]);

        // Other industry
        $otherJob = $this->createJob([
            'job_title' => 'Other Company Designer',
        ]);

        // Authenticated request
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/jobsfeed');
        $response->assertOk();

        $data = collect($response->json('data'));
        $ownJobData = $data->firstWhere('id', $ownJob->id);
        $otherJobData = $data->firstWhere('id', $otherJob->id);

        $this->assertNotNull($ownJobData);
        $this->assertArrayNotHasKey('is_own_industry', $ownJobData);
        $this->assertTrue($ownJobData['industry']['is_own_industry']);
        $this->assertArrayNotHasKey('own_industry', $ownJobData);
        $this->assertArrayNotHasKey('own_industry', $ownJobData['industry']);

        $this->assertNotNull($otherJobData);
        $this->assertArrayNotHasKey('is_own_industry', $otherJobData);
        $this->assertFalse($otherJobData['industry']['is_own_industry']);
    }

    public function test_active_jobs_count_by_state_filtered_by_network_type(): void
    {
        $user = $this->createAuthenticatedUser();

        $state1 = State::firstOrCreate(['name' => 'California'], ['code' => 'CA', 'slug' => 'california']);
        $state2 = State::firstOrCreate(['name' => 'Texas'], ['code' => 'TX', 'slug' => 'texas']);
        $state3 = State::firstOrCreate(['name' => 'Florida'], ['code' => 'FL', 'slug' => 'florida']); // State with 0 jobs

        // State 1: 2 psychology jobs, 1 engineering job
        $this->createJob([
            'state_id' => $state1->id,
            'network_type' => 'psychology',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);
        $this->createJob([
            'state_id' => $state1->id,
            'network_type' => 'psychology',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);
        $this->createJob([
            'state_id' => $state1->id,
            'network_type' => 'engineering',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        // State 2: 1 psychology job, 1 expired psychology job (should not be counted)
        $this->createJob([
            'state_id' => $state2->id,
            'network_type' => 'psychology',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);
        $this->createJob([
            'state_id' => $state2->id,
            'network_type' => 'psychology',
            'status' => IndustryJobPostStatus::EXPIRED,
        ]);

        // 1. Get counts filtered by network_type=psychology (returns all states by default)
        $res = $this->actingAs($user, 'api')->getJson('/api/industry/jobs-count-by-state?network_type=psychology');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_active_jobs', 3)
            ->assertJsonPath('summary.total_states_with_jobs', 2);

        $data = collect($res->json('data'));
        $caState = $data->firstWhere('state_id', $state1->id);
        $txState = $data->firstWhere('state_id', $state2->id);
        $flState = $data->firstWhere('state_id', $state3->id);

        $this->assertEquals(2, $caState['active_jobs_count']);
        $this->assertEquals('CA', $caState['state_code']);
        $this->assertEquals(2, $caState['network_types_breakdown']['psychology']);

        $this->assertEquals(1, $txState['active_jobs_count']);
        $this->assertEquals('TX', $txState['state_code']);

        // Florida has 0 active jobs but is still present in the response
        $this->assertNotNull($flState);
        $this->assertEquals(0, $flState['active_jobs_count']);
        $this->assertEquals('FL', $flState['state_code']);

        // 2. Get counts filtered by network_type=engineering
        $resEng = $this->actingAs($user, 'api')->getJson('/api/industry/jobs-count-by-state?network_type=engineering');
        $resEng->assertOk()
            ->assertJsonPath('total_active_jobs', 1)
            ->assertJsonPath('summary.total_states_with_jobs', 1);

        $engData = collect($resEng->json('data'));
        $this->assertEquals(1, $engData->firstWhere('state_id', $state1->id)['active_jobs_count']);
        $this->assertEquals(0, $engData->firstWhere('state_id', $state2->id)['active_jobs_count']);

        // 3. Test request without filter (all network types)
        $resAll = $this->actingAs($user, 'api')->getJson('/api/industry/jobs-count-by-state');
        $resAll->assertOk()
            ->assertJsonPath('total_active_jobs', 4)
            ->assertJsonPath('summary.network_types.psychology', 3)
            ->assertJsonPath('summary.network_types.engineering', 1);

        // 4. Test only_with_jobs=true filter (excludes states with 0 jobs)
        $resOnlyActive = $this->actingAs($user, 'api')->getJson('/api/industry/jobs-count-by-state?only_with_jobs=1');
        $resOnlyActive->assertOk()
            ->assertJsonPath('total_states', 2);
        $this->assertNull(collect($resOnlyActive->json('data'))->firstWhere('state_id', $state3->id));

        // 5. Test sorting by count descending
        $resSort = $this->actingAs($user, 'api')->getJson('/api/industry/jobs-count-by-state?sort=count');
        $resSort->assertOk();
        $sortedData = $resSort->json('data');
        $this->assertGreaterThanOrEqual($sortedData[1]['active_jobs_count'], $sortedData[0]['active_jobs_count']);
    }
}
