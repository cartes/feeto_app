<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('feeto:ensure-super-admin
    {email : Correo del super-admin}
    {--name= : Nombre del usuario}
    {--password= : Clave para crear o actualizar el super-admin}')]
#[Description('Crea o recupera un super-admin sin entrar manualmente a la base de datos.')]
class EnsureSuperAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $providedPassword = trim((string) $this->option('password'));

        /** @var User|null $user */
        $user = User::query()->firstWhere('email', $email);

        $password = $providedPassword !== ''
            ? $providedPassword
            : ($user === null ? Str::password(16) : null);

        if ($user === null) {
            $user = new User([
                'name' => $name !== '' ? $name : 'Super Admin',
                'email' => $email,
                'is_super_admin' => true,
                'tenant_id' => null,
            ]);
            $user->email_verified_at = now();

            if ($password === null) {
                $this->error('No se pudo generar una clave para el nuevo super-admin.');

                return self::FAILURE;
            }

            $user->password = $password;
            $user->save();

            $this->info('Super-admin creado correctamente.');
            $this->line("Email: {$user->email}");
            $this->warn("Clave temporal: {$password}");

            return self::SUCCESS;
        }

        if ($name !== '') {
            $user->name = $name;
        }

        $user->is_super_admin = true;
        $user->tenant_id = null;
        $user->email_verified_at ??= now();

        if ($password !== null) {
            $user->password = $password;
        }

        $user->save();

        $this->info('Super-admin actualizado correctamente.');
        $this->line("Email: {$user->email}");

        if ($password !== null) {
            $this->warn("Clave temporal: {$password}");
        } else {
            $this->line('Clave: se mantiene la actual.');
        }

        return self::SUCCESS;
    }
}
