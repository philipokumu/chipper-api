<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Support\Arr;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('public');
    }

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

    public function test_users_get_notification_when_favorited_author_creates_new_post()
    {
        $author = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Both users favorite the author
        $this->actingAs($user1)->postJson(route('user.favorites.store', ['user' => $author]));
        $this->actingAs($user2)->postJson(route('user.favorites.store', ['user' => $author]));

        $response = $this->actingAs($author)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ])->assertCreated();

        Notification::assertSentTo([$user1, $user2], NewPostNotification::class);

         // Assert notification NOT sent to author
        Notification::assertNotSentTo($author, NewPostNotification::class);
    }

    public function test_user_can_attach_image_to_post()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Post with image',
                    'body' => 'This post has an image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
        ]);

        $postId = Arr::get($response->json(), 'data.id');
        $post = Post::find($postId);
        $this->assertCount(1, $post->getMedia('gallery'));
    }

    public function test_it_rejects_invalid_image_extensions()
    {
        // Create a fake PDF instead of an image
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $invalidFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('image');
    }

    public function test_it_accepts_valid_webp_extensions()
    {
        // Create a valid WebP fake image
        $user = User::factory()->create();
        $validFile = UploadedFile::fake()->image('photo.webp');

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $validFile,
        ]);

        $response->assertStatus(201);
    }

    public function test_it_accepts_valid_png_extensions()
    {
        // Create a valid PNG fake image
        $user = User::factory()->create();
        $validFile = UploadedFile::fake()->image('photo.png');
        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $validFile,
        ]);

        $response->assertStatus(201);
    }

    public function test_it_accepts_valid_jpg_extensions()
    {
        // Create a valid JPG fake image
        $user = User::factory()->create();
        $validFile = UploadedFile::fake()->image('photo.jpg');
        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $validFile,
        ]);

        $response->assertStatus(201);
    }

    public function test_it_accepts_valid_gif_extensions()
    {
        // Create a valid GIF fake image
        $user = User::factory()->create();
        $validFile = UploadedFile::fake()->image('photo.gif');
        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Post with image',
            'body' => 'This post has an image.',
            'image' => $validFile,
        ]);
        
        $response->assertStatus(201);
    }
      
}
