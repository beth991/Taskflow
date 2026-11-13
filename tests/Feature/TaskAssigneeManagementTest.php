<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssigneeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_task_owner_can_add_one_assignee_and_pivot_timestamps_are_saved(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);

        $response = $this->actingAs($owner)->post(route('tasks.assignees.store', $task), [
            'user_ids' => [$assignee->id],
        ]);

        $response->assertRedirect(route('tasks.edit', $task));
        $pivot = $task->fresh()->assignees()->whereKey($assignee->id)->firstOrFail()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_a_task_owner_can_add_multiple_assignees(): void
    {
        $owner = User::factory()->create();
        $assignees = User::factory()->count(3)->create();
        $task = $this->createTask($owner);

        $this->actingAs($owner)->post(route('tasks.assignees.store', $task), [
            'user_ids' => $assignees->modelKeys(),
        ])->assertRedirect(route('tasks.edit', $task));

        $this->assertEqualsCanonicalizing($assignees->modelKeys(), $task->fresh()->assignees->modelKeys());
    }

    public function test_a_task_owner_can_remove_an_assignee(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($assignee->id);

        $this->actingAs($owner)->delete(route('tasks.assignees.destroy', $task), [
            'user_id' => $assignee->id,
        ])->assertRedirect(route('tasks.edit', $task));

        $this->assertDatabaseMissing('task_user', ['task_id' => $task->id, 'user_id' => $assignee->id]);
    }

    public function test_assignee_addition_rejects_an_unknown_user_without_changing_existing_assignments(): void
    {
        $owner = User::factory()->create();
        $existingAssignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($existingAssignee->id);

        $response = $this->actingAs($owner)
            ->from(route('tasks.edit', $task))
            ->post(route('tasks.assignees.store', $task), ['user_ids' => [$existingAssignee->id, 999999]]);

        $response->assertRedirect(route('tasks.edit', $task));
        $response->assertSessionHasErrors('user_ids.1');
        $this->assertEquals([$existingAssignee->id], $task->fresh()->assignees->modelKeys());
    }

    public function test_repeated_assignees_are_not_duplicated(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($assignee->id);

        $this->actingAs($owner)->post(route('tasks.assignees.store', $task), [
            'user_ids' => [$assignee->id],
        ])->assertRedirect(route('tasks.edit', $task));

        $this->assertDatabaseCount('task_user', 1);
        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $assignee->id]);
    }

    public function test_duplicate_ids_in_one_assignee_request_are_rejected_without_changes(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);

        $response = $this->actingAs($owner)
            ->from(route('tasks.edit', $task))
            ->post(route('tasks.assignees.store', $task), ['user_ids' => [$assignee->id, $assignee->id]]);

        $response->assertRedirect(route('tasks.edit', $task));
        $response->assertSessionHasErrors('user_ids.1');
        $this->assertDatabaseCount('task_user', 0);
    }

    public function test_removing_an_unknown_user_is_rejected_without_changing_assignments(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($assignee->id);

        $response = $this->actingAs($owner)
            ->from(route('tasks.edit', $task))
            ->delete(route('tasks.assignees.destroy', $task), ['user_id' => 999999]);

        $response->assertRedirect(route('tasks.edit', $task));
        $response->assertSessionHasErrors('user_id');
        $this->assertEquals([$assignee->id], $task->fresh()->assignees->modelKeys());
    }

    public function test_another_user_cannot_add_assignees_to_a_task_and_no_assignments_change(): void
    {
        $owner = User::factory()->create();
        $existingAssignee = User::factory()->create();
        $requestedAssignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($existingAssignee->id);

        $this->actingAs(User::factory()->create())
            ->post(route('tasks.assignees.store', $task), ['user_ids' => [$requestedAssignee->id]])
            ->assertNotFound();

        $this->assertEquals([$existingAssignee->id], $task->fresh()->assignees->modelKeys());
    }

    public function test_another_user_cannot_remove_assignees_from_a_task_and_assignments_remain(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask($owner);
        $task->assignees()->attach($assignee->id);

        $this->actingAs(User::factory()->create())
            ->delete(route('tasks.assignees.destroy', $task), ['user_id' => $assignee->id])
            ->assertNotFound();

        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $assignee->id]);
    }

    public function test_the_task_list_and_edit_page_display_assignees(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Assigned Teammate']);
        $task = $this->createTask($owner);
        $task->assignees()->attach($assignee->id);

        $this->actingAs($owner)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Assigned Teammate')
            ->assertDontSee($assignee->email);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Manage assignees')
            ->assertSee('Assigned Teammate')
            ->assertDontSee($assignee->email)
            ->assertSee(route('tasks.assignees.store', $task));
    }

    public function test_guests_cannot_manage_assignees(): void
    {
        $task = $this->createTask(User::factory()->create());
        $assignee = User::factory()->create();

        $this->post(route('tasks.assignees.store', $task), ['user_ids' => [$assignee->id]])
            ->assertRedirect(route('login'));
        $this->delete(route('tasks.assignees.destroy', $task), ['user_id' => $assignee->id])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('task_user', 0);
    }

    private function createTask(User $creator): Task
    {
        return $creator->tasksCreated()->create(['title' => 'Assignee test task']);
    }
}
