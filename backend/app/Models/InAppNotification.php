<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InAppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'type',
        'title',
        'message',
        'link_url',
        'icon',
        'color',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Dispatch notification to specific user or all admins/superadmins
     */
    public static function createNotification(array $data): self
    {
        return self::create([
            'user_id' => $data['user_id'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'type' => $data['type'] ?? 'system',
            'title' => $data['title'],
            'message' => $data['message'],
            'link_url' => $data['link_url'] ?? null,
            'icon' => $data['icon'] ?? 'bell',
            'color' => $data['color'] ?? 'brand',
            'is_read' => false,
        ]);
    }
}
