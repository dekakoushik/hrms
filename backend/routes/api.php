<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\SettingsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for HRMS
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.auth.profile');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('api.auth.change-password');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Employees API
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeApiController::class, 'index'])->name('api.employees.index');
        Route::get('/metadata', [EmployeeApiController::class, 'getMetadata'])->name('api.employees.metadata');
        Route::post('/calculate-salary', [EmployeeApiController::class, 'calculateSalary'])->name('api.employees.calculate-salary');
        Route::get('/{employee}', [EmployeeApiController::class, 'show'])->name('api.employees.show');
    });

    // Settings API
    Route::prefix('settings')->group(function () {
        Route::get('/shifts', [SettingsApiController::class, 'getShifts'])->name('api.settings.shifts');
        Route::post('/shifts', [SettingsApiController::class, 'storeShift'])->name('api.settings.shifts.store');
        Route::put('/shifts/{shift}', [SettingsApiController::class, 'updateShift'])->name('api.settings.shifts.update');
        Route::delete('/shifts/{shift}', [SettingsApiController::class, 'deleteShift'])->name('api.settings.shifts.delete');

        Route::get('/smtp', [SettingsApiController::class, 'getSmtp'])->name('api.settings.smtp');
        Route::post('/smtp', [SettingsApiController::class, 'updateSmtp'])->name('api.settings.smtp.update');
    });

    // Native Mobile App API Suite
    Route::prefix('mobile')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getDashboardState'])->name('api.mobile.dashboard');
        Route::post('/attendance/punch-in', [\App\Http\Controllers\Api\MobileAppApiController::class, 'punchIn'])->name('api.mobile.punch-in');
        Route::post('/attendance/punch-out', [\App\Http\Controllers\Api\MobileAppApiController::class, 'punchOut'])->name('api.mobile.punch-out');
        Route::get('/attendance/history', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getAttendanceHistory'])->name('api.mobile.attendance-history');
        Route::get('/leaves', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getLeaveData'])->name('api.mobile.leaves');
        Route::post('/leaves/apply', [\App\Http\Controllers\Api\MobileAppApiController::class, 'applyLeave'])->name('api.mobile.leaves.apply');
        Route::get('/payslips', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getPayslips'])->name('api.mobile.payslips');
        Route::get('/payslips/{payslip}', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getPayslipDetail'])->name('api.mobile.payslips.detail');
        Route::get('/contacts', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getContacts'])->name('api.mobile.contacts');
        Route::get('/policies', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getPolicies'])->name('api.mobile.policies');
        Route::get('/resignations', [\App\Http\Controllers\Api\MobileAppApiController::class, 'getResignations'])->name('api.mobile.resignations');
        Route::post('/resignations/submit', [\App\Http\Controllers\Api\MobileAppApiController::class, 'submitResignation'])->name('api.mobile.resignations.submit');
    });
});
