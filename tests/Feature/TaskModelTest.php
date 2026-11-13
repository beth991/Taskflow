<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_a_task_and_is_its_creator(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = $creator->tasksCreated()->create([
            'title' => 'Prepare release notes',
            'is_completed' => false,
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $creator->id,
            'title' => 'Prepare release notes',
            'is_completed' => false,
        ]);
        $this->assertTrue($task->creator->is($creator));
        $this->assertIsBool($task->is_completed);
    }

    public function test_a_user_can_have_multiple_created_tasks(): void
    {
        $creator = User::factory()->create();

        $creator->tasksCreated()->createMany([
            ['title' => 'Review onboarding'],
            ['title' => 'Update the handbook'],
        ]);

        $this->assertCount(2, $creator->tasksCreated);
    }

    public function test_a_task_can_have_multiple_assignees(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Plan sprint']);
        $assignees = User::factory()->count(2)->create();

        $task->assignees()->attach($assignees->modelKeys());

        $this->assertCount(2, $task->fresh()->assignees);
        $this->assertCount(1, $assignees->first()->assignedTasks);
    }

    public function test_tasks_and_tags_have_a_many_to_many_relationship(): void
    {
        $firstTask = User::factory()->create()->tasksCreated()->create(['title' => 'Tag first task']);
        $secondTask = User::factory()->create()->tasksCreated()->create(['title' => 'Tag second task']);
        $firstTag = Tag::query()->create(['name' => 'Planning']);
        $secondTag = Tag::query()->create(['name' => 'Review']);

        $firstTask->tags()->attach([$firstTag->id, $secondTag->id]);
        $secondTask->tags()->attach($firstTag->id);

        $this->assertEqualsCanonicalizing(
            [$firstTag->id, $secondTag->id],
            $firstTask->fresh()->tags->modelKeys(),
        );
        $this->assertEqualsCanonicalizing(
            [$firstTask->id, $secondTask->id],
            $firstTag->fresh()->tasks->modelKeys(),
        );
    }

    public function test_tag_pivot_records_timestamps(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Timestamp tag task']);
        $tag = Tag::query()->create(['name' => 'Timestamp']);

        $task->tags()->attach($tag->id);

        $pivot = $task->fresh()->tags->first()->pivot;

        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_a_tag_cannot_be_attached_to_the_same_task_twice(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Duplicate tag task']);
        $tag = Tag::query()->create(['name' => 'Duplicate']);
        $task->tags()->attach($tag->id);

        $this->expectException(QueryException::class);

        $task->tags()->attach($tag->id);
    }

    public function test_tag_pivot_rejects_a_nonexistent_task_foreign_key(): void
    {
        $tag = Tag::query()->create(['name' => 'Foreign key']);

        $this->expectException(QueryException::class);

        DB::table('tag_task')->insert([
            'task_id' => 999999,
            'tag_id' => $tag->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_tag_pivot_rejects_a_nonexistent_tag_foreign_key(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Foreign key task']);

        $this->expectException(QueryException::class);

        DB::table('tag_task')->insert([
            'task_id' => $task->id,
            'tag_id' => 999999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_a_task_or_tag_cascades_its_tag_pivot_rows(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Cascade tag task']);
        $tag = Tag::query()->create(['name' => 'Cascade']);
        $task->tags()->attach($tag->id);

        $task->delete();

        $this->assertDatabaseCount('tag_task', 0);

        $anotherTask = User::factory()->create()->tasksCreated()->create(['title' => 'Cascade tag delete']);
        $anotherTag = Tag::query()->create(['name' => 'Cascade tag removal']);
        $anotherTask->tags()->attach($anotherTag->id);
        $anotherTag->delete();

        $this->assertDatabaseCount('tag_task', 0);
        $this->assertDatabaseHas('tasks', ['id' => $anotherTask->id]);
    }

    public function test_tag_name_is_unique_and_declared_for_fifty_characters(): void
    {
        Tag::query()->create(['name' => 'Unique tag']);

        $migration = file_get_contents(database_path('migrations/2026_11_13_000006_create_tags_table.php'));
        $this->assertIsString($migration);
        $this->assertStringContainsString("\$table->string('name', 50)->unique()", $migration);

        $this->expectException(QueryException::class);
        Tag::query()->create(['name' => 'Unique tag']);
    }

    public function test_assignee_pivot_records_timestamps(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Prepare demo']);
        $assignee = User::factory()->create();

        $task->assignees()->attach($assignee->id);

        $pivot = $task->fresh()->assignees->first()->pivot;

        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_a_user_cannot_be_added_as_the_same_assignee_twice(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Check duplicate']);
        $assignee = User::factory()->create();
        $task->assignees()->attach($assignee->id);

        $this->expectException(QueryException::class);

        $task->assignees()->attach($assignee->id);
    }

    public function test_pivot_rejects_a_nonexistent_task_foreign_key(): void
    {
        $assignee = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('task_user')->insert([
            'task_id' => 999999,
            'user_id' => $assignee->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_pivot_rejects_a_nonexistent_user_foreign_key(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Check foreign key']);

        $this->expectException(QueryException::class);

        DB::table('task_user')->insert([
            'task_id' => $task->id,
            'user_id' => 999999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_creator_cannot_be_deleted_while_created_tasks_exist(): void
    {
        $creator = User::factory()->create();
        $creator->tasksCreated()->create(['title' => 'Keep ownership intact']);

        $this->expectException(QueryException::class);

        $creator->delete();
    }

    public function test_deleting_a_task_cascades_its_assignee_rows(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Remove pivot safely']);
        $task->assignees()->attach(User::factory()->create()->id);

        $task->delete();

        $this->assertDatabaseCount('task_user', 0);
    }

    public function test_new_tasks_default_to_incomplete(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => 'Start incomplete']);
        $task->refresh();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_completed' => false]);
        $this->assertFalse($task->is_completed);
    }

    public function test_task_title_column_is_defined_for_100_characters(): void
    {
        $task = User::factory()->create()->tasksCreated()->create(['title' => str_repeat('a', 100)]);

        $this->assertSame(100, strlen($task->title));
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => str_repeat('a', 100)]);

        // SQLite does not enforce VARCHAR(n), so verify the migration's declared MySQL limit.
        $migration = file_get_contents(database_path('migrations/2026_11_13_000001_create_tasks_table.php'));

        $this->assertIsString($migration);
        $this->assertTrue(str_contains($migration, '$table->string(\'title\', 100)'));
    }
}
