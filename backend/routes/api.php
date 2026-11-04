<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\SettingsOverviewController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\SmtpSettingController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Executive Dashboard Analytics
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Employee Management Routes
    Route::get('/employees/generate-id', [EmployeeController::class, 'generateId']);
    Route::post('/employees/calculate-salary', [EmployeeController::class, 'calculateSalary']);
    Route::apiResource('employees', EmployeeController::class);

    // Company Employee Directory (Search & Contact Lookup)
    Route::get('/directory', [DirectoryController::class, 'index']);

    // Attendance & Field Visit Routes
    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/my-history', [AttendanceController::class, 'myHistory']);
        Route::post('/punch-in', [AttendanceController::class, 'punchIn']);
        Route::post('/punch-out', [AttendanceController::class, 'punchOut']);
        Route::post('/field-visit', [AttendanceController::class, 'recordFieldVisit']);

        // Admin Management Endpoints
        Route::get('/admin/records', [AttendanceController::class, 'adminIndex']);
        Route::get('/admin/field-visits', [AttendanceController::class, 'adminFieldVisits']);
        Route::post('/admin/regularize', [AttendanceController::class, 'regularize']);
    });

    // Leave & Absence Management Routes
    Route::prefix('leaves')->group(function () {
        Route::get('/overview', [LeaveController::class, 'myLeaveOverview']);
        Route::post('/apply', [LeaveController::class, 'applyLeave']);
        Route::get('/types', [LeaveController::class, 'getLeaveTypes']);
        Route::post('/types', [LeaveController::class, 'storeLeaveType']);

        // Admin & Superadmin Controls
        Route::get('/admin/applications', [LeaveController::class, 'adminApplications']);
        Route::put('/admin/applications/{id}/status', [LeaveController::class, 'updateApplicationStatus']);
        Route::get('/admin/balances', [LeaveController::class, 'getEmployeeBalances']);
        Route::post('/admin/balances', [LeaveController::class, 'setLeaveBalance']);
        Route::post('/admin/balances/global', [LeaveController::class, 'updateGlobalBalance']);
    });

    // Holiday Calendar Routes
    Route::prefix('holidays')->group(function () {
        Route::get('/', [HolidayController::class, 'index']);
        Route::post('/', [HolidayController::class, 'store']);
        Route::delete('/{id}', [HolidayController::class, 'destroy']);
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        // High-performance consolidated overview endpoint (1 request instead of 6)
        Route::get('/overview', [SettingsOverviewController::class, 'overview']);

        Route::get('/smtp', [SmtpSettingController::class, 'getSettings']);
        Route::post('/smtp', [SmtpSettingController::class, 'updateSettings']);
        Route::post('/smtp/test', [SmtpSettingController::class, 'testConnection']);

        // Resource Routes
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('locations', LocationController::class);
    });

    // Payroll & Salary Calculation Routes
    Route::prefix('payroll')->group(function () {
        Route::get('/calculate', [PayrollController::class, 'calculate']);
        Route::post('/process', [PayrollController::class, 'calculate']); // with save=true
        Route::get('/runs', [PayrollController::class, 'index']);
        Route::get('/my-payslips', [PayrollController::class, 'employeePayslips']);
        Route::get('/payslips/{id}/download', [PayrollController::class, 'downloadPayslip']);
    });

    // Employee Notice Board & Official Circulars
    Route::prefix('notices')->group(function () {
        Route::get('/', [NoticeController::class, 'index']);
        Route::post('/', [NoticeController::class, 'store']);
        Route::delete('/{id}', [NoticeController::class, 'destroy']);
    });

    // Statutory & Compliance Document Management Routes
    Route::prefix('documents')->group(function () {
        // Folders
        Route::get('/folders', [DocumentController::class, 'indexFolders']);
        Route::post('/folders', [DocumentController::class, 'storeFolder']);
        Route::put('/folders/{id}', [DocumentController::class, 'updateFolder']);
        Route::delete('/folders/{id}', [DocumentController::class, 'destroyFolder']);

        // Documents
        Route::get('/', [DocumentController::class, 'indexDocuments']);
        Route::post('/upload', [DocumentController::class, 'storeDocument']);
        Route::post('/{id}/update', [DocumentController::class, 'updateDocument']); // Supports multipart updates
        Route::delete('/{id}', [DocumentController::class, 'destroyDocument']);
        Route::get('/{id}/download', [DocumentController::class, 'downloadDocument']);
    });

    // Notification Center Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
    });

    // IT Helpdesk & Support Tickets Routes
    Route::prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{id}', [TicketController::class, 'show']);
        Route::put('/{id}', [TicketController::class, 'update']);
        Route::post('/{id}/replies', [TicketController::class, 'reply']);
    });

    // Enterprise Reports & Excel/CSV Exports (Admin & Superadmin)
    Route::prefix('reports')->group(function () {
        Route::get('/employee-master', [ReportController::class, 'employeeMaster']);
        Route::get('/attendance', [ReportController::class, 'attendanceReport']);
        Route::get('/leaves', [ReportController::class, 'leavesReport']);
        Route::get('/payroll', [ReportController::class, 'payrollReport']);
        Route::get('/it-tickets', [ReportController::class, 'itTicketsReport']);
    });
});
