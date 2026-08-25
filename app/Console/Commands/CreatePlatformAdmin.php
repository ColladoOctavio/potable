<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'potable:admin
        {email? : Email del administrador}
        {--name= : Nombre visible del usuario}
        {--password= : Password inicial; si se omite para un usuario nuevo, se pide de forma oculta}';

    protected $description = 'Crea o promueve un usuario administrador de plataforma.';

    public function handle(): int
    {
        $email = $this->resolveEmail();

        if ($email === null) {
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $name = $this->resolveName($user);

        if ($name === null) {
            return self::FAILURE;
        }

        $password = $this->option('password');

        if (! $user || $password !== null) {
            $password = $this->resolvePassword($password);

            if ($password === null) {
                return self::FAILURE;
            }
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'is_platform_admin' => true,
        ];

        if ($password !== null) {
            $data['password'] = $password;
        }

        if ($user) {
            $user->fill($data)->save();
            $this->info("Admin actualizado: {$user->email}");

            return self::SUCCESS;
        }

        $user = User::create($data);
        $this->info("Admin creado: {$user->email}");

        return self::SUCCESS;
    }

    private function resolveEmail(): ?string
    {
        $email = $this->argument('email') ?: $this->ask('Email del admin');
        $email = strtolower(trim((string) $email));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return null;
        }

        return $email;
    }

    private function resolveName(?User $user): ?string
    {
        $defaultName = $user?->name ?: 'Admin PoTable';
        $name = $this->option('name') ?: $this->ask('Nombre del admin', $defaultName);
        $name = trim((string) $name);

        $validator = Validator::make(
            ['name' => $name],
            ['name' => ['required', 'string', 'max:255']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('name'));

            return null;
        }

        return $name;
    }

    private function resolvePassword(mixed $password): ?string
    {
        if ($password === null) {
            $password = $this->askHiddenPassword();
        }

        $password = (string) $password;

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:8']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return null;
        }

        return $password;
    }

    private function askHiddenPassword(): string
    {
        while (true) {
            $password = (string) $this->secret('Password del admin');
            $confirmation = (string) $this->secret('Confirmar password');

            if ($password === $confirmation) {
                return $password;
            }

            $this->error('Las claves no coinciden.');
        }
    }
}
