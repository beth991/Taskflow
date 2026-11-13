<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_existing_user_receives_the_member_default_when_role_is_added(): void
    {
        $legacyUser = User::factory()->create([
            'name' => 'Legacy Member',
            'email' => 'legacy@example.test',
        ]);

        $migration = require database_path('migrations/2026_11_13_000003_add_role_to_users_table.php');
        $migration->down();

        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $migration->up();

        $legacyRecord = DB::table('users')->where('id', $legacyUser->id)->first();

        $this->assertNotNull($legacyRecord);
        $this->assertSame(User::ROLE_MEMBER, $legacyRecord->role);
        $this->assertSame('Legacy Member', $legacyRecord->name);
        $this->assertSame('legacy@example.test', $legacyRecord->email);
    }
}
