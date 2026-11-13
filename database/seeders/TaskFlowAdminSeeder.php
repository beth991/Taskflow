<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\MySqlAdvisoryLock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class TaskFlowAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminInput = [
            'name' => config('taskflow.admin.name'),
            'email' => config('taskflow.admin.email'),
            'password' => config('taskflow.admin.password'),
        ];

        $validated = Validator::make($adminInput, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:12',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('The admin password must not exceed 72 bytes.');
                    }
                },
            ],
        ])->validate();

        $connection = app('db')->connection();
        $provision = fn (): string => $connection->transaction(function () use ($validated, $connection): string {
            $existingUser = User::on($connection->getName())
                ->where('email', $validated['email'])
                ->lockForUpdate()
                ->first();

            if ($existingUser !== null) {
                if (! $existingUser->isAdmin()) {
                    throw new RuntimeException('Admin provisioning stopped: the configured email belongs to a non-Admin account. No account was changed.');
                }

                return 'already_exists';
            }

            $existingAdmin = User::on($connection->getName())
                ->where('role', User::ROLE_ADMIN)
                ->lockForUpdate()
                ->exists();

            if ($existingAdmin) {
                throw new RuntimeException('Admin provisioning stopped: an Admin account already exists with a different email. No account was changed.');
            }

            $admin = User::on($connection->getName())->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

            return 'created';
        });

        if ($connection->getDriverName() === 'sqlite' && app()->environment('testing')) {
            $result = $provision();
        } elseif ($connection->getDriverName() === 'mysql') {
            $result = app(MySqlAdvisoryLock::class)->synchronized(
                $connection,
                'taskflow:initial-admin',
                10,
                $provision,
            );
        } else {
            throw new RuntimeException('Initial Admin provisioning requires MySQL advisory locks outside the SQLite testing environment.');
        }

        $this->command?->info($result === 'created'
            ? 'The initial TaskFlow Admin account was created.'
            : 'The configured Admin account already exists. No changes were made.');
    }
}
