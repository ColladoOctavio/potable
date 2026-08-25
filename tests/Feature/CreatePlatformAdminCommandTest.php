<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreatePlatformAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_platform_admin_user(): void
    {
        $this->artisan('potable:admin', [
            'email' => 'admin@example.test',
            '--name' => 'Admin Real',
            '--password' => 'password-seguro',
        ])
            ->expectsOutput('Admin creado: admin@example.test')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame('Admin Real', $user->name);
        $this->assertTrue($user->isPlatformAdmin());
        $this->assertTrue(Hash::check('password-seguro', $user->password));
    }

    public function test_promotes_existing_user_without_changing_password(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@example.test',
            'name' => 'Cliente',
            'password' => 'password-original',
            'is_platform_admin' => false,
        ]);

        $this->artisan('potable:admin', [
            'email' => 'cliente@example.test',
            '--name' => 'Cliente Admin',
        ])
            ->expectsOutput('Admin actualizado: cliente@example.test')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame('Cliente Admin', $user->name);
        $this->assertTrue($user->isPlatformAdmin());
        $this->assertTrue(Hash::check('password-original', $user->password));
    }
}
