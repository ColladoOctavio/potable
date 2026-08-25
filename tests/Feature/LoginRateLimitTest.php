<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_limits_repeated_failed_attempts(): void
    {
        $email = 'limit@example.test';
        $key = $email.'|127.0.0.1';
        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')
                ->post('/login', ['email' => $email, 'password' => 'incorrecta'])
                ->assertRedirect('/login')
                ->assertSessionHasErrors('email');
        }

        $this->from('/login')
            ->post('/login', ['email' => $email, 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
    }
}
