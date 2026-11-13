<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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
            ->assertSee('Manage users')
            ->assertDontSee('Member workspace');
    }

    public function test_member_sees_member_dashboard_and_navigation(): void
    {
        $member = User::factory()->create()->fresh();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Member workspace')
            ->assertSee('Member dashboard')
            ->assertSee('My tasks')
            ->assertSee('View my tasks')
            ->assertDontSee('Manage users')
            ->assertDontSee('Admin workspace');
    }

    public function test_admin_uses_task_features_without_access_to_another_users_task(): void
    {
        $admin = $this->createAdmin();
        $member = User::factory()->create()->fresh();
        $adminTask = $admin->tasksCreated()->create(['title' => 'Admin owned task']);
        $memberTask = $member->tasksCreated()->create(['title' => 'Member private task']);
        $memberTask->assignees()->attach($admin);

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

    public function test_member_can_see_tasks_assigned_to_them_without_management_actions(): void
    {
        $admin = $this->createAdmin();
        $member = User::factory()->create()->fresh();
        $otherMember = User::factory()->create(['name' => 'Second Assignee']);
        $otherUser = User::factory()->create(['name' => 'Other Creator']);
        $assignedTask = $admin->tasksCreated()->create([
            'title' => 'Assigned work item',
            'notes' => 'Inspect the staging checklist.',
        ]);
        $assignedTask->assignees()->attach($member);
        $assignedTask->assignees()->attach($otherMember);
        $unrelatedTask = $otherUser->tasksCreated()->create(['title' => 'Unrelated private task']);

        $this->assertTrue(Gate::forUser($member)->allows('view', $assignedTask));
        $this->assertFalse(Gate::forUser($member)->allows('update', $assignedTask));

        $this->actingAs($member)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Assigned work item')
            ->assertSee('Inspect the staging checklist.')
            ->assertSee('Assigned to you by '.$admin->name)
            ->assertSee('Confirm complete')
            ->assertDontSee('Unrelated private task')
            ->assertDontSee('href="'.route('tasks.edit', $assignedTask).'"')
            ->assertDontSee('action="'.route('tasks.destroy', $assignedTask).'"');

        $this->patch(route('tasks.status.update', $assignedTask), ['is_completed' => true])
            ->assertRedirect(route('tasks.index'));

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Assigned work item')
            ->assertSee('Completed')
            ->assertDontSee('This member completed the task')
            ->assertDontSee('Confirm complete');

        $this->get(route('tasks.show', $assignedTask))
            ->assertOk()
            ->assertDontSee('Completed by')
            ->assertDontSee('Completed this task');

        $this->patch(route('tasks.status.update', $assignedTask), ['is_completed' => false])->assertForbidden();
        $this->get(route('tasks.edit', $assignedTask))->assertNotFound();

        $this->actingAs($admin)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Assigned work item')
            ->assertSee('Completed')
            ->assertSee('This member completed the task')
            ->assertSee('aria-label="This member completed the task">Completed</span>', false)
            ->assertSeeInOrder([$member->name, 'This member completed the task', $otherMember->name]);

        $this->get(route('tasks.show', $assignedTask))
            ->assertOk()
            ->assertSee('Task details')
            ->assertSee('Completed by')
            ->assertSeeInOrder([$member->name, 'Completed this task', $otherMember->name]);

        $this->assertDatabaseHas('tasks', [
            'id' => $assignedTask->id,
            'is_completed' => true,
            'completed_by' => $member->id,
        ]);
        $this->assertDatabaseHas('tasks', ['id' => $unrelatedTask->id]);
    }

    public function test_an_unrelated_user_cannot_view_task_details(): void
    {
        $creator = User::factory()->create();
        $outsider = User::factory()->create();
        $task = $creator->tasksCreated()->create(['title' => 'Private task details']);

        $this->actingAs($outsider)
            ->get(route('tasks.show', $task))
            ->assertNotFound();
    }

    public function test_status_filters_include_assigned_tasks_without_leaking_unrelated_tasks(): void
    {
        $member = User::factory()->create()->fresh();
        $creator = User::factory()->create();
        $completedTask = $creator->tasksCreated()->create([
            'title' => 'Assigned completed work',
            'is_completed' => true,
        ]);
        $incompleteTask = $creator->tasksCreated()->create(['title' => 'Assigned incomplete work']);
        $completedTask->assignees()->attach($member);
        $incompleteTask->assignees()->attach($member);

        $this->actingAs($member)
            ->get(route('tasks.index', ['filter' => 'completed']))
            ->assertOk()
            ->assertSee('Assigned completed work')
            ->assertDontSee('Assigned incomplete work');

        $this->get(route('tasks.index', ['filter' => 'incomplete']))
            ->assertOk()
            ->assertSee('Assigned incomplete work')
            ->assertDontSee('Assigned completed work');
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
