<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;
    protected Shift $shift;
    protected Location $location;
    protected Employee $fixedEmployee;
    protected User $fixedUser;
    protected Employee $randomEmployee;
    protected User $randomUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Riangdo Veneers Pvt. Ltd.',
            'code' => 'RVPL',
            'legal_type' => 'Private Limited',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Super Administrator',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'password' => Hash::make('AdminPass@123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN-01',
            'start_time' => '09:30',
            'end_time' => '18:30',
            'grace_period_minutes' => 15,
            'half_day_hours' => 4,
            'full_day_hours' => 8,
            'is_night_shift' => false,
            'is_active' => true,
        ]);

        // Office at Guwahati (26.144517, 91.736236) with 200m radius
        $this->location = Location::create([
            'company_id' => $this->company->id,
            'name' => 'Guwahati Head Office',
            'code' => 'GH-01',
            'latitude' => 26.144517,
            'longitude' => 91.736236,
            'radius_meters' => 200,
            'city' => 'Guwahati',
            'is_active' => true,
        ]);

        // 1. Fixed office employee
        $this->fixedUser = User::create([
            'company_id' => $this->company->id,
            'name' => 'Fixed Office Employee',
            'email' => 'fixed@hrms.in',
            'mobile_number' => '9876543222',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->fixedEmployee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->fixedUser->id,
            'employee_id' => 'EMP-0101',
            'full_name' => 'Fixed Office Employee',
            'contact_number' => '9876543222',
            'shift_id' => $this->shift->id,
            'location_type' => 'fixed',
            'location_id' => $this->location->id,
            'status' => 'active',
            'basic_salary' => 25000,
        ]);

        // 2. Random outside office employee
        $this->randomUser = User::create([
            'company_id' => $this->company->id,
            'name' => 'Field Employee',
            'email' => 'field@hrms.in',
            'mobile_number' => '9876543233',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->randomEmployee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->randomUser->id,
            'employee_id' => 'EMP-0102',
            'full_name' => 'Field Employee',
            'contact_number' => '9876543233',
            'shift_id' => $this->shift->id,
            'location_type' => 'random',
            'status' => 'active',
            'basic_salary' => 25000,
        ]);
    }

    public function test_can_create_and_manage_work_locations_in_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/settings/locations', [
            'company_id' => $this->company->id,
            'name' => 'Manufacturing Unit (Tinsukia)',
            'code' => 'TIN-01',
            'latitude' => 27.4922,
            'longitude' => 95.3537,
            'radius_meters' => 200,
            'city' => 'Tinsukia',
            'state' => 'Assam',
            'pin_code' => '786125',
        ]);

        $response->assertRedirect('/settings/locations');
        $this->assertDatabaseHas('locations', ['name' => 'Manufacturing Unit (Tinsukia)', 'radius_meters' => 200]);
    }

    public function test_fixed_employee_within_200m_can_punch_in(): void
    {
        // 50 meters away from 26.144517, 91.736236 (within 200m)
        $lat = 26.144520;
        $lng = 91.736240;

        Carbon::setTestNow(Carbon::parse('2026-10-08 09:20:00')); // Before shift start (on time)

        $response = $this->actingAs($this->fixedUser)->postJson('/attendance/punch-in', [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->fixedEmployee->id,
            'status' => 'present',
            'is_late' => false,
        ]);
    }

    public function test_fixed_employee_outside_200m_is_rejected(): void
    {
        // ~50 km away
        $lat = 26.500000;
        $lng = 92.000000;

        $response = $this->actingAs($this->fixedUser)->postJson('/attendance/punch-in', [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Geofence violation', $response->json('message'));
        $this->assertDatabaseMissing('attendances', ['employee_id' => $this->fixedEmployee->id]);
    }

    public function test_random_location_employee_can_punch_in_from_any_coordinates(): void
    {
        // Anywhere in field (e.g. 50 km away)
        $lat = 26.500000;
        $lng = 92.000000;

        Carbon::setTestNow(Carbon::parse('2026-10-08 09:15:00'));

        $response = $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->randomEmployee->id,
            'punch_in_location_type' => 'random',
            'status' => 'present',
        ]);
    }

    public function test_punch_in_allowed_only_once_per_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 09:15:00'));

        // First punch in
        $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        // Second punch in on the same day
        $response = $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('only permitted once per day', $response->json('message'));
    }

    public function test_grace_period_allowed_up_to_5_days_in_a_month(): void
    {
        // Shift is 09:30, grace period is 15m (up to 09:45)
        // Simulate employee already used grace 5 times this month
        for ($day = 1; $day <= 5; $day++) {
            Attendance::create([
                'employee_id' => $this->randomEmployee->id,
                'company_id' => $this->company->id,
                'shift_id' => $this->shift->id,
                'attendance_date' => "2026-10-0{$day}",
                'punch_in_at' => "2026-10-0{$day} 09:35:00",
                'status' => 'present',
                'is_late' => true,
                'used_grace_period' => true,
            ]);
        }

        // Today is 6th October: Employee comes at 09:38 (within 15m grace window)
        // But since they already exhausted 5 grace days, it must be marked as half_day!
        Carbon::setTestNow(Carbon::parse('2026-10-06 09:38:00'));

        $response = $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        $response->assertStatus(200);
        $attendance = Attendance::where('employee_id', $this->randomEmployee->id)
            ->whereDate('attendance_date', '2026-10-06')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('half_day', $attendance->status);
        $this->assertFalse($attendance->used_grace_period);
    }

    public function test_late_arrival_after_grace_period_marked_as_half_day(): void
    {
        // Shift is 09:30, grace is 15m (09:45). Employee comes at 09:50!
        Carbon::setTestNow(Carbon::parse('2026-10-08 09:50:00'));

        $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        $attendance = Attendance::where('employee_id', $this->randomEmployee->id)
            ->whereDate('attendance_date', '2026-10-08')
            ->first();

        $this->assertEquals('half_day', $attendance->status);
        $this->assertTrue($attendance->is_late);
        $this->assertEquals(20, $attendance->late_minutes);
    }

    public function test_punch_out_can_be_performed_multiple_times(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 09:00:00'));

        $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        // First punch out at 14:00 (5 hours worked) -> early exit before 18:30 shift end
        Carbon::setTestNow(Carbon::parse('2026-10-08 14:00:00'));
        $resp1 = $this->actingAs($this->randomUser)->postJson('/attendance/punch-out', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);
        $resp1->assertStatus(200);

        $attendance = Attendance::where('employee_id', $this->randomEmployee->id)->first();
        $this->assertEquals(5.0, (float) $attendance->working_hours);
        $this->assertEquals(1, $attendance->punch_out_count);
        $this->assertTrue($attendance->is_early_exit);
        $this->assertEquals('half_day', $attendance->status);

        // Second punch out at 18:30 (9.5 hours worked - on time completion)
        Carbon::setTestNow(Carbon::parse('2026-10-08 18:30:00'));
        $resp2 = $this->actingAs($this->randomUser)->postJson('/attendance/punch-out', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);
        $resp2->assertStatus(200);

        $attendance->refresh();
        $this->assertEquals(9.5, (float) $attendance->working_hours);
        $this->assertEquals(2, $attendance->punch_out_count);
        $this->assertFalse($attendance->is_early_exit);
        $this->assertEquals('present', $attendance->status);
    }

    public function test_early_punch_out_marks_half_day(): void
    {
        // Shift is 09:30 to 18:30
        Carbon::setTestNow(Carbon::parse('2026-10-08 09:30:00'));
        $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        // Leaves early at 16:30 (2 hours before shift end 18:30)
        Carbon::setTestNow(Carbon::parse('2026-10-08 16:30:00'));
        $resp = $this->actingAs($this->randomUser)->postJson('/attendance/punch-out', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);
        $resp->assertStatus(200);

        $attendance = Attendance::where('employee_id', $this->randomEmployee->id)->first();
        $this->assertTrue($attendance->is_early_exit);
        $this->assertEquals(120, $attendance->early_minutes);
        $this->assertEquals('half_day', $attendance->status);
    }

    public function test_attendance_can_be_regularized_by_admin(): void
    {
        // Employee punched in late and left early (half_day)
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));
        $this->actingAs($this->randomUser)->postJson('/attendance/punch-in', [
            'latitude' => 26.144517,
            'longitude' => 91.736236,
        ]);

        $attendance = Attendance::where('employee_id', $this->randomEmployee->id)->first();
        $this->assertEquals('half_day', $attendance->status);

        // Superadmin regularizes attendance to present
        $resp = $this->actingAs($this->superAdmin)->postJson("/attendance/{$attendance->id}/regularize", [
            'status' => 'present',
            'punch_in_time' => '09:30',
            'punch_out_time' => '18:30',
            'reason' => 'Client on-site visit authorized by management',
        ]);

        $resp->assertStatus(200);
        $attendance->refresh();

        $this->assertEquals('present', $attendance->status);
        $this->assertTrue($attendance->is_regularized);
        $this->assertEquals($this->superAdmin->id, $attendance->regularized_by);
        $this->assertEquals('Client on-site visit authorized by management', $attendance->regularization_reason);
        $this->assertEquals(9.0, (float) $attendance->working_hours);
    }
}
