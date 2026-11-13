<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_does_not_create_a_test_user_in_production(): void
    {
        $this->inEnvironment('production', function (): void {
            (new DatabaseSeeder)->run();
        });

        $this->assertDatabaseCount('users', 0);
    }

    public function test_database_seeder_creates_the_test_user_in_local(): void
    {
        $this->inEnvironment('local', function (): void {
            (new DatabaseSeeder)->run();
        });

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Test User', $user->name);
        $this->assertSame(UserRole::Member, $user->role);
    }

    public function test_database_seeder_creates_the_test_user_in_testing(): void
    {
        $this->inEnvironment('testing', function (): void {
            (new DatabaseSeeder)->run();
        });

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    private function inEnvironment(string $environment, callable $callback): void
    {
        $previousEnvironment = $this->app->environment();
        $this->app->detectEnvironment(fn (): string => $environment);

        try {
            $callback();
        } finally {
            $this->app->detectEnvironment(fn (): string => $previousEnvironment);
        }
    }
}
