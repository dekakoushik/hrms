<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Resignation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Report Center Dashboard
     */
    public function index(Request $request): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        // High-level summary metrics for reporting
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'total_attendances' => Attendance::count(),
            'month_attendances' => Attendance::whereYear('attendance_date', date('Y'))->whereMonth('attendance_date', date('m'))->count(),
            'total_leaves' => LeaveRequest::count(),
            'pending_leaves' => LeaveRequest::where('status', 'pending')->count(),
            'total_payslips' => Payslip::count(),
            'total_payroll_disbursed' => Payslip::sum('net_salary'),
            'total_resignations' => Resignation::count(),
        ];

        return view('reports.index', compact('companies', 'departments', 'stats'));
    }

    /**
     * Download Attendance Report (CSV / Excel friendly)
     */
    public function downloadAttendance(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $companyId = $request->input('company_id');
        $departmentId = $request->input('department_id');
        $status = $request->input('status');
        $isFieldVisit = $request->input('is_field_visit');

        $query = Attendance::with(['employee.user', 'employee.department', 'employee.designation', 'employee.company', 'shift', 'punchInLocation'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }
        if (!empty($departmentId) && $departmentId !== 'all') {
            $query->whereHas('employee', fn($eq) => $eq->where('department_id', $departmentId));
        }
        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($isFieldVisit === '1') {
            $query->where('is_field_visit', true);
        }

        $records = $query->orderBy('attendance_date', 'desc')->orderBy('employee_id')->get();
        $fileName = 'Attendance_Report_' . Carbon::parse($startDate)->format('Ymd') . '_to_' . Carbon::parse($endDate)->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // CSV Header Row
            fputcsv($handle, [
                'Attendance Date',
                'Employee ID',
                'Employee Name',
                'Official Email',
                'Contact Number',
                'Department',
                'Designation',
                'Company Entity',
                'Work Shift',
                'Punch In Time',
                'Punch Out Time',
                'Total Hours',
                'Status',
                'Late Arrival (Min)',
                'Early Exit (Min)',
                'Grace Period Used',
                'Regularized',
                'Field Visit Duty',
                'Field Remarks',
                'Field GPS Lat',
                'Field GPS Lng',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->attendance_date ? $row->attendance_date->format('Y-m-d') : '',
                    $row->employee?->employee_id ?? 'N/A',
                    $row->employee?->full_name ?? 'N/A',
                    $row->employee?->user?->email ?? $row->employee?->official_email ?? '',
                    $row->employee?->contact_number ?? '',
                    $row->employee?->department?->name ?? 'N/A',
                    $row->employee?->designation?->name ?? 'N/A',
                    $row->employee?->company?->name ?? 'N/A',
                    $row->shift?->name ?? 'General',
                    $row->punch_in_at ? $row->punch_in_at->format('H:i:s') : '',
                    $row->punch_out_at ? $row->punch_out_at->format('H:i:s') : '',
                    $row->working_hours ?? '0.00',
                    ucfirst($row->status),
                    $row->is_late ? $row->late_minutes : '0',
                    $row->is_early_exit ? $row->early_minutes : '0',
                    $row->used_grace_period ? 'Yes' : 'No',
                    $row->is_regularized ? 'Yes (' . ($row->regularization_reason ?: 'Admin') . ')' : 'No',
                    $row->is_field_visit ? 'Yes' : 'No',
                    $row->field_visit_remarks ?? '',
                    $row->punch_in_lat ?? '',
                    $row->punch_in_lng ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Download Comprehensive Employee Master Report (CSV)
     */
    public function downloadEmployees(Request $request): StreamedResponse
    {
        $companyId = $request->input('company_id');
        $departmentId = $request->input('department_id');
        $status = $request->input('status', 'all');

        $query = Employee::with(['company', 'department', 'designation', 'user', 'manager', 'shift', 'location']);

        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }
        if (!empty($departmentId) && $departmentId !== 'all') {
            $query->where('department_id', $departmentId);
        }
        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->orderBy('full_name')->get();
        $fileName = 'Employee_Master_Report_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Employee ID',
                'Full Name',
                'Role/User Type',
                'Status',
                'Company Name',
                'Department',
                'Designation',
                'Reporting Manager',
                'Official Email',
                'Personal Email',
                'Contact Number',
                'Alternate Number',
                'Gender',
                'Date of Birth',
                'Blood Group',
                'Marital Status',
                'Date of Joining',
                'Employment Type',
                'Location Type',
                'Assigned Location',
                'Shift',
                'Present City',
                'Present State',
                'PAN Number',
                'Aadhaar Number',
                'UAN Number',
                'PF Account Number',
                'ESIC Number',
                'Bank Name',
                'Bank Account Number',
                'Bank IFSC',
                'Monthly Gross CTC (INR)',
                'Basic Salary (INR)',
            ]);

            foreach ($records as $e) {
                fputcsv($handle, [
                    $e->employee_id,
                    $e->full_name,
                    ucfirst($e->user_type ?? 'User'),
                    ucfirst($e->status),
                    $e->company?->name ?? 'N/A',
                    $e->department?->name ?? 'N/A',
                    $e->designation?->name ?? 'N/A',
                    $e->manager?->full_name ?? 'N/A',
                    $e->user?->email ?? $e->official_email ?? '',
                    $e->personal_email ?? '',
                    $e->contact_number,
                    $e->alternate_number ?? '',
                    $e->gender ?? '',
                    $e->dob ? $e->dob->format('Y-m-d') : '',
                    $e->blood_group ?? '',
                    $e->marital_status ?? '',
                    $e->joining_date ? $e->joining_date->format('Y-m-d') : '',
                    $e->employment_type ?? 'Permanent',
                    ucfirst($e->location_type ?? 'Fixed'),
                    $e->location?->name ?? 'N/A',
                    $e->shift?->name ?? 'General',
                    $e->present_city ?? '',
                    $e->present_state ?? '',
                    $e->pan_number ?? '',
                    $e->aadhaar_number ?? '',
                    $e->uan_number ?? '',
                    $e->pf_number ?? '',
                    $e->esic_number ?? '',
                    $e->bank_name ?? '',
                    $e->bank_account_number ?? '',
                    $e->bank_ifsc ?? '',
                    $e->gross_salary ?? '0.00',
                    $e->basic_salary ?? '0.00',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Download Payroll & Salary Disbursal Report (CSV)
     */
    public function downloadPayroll(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $month = $request->input('month');
        $companyId = $request->input('company_id');

        $query = Payslip::with(['employee.department', 'employee.designation', 'employee.company', 'payrollRun'])
            ->where('year', $year);

        if (!empty($month) && $month !== 'all') {
            $query->where('month', (int) $month);
        }
        if (!empty($companyId) && $companyId !== 'all') {
            $query->where('company_id', $companyId);
        }

        $records = $query->orderBy('month', 'desc')->orderBy('employee_id')->get();
        $fileName = 'Payroll_Report_' . $year . ($month && $month !== 'all' ? '_M' . $month : '') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Payslip Number',
                'Pay Period (Month/Year)',
                'Employee ID',
                'Employee Name',
                'Company Entity',
                'Department',
                'Designation',
                'Payment Status',
                'Payment Mode',
                'Basic Salary (INR)',
                'Dearness Allowance DA (INR)',
                'HRA (INR)',
                'Conveyance Allowance (INR)',
                'Medical / Special Allowance (INR)',
                'Gross Salary (INR)',
                'Employee PF (INR)',
                'Employee ESIC (INR)',
                'Professional Tax PT (INR)',
                'TDS / Income Tax (INR)',
                'Total Deductions (INR)',
                'Net Take-Home (INR)',
                'Generated On',
            ]);

            foreach ($records as $p) {
                fputcsv($handle, [
                    $p->payslip_number,
                    date('F Y', mktime(0, 0, 0, $p->month, 1, $p->year)),
                    $p->employee?->employee_id ?? 'N/A',
                    $p->employee?->full_name ?? 'N/A',
                    $p->company?->name ?? 'N/A',
                    $p->employee?->department?->name ?? 'N/A',
                    $p->employee?->designation?->name ?? 'N/A',
                    ucfirst($p->payrollRun?->status ?? 'Processed'),
                    'Bank Transfer (NEFT/IMPS)',
                    $p->basic_salary ?? 0,
                    $p->da ?? 0,
                    $p->hra ?? 0,
                    $p->ca ?? 0,
                    $p->special_allowance ?? 0,
                    $p->gross_salary ?? 0,
                    $p->pf_employee ?? 0,
                    $p->esic_employee ?? 0,
                    $p->professional_tax ?? 0,
                    $p->tds ?? 0,
                    $p->total_deductions ?? 0,
                    $p->net_salary ?? 0,
                    $p->created_at ? $p->created_at->format('Y-m-d') : '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Download Leave Applications Report (CSV)
     */
    public function downloadLeaves(Request $request): StreamedResponse
    {
        $status = $request->input('status', 'all');
        $year = (int) $request->input('year', date('Y'));

        $query = LeaveRequest::with(['employee.department', 'employee.designation', 'employee.company', 'leaveType', 'actionedByUser'])
            ->whereYear('start_date', $year);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->orderBy('start_date', 'desc')->get();
        $fileName = 'Leave_Applications_Report_' . $year . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Employee ID',
                'Employee Name',
                'Company',
                'Department',
                'Leave Type',
                'Start Date',
                'End Date',
                'Days Count',
                'Half Day',
                'Status',
                'Reason',
                'Actioned By',
                'Action Remarks',
                'Applied At',
            ]);

            foreach ($records as $lr) {
                fputcsv($handle, [
                    $lr->employee?->employee_id ?? 'N/A',
                    $lr->employee?->full_name ?? 'N/A',
                    $lr->company?->name ?? 'N/A',
                    $lr->employee?->department?->name ?? 'N/A',
                    $lr->leaveType?->name ?? 'General Leave',
                    $lr->start_date ? $lr->start_date->format('Y-m-d') : '',
                    $lr->end_date ? $lr->end_date->format('Y-m-d') : '',
                    $lr->days_count,
                    $lr->is_half_day ? 'Yes (' . $lr->half_day_type . ')' : 'No',
                    ucfirst($lr->status),
                    $lr->reason ?? '',
                    $lr->actionedByUser?->name ?? 'N/A',
                    $lr->action_remarks ?? '',
                    $lr->created_at ? $lr->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
