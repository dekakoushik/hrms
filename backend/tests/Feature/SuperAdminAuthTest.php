<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Rajesh Sharma',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'password' => Hash::make('Password@123'),
            'role' => 'superadmin',
            'designation' => 'Chief HR Officer',
            'department' => 'Executive',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_login_with_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => 'admin@hrms.in',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'admin@hrms.in',
                    'role' => 'superadmin',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_superadmin_can_login_with_mobile_number(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => '9876543210',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'admin@hrms.in',
                    'mobile_number' => '9876543210',
                ],
            ]);
    }

    public function test_superadmin_can_login_with_indian_prefix_mobile(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => '+91 9876543210',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_superadmin_can_update_profile(): void
    {
        $user = User::where('email', 'admin@hrms.in')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/profile', [
                'name' => 'Rajesh K. Sharma',
                'email' => 'rajesh.sharma@hrms.in',
                'mobile_number' => '9876543211',
                'designation' => 'Principal VP HR',
                'department' => 'People Operations',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'name' => 'Rajesh K. Sharma',
                    'email' => 'rajesh.sharma@hrms.in',
                    'mobile_number' => '9876543211',
                    'city' => 'Bengaluru',
                ],
            ]);
    }

    public function test_superadmin_can_change_password(): void
    {
        $user = User::where('email', 'admin@hrms.in')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'Password@123',
                'password' => 'NewSecret@456',
                'password_confirmation' => 'NewSecret@456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Login with old password should fail
        $failResponse = $this->postJson('/api/auth/login', [
            'login' => 'admin@hrms.in',
            'password' => 'Password@123',
        ]);
        $failResponse->assertStatus(422);

        // Login with new password should succeed
        $successResponse = $this->postJson('/api/auth/login', [
            'login' => 'admin@hrms.in',
            'password' => 'NewSecret@456',
        ]);
        $successResponse->assertStatus(200);
    }
}
