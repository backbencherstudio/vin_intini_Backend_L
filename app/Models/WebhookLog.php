<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class WebhookLog extends Model
{
    use Prunable;

    protected $fillable = [
        'provider',
        'event_type',
        'event_id',
        'payload',
        'status',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        $days = (int) config('services.webhooks.retention_days', 30);

        return static::where('created_at', '<=', now()->subDays($days));
    }
}
