<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications with counts.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $unreadCount = AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $notifications = AppNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = AppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.'
        ]);
    }

    /**
     * Clear all notifications for user.
     */
    public function clearAll(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Notifications cleared.'
        ]);
    }
}

