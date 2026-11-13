<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_create_a_task_with_multiple_existing_tags(): void
    {
        $member = User::factory()->create();
        $tags = collect([
            Tag::query()->create(['name' => 'Planning']),
            Tag::query()->create(['name' => 'Review']),
        ]);

        $this->actingAs($member)->post(route('tasks.store'), [
            'title' => 'Prepare sprint',
            'tag_ids' => $tags->pluck('id')->all(),
            'sync_tags' => '1',
        ])->assertRedirect(route('tasks.index'));

        $task = $member->tasksCreated()->where('title', 'Prepare sprint')->firstOrFail();

        $this->assertEqualsCanonicalizing($tags->pluck('id')->all(), $task->tags->modelKeys());
    }

    public function test_a_tag_can_be_reused_by_multiple_tasks(): void
    {
        $creator = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'Shared']);
        $firstTask = $creator->tasksCreated()->create(['title' => 'First shared task']);
        $secondTask = $creator->tasksCreated()->create(['title' => 'Second shared task']);

        $firstTask->tags()->sync([$tag->id]);
        $secondTask->tags()->sync([$tag->id]);

        $this->assertEqualsCanonicalizing(
            [$firstTask->id, $secondTask->id],
            $tag->fresh()->tasks->modelKeys(),
        );
    }

    public function test_task_update_syncs_selected_tags_and_can_remove_all_tags(): void
    {
        $creator = User::factory()->create();
        $oldTag = Tag::query()->create(['name' => 'Old tag']);
        $keptTag = Tag::query()->create(['name' => 'Kept tag']);
        $task = $creator->tasksCreated()->create(['title' => 'Sync tags']);
        $task->tags()->sync([$oldTag->id]);

        $this->actingAs($creator)->put(route('tasks.update', $task), [
            'title' => 'Sync tags',
            'notes' => null,
            'tag_ids' => [$keptTag->id],
            'sync_tags' => '1',
        ])->assertRedirect(route('tasks.index'));

        $this->assertEquals([$keptTag->id], $task->fresh()->tags->modelKeys());
        $this->assertDatabaseMissing('tag_task', ['task_id' => $task->id, 'tag_id' => $oldTag->id]);

        $this->put(route('tasks.update', $task), [
            'title' => 'Sync tags',
            'notes' => null,
            'sync_tags' => '1',
        ])->assertRedirect(route('tasks.index'));

        $this->assertCount(0, $task->fresh()->tags);
    }

    public function test_invalid_or_duplicate_tag_ids_are_rejected_without_creating_a_task(): void
    {
        $member = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'Valid tag']);

        $this->actingAs($member)->from(route('tasks.create'))->post(route('tasks.store'), [
            'title' => 'Invalid tags',
            'tag_ids' => [$tag->id, 999999],
            'sync_tags' => '1',
        ])->assertSessionHasErrors('tag_ids.1');

        $this->from(route('tasks.create'))->post(route('tasks.store'), [
            'title' => 'Duplicate tags',
            'tag_ids' => [$tag->id, $tag->id],
            'sync_tags' => '1',
        ])->assertSessionHasErrors('tag_ids.1');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_user_cannot_change_tags_on_a_task_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $oldTag = Tag::query()->create(['name' => 'Protected old']);
        $newTag = Tag::query()->create(['name' => 'Protected new']);
        $task = $owner->tasksCreated()->create(['title' => 'Protected tags']);
        $task->tags()->sync([$oldTag->id]);

        $this->actingAs($intruder)->put(route('tasks.update', $task), [
            'title' => 'Changed title',
            'tag_ids' => [$newTag->id],
            'sync_tags' => '1',
        ])->assertNotFound();

        $this->assertEquals([$oldTag->id], $task->fresh()->tags->modelKeys());
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Protected tags']);
    }

    public function test_task_list_and_details_display_tags_without_disclosing_unrelated_tasks(): void
    {
        $creator = User::factory()->create();
        $outsider = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'Visible tag']);
        $task = $creator->tasksCreated()->create(['title' => 'Task with visible tag']);
        $task->tags()->attach($tag->id);
        $creator->tasksCreated()->create(['title' => 'Task without tags']);

        $this->actingAs($creator)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Visible tag');

        $this->get(route('tasks.show', $task))->assertOk()->assertSee('Visible tag');
        $this->actingAs($outsider)->get(route('tasks.index'))->assertDontSee('Task with visible tag');
    }

    public function test_task_form_explains_when_no_tags_are_available(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('No tags are available yet.');
    }
}
