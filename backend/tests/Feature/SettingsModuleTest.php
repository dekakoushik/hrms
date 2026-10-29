<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Rajesh Sharma',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'password' => Hash::make('Password@123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_view_shifts_page(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/shifts');
        $response->assertStatus(200);
        $response->assertSee('Shift Manager');
        $response->assertSee('Add New Shift');
    }

    public function test_superadmin_can_create_and_delete_shift(): void
    {
        $response = $this->actingAs($this->user)->post('/settings/shifts', [
            'name' => 'Afternoon Support Shift',
            'code' => 'AFT-01',
            'start_time' => '14:00',
            'end_time' => '22:30',
            'grace_period_minutes' => 15,
            'half_day_hours' => 4,
            'full_day_hours' => 8,
            'is_night_shift' => 0,
            'description' => 'Support shift for Indian second rotation',
        ]);

        $response->assertRedirect('/settings/shifts');
        $this->assertDatabaseHas('shifts', [
            'code' => 'AFT-01',
            'name' => 'Afternoon Support Shift',
        ]);

        $shift = Shift::where('code', 'AFT-01')->first();
        $deleteResponse = $this->actingAs($this->user)->delete("/settings/shifts/{$shift->id}");
        $deleteResponse->assertRedirect('/settings/shifts');
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_superadmin_can_view_and_update_smtp_configuration(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/smtp');
        $response->assertStatus(200);
        $response->assertSee('SMTP Mail Configuration');

        $updateResponse = $this->actingAs($this->user)->post('/settings/smtp', [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.sendgrid.net',
            'mail_port' => 587,
            'mail_username' => 'apikey',
            'mail_password' => 'SG.testkey',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'hr@enterprise.in',
            'mail_from_name' => 'HR Operations India',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect('/settings/smtp');
        $this->assertDatabaseHas('smtp_settings', [
            'mail_host' => 'smtp.sendgrid.net',
            'mail_from_address' => 'hr@enterprise.in',
        ]);
    }

    public function test_settings_api_shifts_and_smtp_endpoints(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        // Shifts API
        $shiftsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/settings/shifts');
        $shiftsResponse->assertStatus(200)->assertJson(['success' => true]);

        // SMTP API
        $smtpResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/settings/smtp');
        $smtpResponse->assertStatus(200)->assertJson(['success' => true]);
    }
}
