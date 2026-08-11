<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'message',
        'file_path',
        'file_name',
        'file_size',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/'.ltrim($this->file_path, '/'));
    }

    public function getFileExtensionAttribute(): ?string
    {
        if (! $this->file_name) {
            return null;
        }

        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION)) ?: null;
    }

    public function getFileCategoryAttribute(): ?string
    {
        $extension = $this->file_extension;

        if (! $extension) {
            return null;
        }

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'heic', 'heif' => 'image',
            'mp4', 'mov', 'avi', 'mkv', 'webm', '3gp' => 'video',
            'mp3', 'wav', 'm4a', 'aac', 'ogg', 'opus' => 'audio',
            'pdf' => 'pdf',
            'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf' => 'document',
            'zip', 'rar', '7z', 'tar', 'gz' => 'archive',
            default => 'other',
        };
    }
}
