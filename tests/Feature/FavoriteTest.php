<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_user_can_favorite_an_author()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('user.favorites.store', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $userToFavorite->id,
            'favoritable_type' => User::class,
        ]);
    }

    public function test_a_user_can_remove_an_author_from_his_favorites()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('user.favorites.destroy', ['user' => $userToFavorite]))
            ->assertNoContent();
    }

    public function test_user_cannot_favorite_a_post_twice()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(201);

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_user_cannot_favorite_an_author_twice()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('user.favorites.store', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('user.favorites.store', ['user' => $userToFavorite]))
            ->assertStatus(201);

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_cannot_favorite_themselves()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('user.favorites.store', ['user' => $user]))
            ->assertStatus(400);

        $this->assertDatabaseCount('favorites', 0);
    }
}
