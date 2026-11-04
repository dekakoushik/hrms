<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\Payslip;
use App\Models\ItTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Ensure Admin/Superadmin privilege
     */
    protected function checkAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['superadmin', 'admin'])) {
            abort(403, 'Unauthorized: Report exports require administrative privileges.');
        }
    }

    /**
     * Stream CSV helper
     */
    protected function exportCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for clean Excel display
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Employee Master Report (Full Roster & HR Profile)
     */
    public function employeeMaster(Request $request)
    {
        $this->checkAdmin($request);

        $query = Employee::with(['company', 'department', 'designation', 'shift', 'location', 'reportingManager'])
            ->orderBy('employee_code', 'asc');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        $employees = $query->get();

        $headers = [
            'Employee Code',
            'Full Name',
            'Email Address',
            'Phone Number',
            'Gender',
            'Date of Birth',
            'Marital Status',
            'Joining Date',
            'Employment Type',
            'Employment Status',
            'Company Entity',
            'Department',
            'Designation / Job Title',
            'Assigned Shift',
            'Work Location Campus',
            'Reporting Manager',
            'Basic Salary',
            'Gross Salary',
            'Bank Account Holder',
            'Bank Name',
            'Bank Account Number',
            'IFSC Code',
            'PAN Number',
            'Aadhaar Number',
            'UAN Number',
            'ESIC Number',
        ];

        $rows = [];
        foreach ($employees as $emp) {
            $rows[] = [
                $emp->employee_code,
                $emp->name,
                $emp->email,
                $emp->phone,
                ucfirst($emp->gender ?? ''),
                $emp->date_of_birth,
                ucfirst($emp->marital_status ?? ''),
                $emp->date_of_joining,
                ucfirst(str_replace('_', ' ', $emp->employment_type ?? '')),
                ucfirst($emp->employment_status ?? 'active'),
                $emp->company?->name ?? 'N/A',
                $emp->department?->name ?? 'N/A',
                $emp->designation?->title ?? 'N/A',
                $emp->shift?->name ?? 'N/A',
                $emp->location?->name ?? 'N/A',
                $emp->reportingManager?->name ?? 'None / Self',
                $emp->basic_salary,
                $emp->gross_salary,
                $emp->bank_account_holder_name ?? '',
                $emp->bank_name ?? '',
                $emp->bank_account_number ?? '',
                $emp->ifsc_code ?? '',
                $emp->pan_number ?? '',
                $emp->aadhaar_number ?? '',
                $emp->uan_number ?? '',
                $emp->esic_number ?? '',
            ];
        }

        $filename = 'Employee_Master_Roster_' . date('Y_m_d_His') . '.csv';
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * Daily & Monthly Attendance Register Report
     */
    public function attendanceReport(Request $request)
    {
        $this->checkAdmin($request);

        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $query = Attendance::with(['employee.department', 'employee.designation'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('employee_id', 'asc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $attendances = $query->get();

        $headers = [
            'Attendance Date',
            'Employee Code',
            'Employee Name',
            'Department',
            'Designation',
            'Status',
            'Punch In Time',
            'Punch Out Time',
            'Total Working Hours',
            'Attendance Mode',
            'Distance from Campus (m)',
            'Is Late Mark',
            'Late Duration (mins)',
            'Is Regularized',
            'Regularization Reason',
            'Regularization Remarks',
        ];

        $rows = [];
        foreach ($attendances as $att) {
            $rows[] = [
                $att->date,
                $att->employee?->employee_code ?? '',
                $att->employee?->name ?? '',
                $att->employee?->department?->name ?? 'N/A',
                $att->employee?->designation?->title ?? 'N/A',
                strtoupper($att->status),
                $att->punch_in_time ?? '—',
                $att->punch_out_time ?? '—',
                $att->total_working_hours,
                strtoupper($att->mode ?? 'FIXED'),
                $att->distance_from_location ?? '0',
                $att->is_late ? 'YES' : 'NO',
                $att->late_minutes ?? 0,
                $att->is_regularized ? 'YES' : 'NO',
                $att->regularization_reason ?? '',
                $att->regularization_remarks ?? '',
            ];
        }

        $filename = "Attendance_Report_{$startDate}_to_{$endDate}.csv";
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * Leaves & Absence Report
     */
    public function leavesReport(Request $request)
    {
        $this->checkAdmin($request);

        $startDate = $request->query('start_date', Carbon::today()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $query = LeaveApplication::with(['employee.department', 'employee.designation', 'leaveType', 'actionByUser'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $leaves = $query->get();

        $headers = [
            'Leave ID',
            'Employee Code',
            'Employee Name',
            'Department',
            'Leave Type',
            'Start Date',
            'End Date',
            'Total Days',
            'Is Half Day',
            'Half Day Type',
            'Status',
            'Employee Reason',
            'Reviewed By',
            'Admin Remarks',
            'Reviewed At',
        ];

        $rows = [];
        foreach ($leaves as $leave) {
            $rows[] = [
                $leave->id,
                $leave->employee?->employee_code ?? '',
                $leave->employee?->name ?? '',
                $leave->employee?->department?->name ?? 'N/A',
                $leave->leaveType?->name ?? '',
                $leave->start_date,
                $leave->end_date,
                $leave->total_days,
                $leave->is_half_day ? 'YES' : 'NO',
                $leave->half_day_type ?? 'Full Day',
                strtoupper($leave->status),
                $leave->reason,
                $leave->actionByUser?->name ?? 'Pending',
                $leave->admin_remarks ?? '',
                $leave->action_at ? Carbon::parse($leave->action_at)->toDateTimeString() : '—',
            ];
        }

        $filename = "Leaves_Absence_Report_{$startDate}_to_{$endDate}.csv";
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * Payroll Disbursement Register Report
     */
    public function payrollReport(Request $request)
    {
        $this->checkAdmin($request);

        $year = (int) $request->query('year', Carbon::today()->year);
        $month = (int) $request->query('month', Carbon::today()->month);

        $query = Payslip::with(['employee.department', 'employee.designation', 'payroll'])
            ->whereHas('payroll', function ($q) use ($year, $month) {
                $q->where('year', $year)->where('month', $month);
            })
            ->orderBy('employee_id', 'asc');

        $payslips = $query->get();

        $headers = [
            'Payroll Month/Year',
            'Employee Code',
            'Employee Name',
            'Department',
            'Designation',
            'Calendar Days',
            'Present Days',
            'Sundays Off',
            'Paid Holidays',
            'Paid Leaves',
            'Late Arrival Penalty (Half Days)',
            'Payable Days',
            'Basic Salary',
            'Earned Basic',
            'Gross Salary',
            'Earned Gross',
            'EPF Employee (12%)',
            'ESIC Employee (0.75%)',
            'TDS / Tax',
            'Total Deductions',
            'Net Take-Home Salary',
            'Bank Account',
            'Bank IFSC',
            'Disbursement Status',
        ];

        $rows = [];
        foreach ($payslips as $ps) {
            $monthName = Carbon::create($year, $month, 1)->format('F Y');
            $rows[] = [
                $monthName,
                $ps->employee?->employee_code ?? '',
                $ps->employee?->name ?? '',
                $ps->employee?->department?->name ?? 'N/A',
                $ps->employee?->designation?->title ?? 'N/A',
                $ps->calendar_days,
                $ps->present_days,
                $ps->sundays_count,
                $ps->holidays_count,
                $ps->paid_leave_days,
                $ps->late_penalty_half_days,
                $ps->payable_days,
                $ps->basic_salary,
                $ps->earned_basic,
                $ps->gross_salary,
                $ps->earned_gross,
                $ps->employee_pf,
                $ps->employee_esic,
                $ps->monthly_tax,
                $ps->total_deductions,
                $ps->net_salary,
                $ps->employee?->bank_account_number ?? 'N/A',
                $ps->employee?->ifsc_code ?? 'N/A',
                strtoupper($ps->status ?? 'PROCESSED'),
            ];
        }

        $filename = "Payroll_Disbursement_Register_{$year}_{$month}.csv";
        return $this->exportCsv($filename, $headers, $rows);
    }

    /**
     * IT Tickets & Helpdesk Resolution Report
     */
    public function itTicketsReport(Request $request)
    {
        $this->checkAdmin($request);

        $query = ItTicket::with(['employee.department', 'creator', 'assignee'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $tickets = $query->get();

        $headers = [
            'Ticket Number',
            'Date Created',
            'Employee Code',
            'Employee Name',
            'Department',
            'Category',
            'Priority',
            'Status',
            'Subject',
            'Description',
            'Assigned Engineer',
            'Resolution Summary',
            'Resolved At',
        ];

        $rows = [];
        foreach ($tickets as $t) {
            $rows[] = [
                $t->ticket_number,
                Carbon::parse($t->created_at)->toDateTimeString(),
                $t->employee?->employee_code ?? '',
                $t->employee?->name ?? '',
                $t->employee?->department?->name ?? 'N/A',
                strtoupper($t->category),
                strtoupper($t->priority),
                strtoupper($t->status),
                $t->subject,
                $t->description,
                $t->assignee?->name ?? 'Unassigned',
                $t->resolution_notes ?? '',
                $t->resolved_at ? Carbon::parse($t->resolved_at)->toDateTimeString() : '—',
            ];
        }

        $filename = 'IT_Support_Tickets_Audit_' . date('Y_m_d_His') . '.csv';
        return $this->exportCsv($filename, $headers, $rows);
    }
}

