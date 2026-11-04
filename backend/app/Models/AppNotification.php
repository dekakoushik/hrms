<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'icon',
        'priority',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to send notification to specific user or all admins/superadmins.
     */
    public static function sendToAdmins(string $type, string $title, string $message, ?string $actionUrl = null, ?string $icon = null, ?array $data = null)
    {
        $admins = User::whereIn('role', ['superadmin', 'admin'])->get();
        foreach ($admins as $admin) {
            self::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'icon' => $icon ?? 'bell',
                'priority' => 'high',
                'data' => $data,
                'is_read' => false,
            ]);
        }
    }

    /**
     * Helper to send notification to a single user.
     */
    public static function sendToUser(int $userId, string $type, string $title, string $message, ?string $actionUrl = null, ?string $icon = null, ?array $data = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'icon' => $icon ?? 'bell',
            'priority' => 'normal',
            'data' => $data,
            'is_read' => false,
        ]);
    }
}

