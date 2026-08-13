<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(RolePermissionSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(IntegrationSettingSeeder::class);

        $adminApi = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'mobile' => '01700000001',
                'password' => bcrypt('111111'),
                'is_verified' => true,
            ]
        );

        $adminApi->assignRole(Role::where('name', 'admin')->where('guard_name', 'api')->first());

        $userRole = Role::where('name', 'user')->where('guard_name', 'api')->first();

        $user1 = User::updateOrCreate(
            ['email' => 'user1@gmail.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'mobile' => '01700000002',
                'password' => bcrypt('111111'),
                'is_verified' => true,
            ]
        );

        $user1->assignRole($userRole);

        Subscription::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'plan_id' => Plan::where('name', 'Premium Plan')->first()?->id,
                'platform' => 'stripe',
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]
        );

        UserProfile::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'country' => 'Bangladesh',
                'postal_code' => '1212',
                'profession' => ['Software Engineer'],
                'highest_degree' => "Bachelor's Degree",
                'study_category' => 'Engineering',
                'field_study' => 'Computer Science',
                'institution' => 'BUET',
                'graduation_year' => '2020',
                'interests' => ['Machine Learning', 'Open Source'],
                'about' => 'Backend engineer passionate about building scalable systems.',
                'notify_jobs' => true,
                'notify_publications' => true,
                'notify_residency' => false,
                'notify_offers' => true,
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'user2@gmail.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'mobile' => '01700000003',
                'password' => bcrypt('111111'),
                'is_verified' => true,
            ]
        );

        $user2->assignRole($userRole);

        Connection::updateOrCreate(
            ['sender_id' => $user1->id, 'receiver_id' => $user2->id],
            ['status' => Connection::STATUS_ACCEPTED]
        );

        UserProfile::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'country' => 'United States',
                'postal_code' => '10001',
                'profession' => ['Data Scientist'],
                'highest_degree' => "Master's Degree",
                'study_category' => 'Science',
                'field_study' => 'Data Science',
                'institution' => 'NYU',
                'graduation_year' => '2022',
                'interests' => ['Data Analysis', 'AI Research'],
                'about' => 'Data scientist focused on turning data into insights.',
                'notify_jobs' => true,
                'notify_publications' => false,
                'notify_residency' => true,
                'notify_offers' => true,
            ]
        );
    }
}
