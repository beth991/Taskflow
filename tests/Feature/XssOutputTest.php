<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class XssOutputTest extends TestCase
{
    use RefreshDatabase;

    private const HTML_PAYLOAD = '<img src=x onerror=alert(1)>';

    public function test_user_names_are_escaped_on_admin_user_management_page(): void
    {
        $admin = $this->createAdmin();
        User::factory()->create(['name' => self::HTML_PAYLOAD]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $this->assertEscapedPayload($response);
    }

    public function test_creator_and_assignee_names_are_escaped_on_task_details(): void
    {
        $creator = User::factory()->create(['name' => '<img src=x onerror=alert(2)>'])->fresh();
        $assignee = User::factory()->create(['name' => '<img src=x onerror=alert(3)>'])->fresh();
        $task = $creator->tasksCreated()->create(['title' => 'Safe task']);
        $task->assignees()->attach($assignee);

        $response = $this->actingAs($creator)->get(route('tasks.show', $task));

        $this->assertEscapedPayload($response, '<img src=x onerror=alert(2)>');
        $this->assertEscapedPayload($response, '<img src=x onerror=alert(3)>');
    }

    public function test_task_notes_are_escaped_in_task_list_and_details(): void
    {
        $creator = User::factory()->create()->fresh();
        $task = $creator->tasksCreated()->create([
            'title' => 'Task with untrusted notes',
            'notes' => self::HTML_PAYLOAD,
        ]);

        $this->actingAs($creator);

        $listResponse = $this->get(route('tasks.index'));
        $this->assertEscapedPayload($listResponse);

        $detailResponse = $this->get(route('tasks.show', $task));
        $this->assertEscapedPayload($detailResponse);
    }

    public function test_tag_names_are_escaped_in_tag_management_and_task_details(): void
    {
        $admin = $this->createAdmin();
        $task = $admin->tasksCreated()->create(['title' => 'Tagged task']);
        $tag = Tag::query()->create(['name' => self::HTML_PAYLOAD]);
        $task->tags()->attach($tag);

        $this->actingAs($admin);

        $tagListResponse = $this->get(route('admin.tags.index'));
        $this->assertEscapedPayload($tagListResponse);

        $taskDetailResponse = $this->get(route('tasks.show', $task));
        $this->assertEscapedPayload($taskDetailResponse);
    }

    private function assertEscapedPayload(TestResponse $response, string $payload = self::HTML_PAYLOAD): void
    {
        $escapedPayload = htmlspecialchars($payload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $response
            ->assertOk()
            ->assertSee($escapedPayload, false)
            ->assertDontSee($payload, false);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create()->fresh();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        return $admin->fresh();
    }
}
