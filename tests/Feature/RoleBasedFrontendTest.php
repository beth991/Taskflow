<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_admin_dashboard_and_navigation(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin workspace')
            ->assertSee('Admin dashboard')
            ->assertSee('My task workspace')
            ->assertSee('Open my task workspace')
            ->assertDontSee('Member workspace');
    }

    public function test_member_sees_member_dashboard_and_navigation(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Member workspace')
            ->assertSee('Member dashboard')
            ->assertSee('My tasks')
            ->assertSee('View my tasks')
            ->assertDontSee('Admin workspace');
    }

    public function test_admin_uses_task_features_without_access_to_another_users_task(): void
    {
        $admin = $this->createAdmin();
        $member = User::factory()->create();
        $adminTask = $admin->tasksCreated()->create(['title' => 'Admin owned task']);
        $memberTask = $member->tasksCreated()->create(['title' => 'Member private task']);

        $this->actingAs($admin)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Admin owned task')
            ->assertDontSee('Member private task');

        $this->get(route('tasks.edit', $memberTask))->assertNotFound();
        $this->put(route('tasks.update', $memberTask), ['title' => 'Changed by Admin'])->assertNotFound();
        $this->delete(route('tasks.destroy', $memberTask))->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $memberTask->id, 'title' => 'Member private task']);
        $this->assertDatabaseHas('tasks', ['id' => $adminTask->id, 'title' => 'Admin owned task']);
    }

    public function test_guests_are_redirected_to_login_instead_of_a_role_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        return $admin->fresh();
    }
}
