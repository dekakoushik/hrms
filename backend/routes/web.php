<?php

use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Enterprise HRMS Web Portal
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global Company Switcher (Session Filter)
    Route::post('/companies/switch', [CompanyController::class, 'switch'])->name('companies.switch');

    // Organizations / Companies Module (Module 2 in sidebar)
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::post('/quick-store', [CompanyController::class, 'quickStore'])->name('quick-store');
    });

    // Employee Onboarding & Directory Module
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::patch('/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::post('/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('reset-password');

        // Document Viewer / Download Secure Route
        Route::get('/{employee}/document/{type}', [EmployeeController::class, 'viewDocument'])->name('document');

        // AJAX Helper Endpoints for Quick Add & Real-time Calculation
        Route::post('/quick-department', [EmployeeController::class, 'quickStoreDepartment'])->name('quick-department');
        Route::post('/quick-designation', [EmployeeController::class, 'quickStoreDesignation'])->name('quick-designation');
        Route::post('/calculate-salary', [EmployeeController::class, 'calculateSalary'])->name('calculate-salary');
    });

    // Attendance Module Routes (Module 4)
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\AttendanceController::class, 'index'])->name('index');
        Route::post('/punch-in', [\App\Http\Controllers\Web\AttendanceController::class, 'punchIn'])->name('punch-in');
        Route::post('/punch-out', [\App\Http\Controllers\Web\AttendanceController::class, 'punchOut'])->name('punch-out');
        Route::post('/{attendance}/regularize', [\App\Http\Controllers\Web\AttendanceController::class, 'regularize'])->name('regularize');
    });

    // Leave Manager Module Routes (Module 5)
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\LeaveController::class, 'index'])->name('index');
        Route::post('/apply', [\App\Http\Controllers\Web\LeaveController::class, 'store'])->name('store');
        Route::post('/{leaveRequest}/action', [\App\Http\Controllers\Web\LeaveController::class, 'action'])->name('action');
        Route::post('/global-policy', [\App\Http\Controllers\Web\LeaveController::class, 'updateGlobalPolicy'])->name('global-policy');
    });

    // Notification Center Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [\App\Http\Controllers\Web\NotificationController::class, 'dropdown'])->name('dropdown');
        Route::post('/read-all', [\App\Http\Controllers\Web\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/{notification}/read', [\App\Http\Controllers\Web\NotificationController::class, 'markAsRead'])->name('read');
    });

    // Payroll (India) Module Routes (Module 6)
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\PayrollController::class, 'index'])->name('index');
        Route::post('/process', [\App\Http\Controllers\Web\PayrollController::class, 'process'])->name('process');
        Route::get('/runs/{payrollRun}', [\App\Http\Controllers\Web\PayrollController::class, 'show'])->name('show');
        Route::post('/runs/{payrollRun}/mark-paid', [\App\Http\Controllers\Web\PayrollController::class, 'markPaid'])->name('mark-paid');
        Route::get('/payslips/{payslip}', [\App\Http\Controllers\Web\PayrollController::class, 'showPayslip'])->name('payslip');
    });

    // Exit & Resignation Management Routes
    Route::prefix('resignations')->name('resignations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ResignationController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Web\ResignationController::class, 'store'])->name('store');
        Route::post('/{resignation}/action', [\App\Http\Controllers\Web\ResignationController::class, 'action'])->name('action');
    });

    // Settings Module Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // Superadmin Profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('password.update');

        // Shift Manager
        Route::get('/shifts', [SettingsController::class, 'shifts'])->name('shifts');
        Route::post('/shifts', [SettingsController::class, 'storeShift'])->name('shifts.store');
        Route::put('/shifts/{shift}', [SettingsController::class, 'updateShift'])->name('shifts.update');
        Route::delete('/shifts/{shift}', [SettingsController::class, 'deleteShift'])->name('shifts.delete');

        // SMTP Configuration
        Route::get('/smtp', [SettingsController::class, 'smtp'])->name('smtp');
        Route::post('/smtp', [SettingsController::class, 'updateSmtp'])->name('smtp.update');
        Route::post('/smtp/test', [SettingsController::class, 'testSmtp'])->name('smtp.test');

        // Location Manager (Work Locations & Geofence)
        Route::get('/locations', [SettingsController::class, 'locations'])->name('locations');
        Route::post('/locations', [SettingsController::class, 'storeLocation'])->name('locations.store');
        Route::put('/locations/{location}', [SettingsController::class, 'updateLocation'])->name('locations.update');
        Route::delete('/locations/{location}', [SettingsController::class, 'deleteLocation'])->name('locations.delete');
        Route::post('/locations/quick-store', [SettingsController::class, 'quickStoreLocation'])->name('locations.quick-store');
    });

    // Contact Directory Module (Everyone can search & view department-wise contacts)
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ContactDirectoryController::class, 'index'])->name('index');
    });

    // Report Generation & Export Module (Admin & Superadmin)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ReportController::class, 'index'])->name('index');
        Route::get('/attendance/download', [\App\Http\Controllers\Web\ReportController::class, 'downloadAttendance'])->name('attendance.download');
        Route::get('/employees/download', [\App\Http\Controllers\Web\ReportController::class, 'downloadEmployees'])->name('employees.download');
        Route::get('/payroll/download', [\App\Http\Controllers\Web\ReportController::class, 'downloadPayroll'])->name('payroll.download');
        Route::get('/leaves/download', [\App\Http\Controllers\Web\ReportController::class, 'downloadLeaves'])->name('leaves.download');
    });

    // Department & Designation Management (Add/Delete)
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/list', [\App\Http\Controllers\Web\DepartmentDesignationController::class, 'listDepartments'])->name('list');
        Route::delete('/{department}', [\App\Http\Controllers\Web\DepartmentDesignationController::class, 'destroyDepartment'])->name('destroy');
    });

    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('/list', [\App\Http\Controllers\Web\DepartmentDesignationController::class, 'listDesignations'])->name('list');
        Route::delete('/{designation}', [\App\Http\Controllers\Web\DepartmentDesignationController::class, 'destroyDesignation'])->name('destroy');
    });

    // HR & Policies Module (Folders, Uploads, Previews, Downloads)
    Route::prefix('policies')->name('policies.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\PolicyController::class, 'index'])->name('index');
        Route::post('/folders', [\App\Http\Controllers\Web\PolicyController::class, 'storeFolder'])->name('folders.store');
        Route::delete('/folders/{folder}', [\App\Http\Controllers\Web\PolicyController::class, 'destroyFolder'])->name('folders.destroy');
        Route::post('/documents', [\App\Http\Controllers\Web\PolicyController::class, 'storeDocument'])->name('documents.store');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\Web\PolicyController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/documents/{document}/preview', [\App\Http\Controllers\Web\PolicyController::class, 'previewDocument'])->name('documents.preview');
        Route::delete('/documents/{document}', [\App\Http\Controllers\Web\PolicyController::class, 'destroyDocument'])->name('documents.destroy');
    });
});

