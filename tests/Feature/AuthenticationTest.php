<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_successfuly_login_jwt(): void
    {
        $user = User::factory()->create(['password' => bcrypt('test')]);
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'test']);
 
        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('message', 'Successfuly Login')
                    ->whereType('token', 'string')
                    ->where('user', $user)
            );
    }

    public function test_email_password_blank(): void
    {
        $this->setUpFaker();
        $response = $this->postJson('/api/login');
 
        $response
            ->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->whereType('message', 'array'));
    }

    public function test_failed_credential(): void
    {
        $response = $this->postJson('/api/login', ['email' => $this->faker->email(), 'password' => $this->faker->unique()->randomDigitNotNull]);
 
        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Your email or password is wrong!'
            ]);
    }

    public function test_logout(): void
    {
        $user = User::factory()->create(['password' => bcrypt('test')]);
        $token = JWTAuth::attempt(['email' => $user->email, 'password' => 'test']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token
        ])->postJson('/api/logout');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
    }
}
