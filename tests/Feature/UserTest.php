<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * A basic feature test example.
     */

    public function MakeToken(): string {
        $user = User::factory()->create(['password' => bcrypt('test')]);
        return JWTAuth::attempt(['email' => $user->email, 'password' => 'test']);
    }

    public function test_get_all_data(): void
    {
        $token = $this->MakeToken();
        User::factory(10)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token
        ])->getJson('/api/user');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'    => [
                    "*" => [
                        "id",
                        "name",
                        "email",
                        "email_verified_at",
                        "created_at",
                        "updated_at"
                    ]
                ]
            ]);
    }

    public function test_store_user(): void
    {
        $token = $this->MakeToken();

        $user = [
            "name"                  =>  $this->faker->firstName(),
            "email"                 =>  $this->faker->email(),
            "password"              =>  "Test123",
            "password_confirmation" =>  "Test123"
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type'  => 'application/json'
        ])->postJson('/api/user', $user);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data'    => [
                    "id",
                    "name",
                    "email",
                    "created_at",
                    "updated_at"
                ]
            ]);
    }

    public function test_update_user(): void
    {
        $token = $this->MakeToken();
        $user = User::factory()->create(['password' => bcrypt('test')]);

        $data = [
            "name"                  =>  $this->faker->firstName(),
            "email"                 =>  $user->email,
            "password"              =>  "Test123",
            "password_confirmation" =>  "Test123"
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type'  => 'application/json'
        ])->putJson('/api/user/'.$user->id, $data);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'    => [
                    "id",
                    "name",
                    "email",
                    "created_at",
                    "updated_at"
                ]
            ]);
    }

    public function test_show_user(): void
    {
        $token = $this->MakeToken();
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type'  => 'application/json'
        ])->getJson('/api/user/'.$user->id);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'    => [
                    "id",
                    "name",
                    "email",
                    "email_verified_at",
                    "created_at",
                    "updated_at"
                ]
            ]);
    }

    public function test_delete_user(): void
    {
        $token = $this->MakeToken();
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type'  => 'application/json'
        ])->deleteJson('/api/user/'.$user->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data deleted successfully'
            ]);
    }
}
