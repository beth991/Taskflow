<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\TaskFlowAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

class TaskFlowAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_an_admin_without_exposing_the_password(): void
    {
        $password = 'a-secure-admin-password';

        $output = $this->withAdminConfiguration($password, function (): string {
            $this->assertSame(0, Artisan::call('db:seed', [
                '--class' => TaskFlowAdminSeeder::class,
                '--force' => true,
            ]));

            return Artisan::output();
        });

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(password_verify($password, $admin->password));
        $this->assertStringNotContainsString($password, $output);
    }

    public function test_running_the_seeder_repeatedly_with_the_same_email_is_safe(): void
    {
        $password = 'a-secure-admin-password';

        $this->withAdminConfiguration($password, function (): void {
            Artisan::call('db:seed', [
                '--class' => TaskFlowAdminSeeder::class,
                '--force' => true,
            ]);
            config(['taskflow.admin.name' => 'Changed Admin Name']);
            config(['taskflow.admin.password' => 'another-secure-admin-password']);
            Artisan::call('db:seed', [
                '--class' => TaskFlowAdminSeeder::class,
                '--force' => true,
            ]);
        });

        $this->assertDatabaseCount('users', 1);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('TaskFlow Admin', $admin->name);
        $this->assertTrue(password_verify($password, $admin->password));
    }

    public function test_a_second_admin_with_a_different_email_is_rejected_without_changes(): void
    {
        $this->withAdminConfiguration('a-secure-admin-password', function (): void {
            Artisan::call('db:seed', [
                '--class' => TaskFlowAdminSeeder::class,
                '--force' => true,
            ]);

            config(['taskflow.admin.email' => 'second-admin@example.test']);

            try {
                (new TaskFlowAdminSeeder)->run();
                $this->fail('The seeder should stop when an Admin already exists.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('Admin account already exists', $exception->getMessage());
            }
        });

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['email' => 'second-admin@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => User::ROLE_ADMIN]);
    }

    public function test_the_seeder_does_not_promote_an_existing_member(): void
    {
        $member = User::factory()->create([
            'name' => 'Existing Member',
            'email' => 'admin@example.test',
        ]);

        $this->withAdminConfiguration('a-secure-admin-password', function (): void {
            try {
                (new TaskFlowAdminSeeder)->run();
                $this->fail('The seeder should stop when the configured email belongs to a Member.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('non-Admin account', $exception->getMessage());
                $this->assertStringNotContainsString('a-secure-admin-password', $exception->getMessage());
            }
        });

        $this->assertSame(UserRole::Member, $member->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_the_seeder_uses_laravel_configuration_values(): void
    {
        $previousEmail = getenv('TASKFLOW_ADMIN_EMAIL');
        putenv('TASKFLOW_ADMIN_EMAIL=ignored-env@example.test');

        try {
            $this->withAdminConfiguration('a-secure-admin-password', function (): void {
                config(['taskflow.admin.email' => 'configured-admin@example.test']);
                (new TaskFlowAdminSeeder)->run();
            });
        } finally {
            if ($previousEmail === false) {
                putenv('TASKFLOW_ADMIN_EMAIL');
            } else {
                putenv("TASKFLOW_ADMIN_EMAIL={$previousEmail}");
            }
        }

        $this->assertDatabaseHas('users', [
            'email' => 'configured-admin@example.test',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'ignored-env@example.test']);
    }

    private function withAdminConfiguration(string $password, callable $callback): mixed
    {
        $values = [
            'taskflow.admin.name' => 'TaskFlow Admin',
            'taskflow.admin.email' => 'admin@example.test',
            'taskflow.admin.password' => $password,
        ];
        $previous = [];

        foreach ($values as $key => $value) {
            $previous[$key] = config($key);
        }

        config($values);

        try {
            return $callback();
        } finally {
            config($previous);
        }
    }
}
