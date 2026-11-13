<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ValueError;

class UserRoleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_and_member_roles_can_be_assigned_to_a_user(): void
    {
        $user = User::factory()->make();

        $user->role = UserRole::Admin;
        $this->assertTrue($user->isAdmin());

        $user->role = UserRole::Member;
        $this->assertTrue($user->isMember());

        $this->expectException(ValueError::class);
        $user->role = 'owner';
    }
}
