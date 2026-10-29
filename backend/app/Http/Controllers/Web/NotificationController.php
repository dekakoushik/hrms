<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List all in-app notifications
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $activeCompanyId = session('active_company_id');

        $query = InAppNotification::orderBy('created_at', 'desc');

        if ($isAdmin) {
            // Admins see broadcast notifications (user_id is null) or specific to them
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        } else {
            // Regular employees see only their personal notifications
            $query->where('user_id', $user->id);
        }

        if ($activeCompanyId) {
            $query->where(function ($q) use ($activeCompanyId) {
                $q->whereNull('company_id')->orWhere('company_id', $activeCompanyId);
            });
        }

        $notifications = $query->paginate(25);

        return view('notifications.index', compact('notifications', 'isAdmin'));
    }

    /**
     * AJAX endpoint to fetch unread notification count & recent items for header bell dropdown
     */
    public function dropdown(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $activeCompanyId = session('active_company_id');

        $query = InAppNotification::orderBy('created_at', 'desc');

        if ($isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        if ($activeCompanyId) {
            $query->where(function ($q) use ($activeCompanyId) {
                $q->whereNull('company_id')->orWhere('company_id', $activeCompanyId);
            });
        }

        $unreadCount = (clone $query)->where('is_read', false)->count();
        $recent = $query->limit(8)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'color' => $n->color,
                'icon' => $n->icon,
                'link_url' => $n->link_url ?: '#',
                'is_read' => $n->is_read,
                'time_ago' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $recent,
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(Request $request, InAppNotification $notification): JsonResponse|RedirectResponse
    {
        $notification->update([
            'is_read' => true,
            'read_at' => Carbon::now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        if ($notification->link_url) {
            return redirect($notification->link_url);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);

        $query = InAppNotification::where('is_read', false);

        if ($isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $query->update([
            'is_read' => true,
            'read_at' => Carbon::now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
