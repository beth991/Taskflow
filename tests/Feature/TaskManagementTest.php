<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_task_with_themselves_as_creator(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Plan weekly review',
            'user_id' => $otherUser->id,
            'is_completed' => true,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Plan weekly review',
            'is_completed' => false,
        ]);
        $this->assertDatabaseMissing('tasks', ['user_id' => $otherUser->id, 'title' => 'Plan weekly review']);
    }

    public function test_a_task_title_is_required(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), ['title' => '']);

        $response->assertRedirect(route('tasks.create'));
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_99_character_title_is_accepted(): void
    {
        $user = User::factory()->create();
        $title = str_repeat('a', 99);

        $this->actingAs($user)->post(route('tasks.store'), ['title' => $title])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => $title]);
    }

    public function test_a_100_character_title_is_accepted(): void
    {
        $user = User::factory()->create();
        $title = str_repeat('b', 100);

        $this->actingAs($user)->post(route('tasks.store'), ['title' => $title])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => $title]);
    }

    public function test_a_101_character_title_is_rejected_at_request_time(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), ['title' => str_repeat('c', 101)]);

        $response->assertRedirect(route('tasks.create'));
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_the_task_list_contains_only_tasks_created_by_the_current_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createTask($user, 'My private task');
        $this->createTask($otherUser, 'Someone else task');

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk()->assertSee('My private task')->assertDontSee('Someone else task');
    }

    public function test_a_user_cannot_open_another_users_task_for_editing(): void
    {
        $task = $this->createTask(User::factory()->create(), 'Private edit target');

        $this->actingAs(User::factory()->create())
            ->get(route('tasks.edit', $task))
            ->assertNotFound();
    }

    public function test_a_user_cannot_update_another_users_task(): void
    {
        $task = $this->createTask(User::factory()->create(), 'Original private title');

        $this->actingAs(User::factory()->create())
            ->put(route('tasks.update', $task), ['title' => 'Changed by another user'])
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original private title']);
    }

    public function test_a_user_cannot_change_another_users_task_status(): void
    {
        $task = $this->createTask(User::factory()->create(), 'Protected status', ['is_completed' => false]);

        $this->actingAs(User::factory()->create())
            ->patch(route('tasks.status.update', $task), ['is_completed' => true])
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Protected status',
            'is_completed' => false,
        ]);
    }

    public function test_a_user_cannot_delete_another_users_task(): void
    {
        $task = $this->createTask(User::factory()->create(), 'Protected task');

        $this->actingAs(User::factory()->create())
            ->delete(route('tasks.destroy', $task))
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_a_user_can_update_their_task_title_without_changing_creator_or_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = $this->createTask($user, 'Old title', ['is_completed' => true]);

        $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => 'Updated title',
            'user_id' => $otherUser->id,
            'is_completed' => false,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
            'title' => 'Updated title',
            'is_completed' => true,
        ]);
    }

    public function test_an_empty_title_is_rejected_when_updating_a_task(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user, 'Original title');

        $response = $this->actingAs($user)
            ->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), ['title' => '']);

        $response->assertRedirect(route('tasks.edit', $task));
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original title']);
    }

    public function test_a_101_character_title_is_rejected_when_updating_a_task(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user, 'Original title');

        $response = $this->actingAs($user)
            ->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), ['title' => str_repeat('x', 101)]);

        $response->assertRedirect(route('tasks.edit', $task));
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original title']);
    }

    public function test_a_user_can_delete_their_task_and_its_pivot_rows(): void
    {
        $user = User::factory()->create();
        $task = $this->createTask($user, 'Task to delete');
        $task->assignees()->attach(User::factory()->create()->id);

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseCount('task_user', 0);
    }

    public function test_status_can_be_toggled_without_updating_other_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = $this->createTask($user, 'Status target');

        $this->actingAs($user)->patch(route('tasks.status.update', $task), [
            'is_completed' => '1',
            'title' => 'Injected title',
            'user_id' => $otherUser->id,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
            'title' => 'Status target',
            'is_completed' => true,
        ]);

        $this->actingAs($user)->patch(route('tasks.status.update', $task), ['is_completed' => '0']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_completed' => false]);
    }

    public function test_all_completed_and_incomplete_filters_show_the_correct_tasks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createTask($user, 'Completed task', ['is_completed' => true]);
        $this->createTask($user, 'Incomplete task');
        $this->createTask($otherUser, 'Foreign completed task', ['is_completed' => true]);

        $this->actingAs($user)->get(route('tasks.index', ['filter' => 'all']))
            ->assertOk()
            ->assertSee('Completed task')
            ->assertSee('Incomplete task')
            ->assertDontSee('Foreign completed task');

        $this->get(route('tasks.index', ['filter' => 'completed']))
            ->assertOk()
            ->assertSee('Completed task')
            ->assertDontSee('Incomplete task')
            ->assertDontSee('Foreign completed task');

        $this->get(route('tasks.index', ['filter' => 'incomplete']))
            ->assertOk()
            ->assertSee('Incomplete task')
            ->assertDontSee('Completed task')
            ->assertDontSee('Foreign completed task');
    }

    public function test_task_pagination_shows_at_most_ten_tasks_and_next_page_works(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 11) as $number) {
            $this->createTask($user, "Pagination task {$number}");
        }

        $firstPage = $this->actingAs($user)->get(route('tasks.index'));

        $firstPage->assertOk()->assertViewHas('tasks', function ($tasks): bool {
            return $tasks->perPage() === 10
                && $tasks->count() === 10
                && $tasks->hasMorePages()
                && $tasks->nextPageUrl() !== null;
        });

        $secondPage = $this->get(route('tasks.index', ['page' => 2]));

        $secondPage->assertOk()->assertViewHas('tasks', function ($tasks): bool {
            return $tasks->perPage() === 10
                && $tasks->currentPage() === 2
                && $tasks->count() === 1
                && ! $tasks->hasMorePages();
        });
    }

    public function test_an_invalid_filter_is_rejected(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('tasks.index', ['filter' => 'unexpected']));

        $response->assertSessionHasErrors('filter');
    }

    public function test_a_guest_cannot_access_task_routes(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->get(route('tasks.create'))->assertRedirect(route('login'));
        $this->post(route('tasks.store'), ['title' => 'Guest task'])->assertRedirect(route('login'));
        $this->get(route('tasks.edit', 1))->assertRedirect(route('login'));
        $this->put(route('tasks.update', 1), ['title' => 'Guest edit'])->assertRedirect(route('login'));
        $this->delete(route('tasks.destroy', 1))->assertRedirect(route('login'));
        $this->patch(route('tasks.status.update', 1), ['is_completed' => true])->assertRedirect(route('login'));
    }

    private function createTask(User $creator, string $title, array $attributes = []): Task
    {
        return $creator->tasksCreated()->create(array_merge([
            'title' => $title,
            'is_completed' => false,
        ], $attributes));
    }
}
