<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Notice;
use App\Models\AppNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * List notices (Scoped for employees; full access for admin/superadmin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $today = Carbon::today()->toDateString();

        $query = Notice::with(['creator:id,name,role', 'department:id,name'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            // Employees see only active notices within validity
            $query->where('is_active', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('publish_date')->orWhere('publish_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
                });

            // Target audience filtering
            $employee = Employee::where('user_id', $user->id)->first();
            $deptId = $employee?->department_id;

            $query->where(function ($q) use ($deptId) {
                $q->where('target_audience', 'all');
                if ($deptId) {
                    $q->orWhere(function ($sub) use ($deptId) {
                        $sub->where('target_audience', 'department')
                            ->where('department_id', $deptId);
                    });
                }
            });
        }

        $notices = $query->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $notices,
        ]);
    }

    /**
     * Create a new official notice (Admin/Superadmin only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:normal,important,urgent',
            'target_audience' => 'nullable|in:all,department,management',
            'department_id' => 'nullable|exists:departments,id',
            'publish_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:publish_date',
            'is_pinned' => 'nullable|boolean',
        ]);

        $notice = Notice::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'] ?? 'normal',
            'target_audience' => $validated['target_audience'] ?? 'all',
            'department_id' => $validated['department_id'] ?? null,
            'created_by_user_id' => $user->id,
            'publish_date' => $validated['publish_date'] ?? Carbon::today()->toDateString(),
            'expiry_date' => $validated['expiry_date'] ?? null,
            'is_pinned' => filter_var($validated['is_pinned'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_active' => true,
        ]);

        // Push in-app notifications to all relevant users
        $recipients = User::query();
        if ($notice->target_audience === 'department' && $notice->department_id) {
            $recipients->whereHas('employee', function ($q) use ($notice) {
                $q->where('department_id', $notice->department_id);
            });
        }

        foreach ($recipients->cursor() as $recipient) {
            AppNotification::create([
                'user_id' => $recipient->id,
                'title' => '📢 New Notice: ' . $notice->title,
                'message' => substr(strip_tags($notice->content), 0, 140) . '...',
                'type' => 'announcement',
                'action_url' => '/dashboard',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Official notice published and circulated to staff.',
            'data' => $notice->load(['creator:id,name', 'department:id,name']),
        ], 201);
    }

    /**
     * Delete notice (Admin/Superadmin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notice removed from bulletin board.',
        ]);
    }
}

