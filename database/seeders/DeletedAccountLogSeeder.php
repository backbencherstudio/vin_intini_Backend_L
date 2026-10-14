<?php

namespace Database\Seeders;

use App\Models\DeletedAccountLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeletedAccountLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $sampleLogs = [
            [
                'user_id' => 101,
                'user_name' => 'Michael Vance',
                'user_email' => 'michael.vance@gmail.com',
                'reason' => 'Switching to another platform for my neuroscience research.',
                'requested_at' => $now->copy()->subDays(2),
                'permanent_delete_at' => $now->copy()->subDays(2)->addDays(30),
            ],
            [
                'user_id' => 102,
                'user_name' => 'Sophia Martinez',
                'user_email' => 'sophia.martinez@outlook.com',
                'reason' => 'Privacy concerns and desire to remove my digital footprint.',
                'requested_at' => $now->copy()->subDays(5),
                'permanent_delete_at' => $now->copy()->subDays(5)->addDays(30),
            ],
            [
                'user_id' => 103,
                'user_name' => 'Alexander Hayes',
                'user_email' => 'alex.hayes@academic.edu',
                'reason' => 'Finished my university project and no longer need this research account.',
                'requested_at' => $now->copy()->subDays(9),
                'permanent_delete_at' => $now->copy()->subDays(9)->addDays(30),
            ],
            [
                'user_id' => 104,
                'user_name' => 'Emma Watson',
                'user_email' => 'emma.watson@researchlabs.org',
                'reason' => 'Receiving too many notification emails and taking a career break.',
                'requested_at' => $now->copy()->subDays(14),
                'permanent_delete_at' => $now->copy()->subDays(14)->addDays(30),
            ],
            [
                'user_id' => 105,
                'user_name' => 'Daniel Tanaka',
                'user_email' => 'daniel.tanaka@tokyo-biotech.jp',
                'reason' => 'Company closed its biotech division, migrating data to internal archive.',
                'requested_at' => $now->copy()->subDays(18),
                'permanent_delete_at' => $now->copy()->subDays(18)->addDays(30),
            ],
            [
                'user_id' => 106,
                'user_name' => 'Olivia Johansson',
                'user_email' => 'olivia.johansson@nordicneuro.se',
                'reason' => 'Consolidating multiple accounts into my primary institutional email.',
                'requested_at' => $now->copy()->subDays(22),
                'permanent_delete_at' => $now->copy()->subDays(22)->addDays(30),
            ],
            [
                'user_id' => 107,
                'user_name' => 'Liam O\'Connor',
                'user_email' => 'liam.oconnor@dublin-neuro.ie',
                'reason' => 'No longer working in computational neuroscience.',
                'requested_at' => $now->copy()->subDays(26),
                'permanent_delete_at' => $now->copy()->subDays(26)->addDays(30),
            ],
            [
                'user_id' => 108,
                'user_name' => 'Chloe Dubois',
                'user_email' => 'chloe.dubois@sorbonne.fr',
                'reason' => 'Requested permanent deletion under GDPR right to be forgotten.',
                'requested_at' => $now->copy()->subDays(29),
                'permanent_delete_at' => $now->copy()->subDays(29)->addDays(30),
            ],
        ];

        foreach ($sampleLogs as $log) {
            DeletedAccountLog::updateOrCreate(
                ['user_email' => $log['user_email']],
                $log
            );
        }
    }
}
