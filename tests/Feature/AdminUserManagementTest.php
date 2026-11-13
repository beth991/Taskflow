<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_paginated_users_without_sensitive_fields(): void
    {
        $admin = $this->createAdmin('Admin Account');
        $member = User::factory()->create(['name' => 'Member Account']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manage users')
            ->assertSee('Admin Account')
            ->assertSee('Member Account')
            ->assertSee($member->email)
            ->assertDontSee($admin->password)
            ->assertDontSee($member->password)
            ->assertDontSee('remember_token');
    }

    public function test_member_cannot_access_user_management(): void
    {
        $admin = $this->createAdmin();
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.users.index'))
            ->assertForbidden();
        $this->patch(route('admin.users.role.update', $admin), ['role' => 'member'])
            ->assertForbidden();
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $admin = $this->createAdmin();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->patch(route('admin.users.role.update', $admin), ['role' => 'member'])
            ->assertRedirect(route('login'));
    }

    public function test_user_list_is_paginated(): void
    {
        $admin = $this->createAdmin();
        User::factory()->count(16)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => $users->perPage() === 15
                && $users->count() === 15
                && $users->hasMorePages());
    }

    public function test_admin_can_promote_a_member_to_admin(): void
    {
        $admin = $this->createAdmin();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $member), ['role' => UserRole::Admin->value])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'User role updated successfully.');

        $this->assertSame(UserRole::Admin, $member->fresh()->role);
    }

    public function test_admin_can_demote_another_admin_while_an_admin_remains(): void
    {
        $actor = $this->createAdmin('Actor Admin');
        $target = $this->createAdmin('Target Admin');

        $this->actingAs($actor)
            ->patch(route('admin.users.role.update', $target), ['role' => UserRole::Member->value])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(UserRole::Member, $target->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $actor->id, 'role' => UserRole::Admin->value]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $admin), ['role' => UserRole::Member->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_a_stale_admin_cannot_change_roles_after_becoming_a_member(): void
    {
        $staleAdmin = $this->createAdmin('Stale Admin');
        $target = User::factory()->create();

        User::query()
            ->whereKey($staleAdmin->getKey())
            ->update(['role' => UserRole::Member->value]);

        $this->actingAs($staleAdmin)
            ->patch(route('admin.users.role.update', $target), ['role' => UserRole::Admin->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Member, $staleAdmin->fresh()->role);
        $this->assertSame(UserRole::Member, $target->fresh()->role);
    }

    public function test_a_stale_admin_request_cannot_demote_the_last_remaining_admin(): void
    {
        $firstAdmin = $this->createAdmin('First Admin');
        $secondAdmin = $this->createAdmin('Second Admin');

        $this->actingAs($firstAdmin)
            ->patch(route('admin.users.role.update', $secondAdmin), ['role' => UserRole::Member->value])
            ->assertRedirect(route('admin.users.index'));

        // Simulates a request authenticated as Admin before the first concurrent update committed.
        $this->actingAs($secondAdmin)
            ->patch(route('admin.users.role.update', $firstAdmin), ['role' => UserRole::Member->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Admin, $firstAdmin->fresh()->role);
        $this->assertSame(UserRole::Member, $secondAdmin->fresh()->role);
        $this->assertSame(1, User::query()->where('role', UserRole::Admin->value)->count());
    }

    public function test_invalid_roles_are_rejected_without_changing_user_data(): void
    {
        $admin = $this->createAdmin();
        $target = User::factory()->create();
        $original = $target->only(['name', 'email', 'password']);

        $this->actingAs($admin)->from(route('admin.users.index'))
            ->patch(route('admin.users.role.update', $target), ['role' => 'owner'])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Member, $target->fresh()->role);
        $this->assertSame($original['name'], $target->fresh()->name);
        $this->assertSame($original['email'], $target->fresh()->email);
        $this->assertSame($original['password'], $target->fresh()->password);
    }

    public function test_extra_request_fields_cannot_change_other_user_attributes(): void
    {
        $admin = $this->createAdmin();
        $target = User::factory()->create(['name' => 'Keep this name']);
        $email = $target->email;
        $password = $target->password;
        $rememberToken = $target->remember_token;

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $target), [
                'role' => UserRole::Admin->value,
                'name' => 'Injected name',
                'email' => 'injected@example.test',
                'password' => 'injected-password',
                'remember_token' => 'injected-token',
            ])
            ->assertRedirect(route('admin.users.index'));

        $updated = $target->fresh();
        $this->assertSame(UserRole::Admin, $updated->role);
        $this->assertSame('Keep this name', $updated->name);
        $this->assertSame($email, $updated->email);
        $this->assertSame($password, $updated->password);
        $this->assertSame($rememberToken, $updated->remember_token);
    }

    private function createAdmin(string $name = 'Admin'): User
    {
        $admin = User::factory()->create(['name' => $name]);
        $admin->forceFill(['role' => UserRole::Admin])->save();

        return $admin->fresh();
    }
}
