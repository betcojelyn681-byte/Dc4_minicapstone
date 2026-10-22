<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't seed - create isolated test data
    }

    public function test_login_returns_token(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@daylight.test',
            'password' => bcrypt('ChangeMe123!'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@daylight.test',
            'password' => 'ChangeMe123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_invalid_credentials_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@daylight.test',
            'password' => bcrypt('ChangeMe123!'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@daylight.test',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable();
    }

    public function test_logout_revokes_token(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@daylight.test',
            'password' => bcrypt('ChangeMe123!'),
            'role' => 'admin',
        ]);

        // Login to get a token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin@daylight.test',
            'password' => 'ChangeMe123!',
        ]);
        $token = $loginResponse->json('token');

        // Logout with the token
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/logout');

        $response->assertOk();

        // Token should be revoked - verify by checking the token was deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_user_endpoint_returns_current_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/api/user');
        $response->assertOk()
            ->assertJsonStructure(['id', 'name', 'email', 'role', 'section']);
    }

    public function test_register_creates_student_by_default(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Student',
            'student_no' => '2026-99999',
            'email' => 'newstudent@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'token']);

        $this->assertEquals('student', $response->json('user.role'));
    }

    public function test_register_cannot_create_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Hacker',
            'student_no' => '2026-00000',
            'email' => 'hacker@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        if ($response->status() === 201) {
            $this->assertEquals('student', $response->json('user.role'));
        } else {
            $response->assertUnprocessable();
        }
    }
}