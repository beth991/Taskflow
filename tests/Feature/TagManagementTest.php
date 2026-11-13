<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_tag(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('No tags yet');
        $this->get(route('admin.tags.create'))->assertOk()->assertSee('Create a tag');

        $this->post(route('admin.tags.store'), ['name' => 'Planning'])
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('status', 'Tag created successfully.');

        $tag = Tag::query()->where('name', 'Planning')->firstOrFail();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Planning']);

        $task = $admin->tasksCreated()->create(['title' => 'Tagged task']);
        $task->tags()->attach($tag->id);

        $this->get(route('admin.tags.index'))->assertOk()->assertSee('Planning')->assertSee('Used on 1 task');
        $this->get(route('admin.tags.edit', $tag))->assertOk()->assertSee('Planning');

        $this->put(route('admin.tags.update', $tag), ['name' => 'Roadmap'])
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('status', 'Tag updated successfully.');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Roadmap']);
        $this->assertDatabaseHas('tag_task', ['task_id' => $task->id, 'tag_id' => $tag->id]);

        $this->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('status', 'Tag deleted successfully.');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('tag_task', ['task_id' => $task->id, 'tag_id' => $tag->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_tag_names_are_required_limited_to_fifty_characters_and_unique(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::query()->create(['name' => 'Existing']);

        $this->actingAs($admin)->from(route('admin.tags.create'))
            ->post(route('admin.tags.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->from(route('admin.tags.create'))
            ->post(route('admin.tags.store'), ['name' => str_repeat('x', 51)])
            ->assertSessionHasErrors('name');

        $this->from(route('admin.tags.create'))
            ->post(route('admin.tags.store'), ['name' => 'Existing'])
            ->assertSessionHasErrors('name');

        $this->from(route('admin.tags.edit', $tag))
            ->put(route('admin.tags.update', $tag), ['name' => str_repeat('y', 51)])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Existing']);
    }

    public function test_admin_can_keep_the_current_tag_name_when_editing(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::query()->create(['name' => 'Same name']);

        $this->actingAs($admin)
            ->put(route('admin.tags.update', $tag), ['name' => 'Same name'])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_member_is_forbidden_from_all_tag_management_routes(): void
    {
        $member = User::factory()->create();
        $tag = Tag::query()->create(['name' => 'Restricted']);

        $this->actingAs($member)
            ->get(route('admin.tags.index'))->assertForbidden();
        $this->get(route('admin.tags.create'))->assertForbidden();
        $this->post(route('admin.tags.store'), ['name' => 'Member tag'])->assertForbidden();
        $this->get(route('admin.tags.edit', $tag))->assertForbidden();
        $this->put(route('admin.tags.update', $tag), ['name' => 'Changed'])->assertForbidden();
        $this->delete(route('admin.tags.destroy', $tag))->assertForbidden();

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Restricted']);
    }

    public function test_guests_are_redirected_from_tag_management(): void
    {
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
        $this->post(route('admin.tags.store'), ['name' => 'Guest tag'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('tags', 0);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        return $admin->fresh();
    }
}
