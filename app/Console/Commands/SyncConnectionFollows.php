<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Console\Command;

class SyncConnectionFollows extends Command
{
    protected $signature = 'connections:sync-follows';

    protected $description = 'Ensure mutual UserFollow records exist for all accepted connections';

    public function handle(): int
    {
        $this->info('Scanning accepted connections...');

        $connections = Connection::query()
            ->where('status', Connection::STATUS_ACCEPTED)
            ->whereHas('sender', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('receiver', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        $this->info("Found {$connections->count()} accepted connections.");

        $created = 0;
        $existing = 0;

        foreach ($connections as $conn) {
            $f1 = UserFollow::firstOrCreate(
                ['follower_id' => $conn->sender_id, 'following_id' => $conn->receiver_id]
            );
            if ($f1->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }

            $f2 = UserFollow::firstOrCreate(
                ['follower_id' => $conn->receiver_id, 'following_id' => $conn->sender_id]
            );
            if ($f2->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
        }

        $this->info("Sync completed! Newly created follows: {$created}, Existing follows: {$existing}.");

        return self::SUCCESS;
    }
}
