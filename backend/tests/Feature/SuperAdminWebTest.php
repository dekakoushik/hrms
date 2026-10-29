<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminWebTest extends TestCase
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
            'designation' => 'Chief HR Officer',
            'department' => 'Executive Management',
            'city' => 'Gurugram',
            'state' => 'Haryana',
            'pincode' => '122002',
            'is_active' => true,
        ]);
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Superadmin Portal');
        $response->assertSee('admin@hrms.in');
        $response->assertSee('9876543210');
    }

    public function test_web_login_with_email_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin@hrms.in',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_web_login_with_indian_mobile_redirects_to_dashboard(): void
    {
        $response = $this->post('/login', [
            'login' => '+91 9876543210',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_superadmin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Namaste, Rajesh Sharma!');
        $response->assertSee('Settings');
        $response->assertSee('Profile');
    }

    public function test_superadmin_can_access_profile_under_settings(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/profile');
        $response->assertStatus(200);
        $response->assertSee('Superadmin Profile Settings');
        $response->assertSee('admin@hrms.in');
        $response->assertSee('9876543210');
    }

    public function test_superadmin_can_update_profile_via_web(): void
    {
        $response = $this->actingAs($this->user)->post('/settings/profile', [
            'name' => 'Rajesh Kumar Sharma',
            'email' => 'rajesh.updated@hrms.in',
            'mobile_number' => '9876543212',
            'designation' => 'Executive Director HR',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Rajesh Kumar Sharma',
            'email' => 'rajesh.updated@hrms.in',
            'mobile_number' => '9876543212',
            'city' => 'New Delhi',
        ]);
    }

    public function test_superadmin_can_update_password_via_web(): void
    {
        $response = $this->actingAs($this->user)->post('/settings/change-password', [
            'current_password' => 'Password@123',
            'password' => 'NewSecret@789',
            'password_confirmation' => 'NewSecret@789',
        ]);

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecret@789', $this->user->password));
    }
}
