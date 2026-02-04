<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_published_posts()
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_404_for_draft_posts()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_scheduled_posts()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_guest_cannot_create_post()
    {
        $response = $this->post('/posts', []);

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_post()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = [
            'title' => 'Test Post',
            'content' => 'This is test content',
            'is_draft' => true,
        ];

        $response = $this->actingAs($user)->postJson('/posts', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', ['title' => 'Test Post']);
    }

    public function test_only_author_can_update_post()
    {
        /** @var User $author */
        $author = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($otherUser)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    }

    public function test_author_can_update_own_post()
    {
        /** @var User $author */
        $author = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($author)
            ->putJson("/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', ['title' => 'Updated Title']);
    }

    public function test_only_author_can_delete_post()
    {
        /** @var User $author */
        $author = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/posts/{$post->id}");

        $response->assertForbidden();
    }

    public function test_author_can_delete_own_post()
    {
        /** @var User $author */
        $author = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($author)
            ->deleteJson("/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
