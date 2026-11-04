<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ItTicket;
use App\Models\ItTicketReply;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Resolve requesting employee or fallback
     */
    protected function getEmployee(Request $request): ?Employee
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee && $user->role === 'superadmin') {
            $employee = Employee::first();
        }
        return $employee;
    }

    /**
     * List tickets
     * - Admins/Superadmins: see all or filtered
     * - Employees: see their own tickets
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);

        $query = ItTicket::with(['employee.department', 'employee.designation', 'creator:id,name,email', 'assignee:id,name,email'])
            ->withCount('replies');

        if (!$isAdmin) {
            $employee = $this->getEmployee($request);
            if (!$employee) {
                return response()->json([
                    'stats' => ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'total' => 0],
                    'tickets' => []
                ]);
            }
            $query->where('employee_id', $employee->id);
        }

        // Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $tickets = $query->orderByRaw("CASE WHEN status = 'open' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        // Stats calculation
        $baseStatsQuery = ItTicket::query();
        if (!$isAdmin) {
            $employee = $this->getEmployee($request);
            if ($employee) {
                $baseStatsQuery->where('employee_id', $employee->id);
            }
        }

        $stats = [
            'open' => (clone $baseStatsQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $baseStatsQuery)->where('status', 'in_progress')->count(),
            'resolved' => (clone $baseStatsQuery)->whereIn('status', ['resolved', 'closed'])->count(),
            'total' => (clone $baseStatsQuery)->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'tickets' => $tickets,
        ]);
    }

    /**
     * Create / Raise a new IT Ticket
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $employee = $this->getEmployee($request);

        if (!$employee) {
            return response()->json([
                'message' => 'No active employee profile linked to your user account to raise tickets.',
            ], 422);
        }

        $request->validate([
            'category' => 'required|string|in:hardware,software,network,access_credentials,email,printer,other',
            'priority' => 'required|string|in:low,medium,high,critical',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'attachment' => 'nullable|file|max:15360', // max 15MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('it_tickets', 'public');
        }

        // Generate unique ticket number: IT-YYYY-####
        $count = ItTicket::whereYear('created_at', now()->year)->count() + 1;
        $ticketNumber = 'IT-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $ticket = ItTicket::create([
            'ticket_number' => $ticketNumber,
            'employee_id' => $employee->id,
            'created_by_user_id' => $user->id,
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'open',
            'subject' => $request->subject,
            'description' => $request->description,
            'attachment_path' => $attachmentPath,
        ]);

        // Notify Admins and Superadmins
        AppNotification::sendToAdmins(
            'it_ticket_raised',
            "IT Ticket Raised: {$ticket->ticket_number}",
            "{$employee->name} submitted ticket \"{$ticket->subject}\" [Priority: " . strtoupper($ticket->priority) . "]",
            '/tickets',
            'shield',
            [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'priority' => $ticket->priority,
            ]
        );

        return response()->json([
            'message' => 'IT Support ticket created successfully.',
            'ticket' => $ticket->load(['employee.department', 'employee.designation', 'creator:id,name,email']),
        ], 201);
    }

    /**
     * Show single ticket details with reply thread
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);

        $ticket = ItTicket::with([
            'employee.department',
            'employee.designation',
            'creator:id,name,email',
            'assignee:id,name,email',
            'replies.user:id,name,email,role',
        ])->findOrFail($id);

        if (!$isAdmin) {
            $employee = $this->getEmployee($request);
            if (!$employee || $ticket->employee_id !== $employee->id) {
                abort(403, 'Unauthorized access to this ticket.');
            }
        }

        return response()->json([
            'ticket' => $ticket
        ]);
    }

    /**
     * Admin/Superadmin: Update ticket status, priority, assignee, or resolution notes
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Only Admins and Superadmins can modify ticket management status.');
        }

        $ticket = ItTicket::with('employee.user')->findOrFail($id);

        $request->validate([
            'status' => 'nullable|string|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'resolution_notes' => 'nullable|string|max:3000',
        ]);

        $prevStatus = $ticket->status;

        $updateData = [];
        if ($request->filled('status')) {
            $updateData['status'] = $request->status;
            if (in_array($request->status, ['resolved', 'closed']) && !$ticket->resolved_at) {
                $updateData['resolved_at'] = now();
            }
        }
        if ($request->filled('priority')) {
            $updateData['priority'] = $request->priority;
        }
        if ($request->has('assigned_to_user_id')) {
            $updateData['assigned_to_user_id'] = $request->assigned_to_user_id;
        }
        if ($request->has('resolution_notes')) {
            $updateData['resolution_notes'] = $request->resolution_notes;
        }

        $ticket->update($updateData);

        // Notify employee of status changes
        if ($request->filled('status') && $request->status !== $prevStatus && $ticket->employee && $ticket->employee->user_id) {
            $statusLabel = strtoupper(str_replace('_', ' ', $ticket->status));
            AppNotification::sendToUser(
                $ticket->employee->user_id,
                'it_ticket_status',
                "Ticket {$ticket->ticket_number} Updated: {$statusLabel}",
                "Your IT support ticket has been moved to status {$statusLabel}." . ($ticket->resolution_notes ? " Notes: {$ticket->resolution_notes}" : ''),
                '/tickets',
                'check-circle',
                [
                    'ticket_id' => $ticket->id,
                    'status' => $ticket->status,
                ]
            );
        }

        return response()->json([
            'message' => 'Ticket updated successfully.',
            'ticket' => $ticket->fresh(['employee.department', 'employee.designation', 'creator:id,name,email', 'assignee:id,name,email']),
        ]);
    }

    /**
     * Post a reply/update in ticket conversation thread
     */
    public function reply(Request $request, $id)
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);

        $ticket = ItTicket::with(['employee.user', 'creator'])->findOrFail($id);

        if (!$isAdmin) {
            $employee = $this->getEmployee($request);
            if (!$employee || $ticket->employee_id !== $employee->id) {
                abort(403, 'Unauthorized.');
            }
        }

        $request->validate([
            'message' => 'required|string|max:3000',
            'attachment' => 'nullable|file|max:15360',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('it_tickets/replies', 'public');
        }

        $reply = ItTicketReply::create([
            'it_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
        ]);

        // If employee replied, notify admins; if admin replied, notify employee
        if (!$isAdmin) {
            AppNotification::sendToAdmins(
                'it_ticket_reply',
                "New Reply on Ticket {$ticket->ticket_number}",
                "{$user->name}: " . Str::limit($request->message, 80),
                '/tickets',
                'bell',
                ['ticket_id' => $ticket->id]
            );
        } else {
            if ($ticket->employee && $ticket->employee->user_id && $ticket->employee->user_id !== $user->id) {
                AppNotification::sendToUser(
                    $ticket->employee->user_id,
                    'it_ticket_reply',
                    "Support Response: {$ticket->ticket_number}",
                    "IT Team: " . Str::limit($request->message, 80),
                    '/tickets',
                    'bell',
                    ['ticket_id' => $ticket->id]
                );
            }
        }

        return response()->json([
            'message' => 'Reply posted successfully.',
            'reply' => $reply->load('user:id,name,email,role'),
        ], 201);
    }
}

