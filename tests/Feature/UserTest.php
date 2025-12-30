<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    public function test_it_imports_users_from_json_url_up_to_given_limit()
    {
        // Fake HTTP request to the JSON URL
        $jsonUrl = 'https://jsonplaceholder.typicode.com/users';
        $fakeUsers = [
            [
                "id" => 1,
                "name" => "Leanne Graham",
                "username" => "Bret",
                "email" => "Sincere@april.biz",
            ],
            [
                "id" => 2,
                "name" => "Ervin Howell",
                "username" => "Antonette",
                "email" => "Shanna@melissa.tv",
            ],
            [
                "id" => 3,
                "name" => "Clementine Bauch",
                "username" => "Samantha",
                "email" => "Nathan@yesenia.net",
            ],
        ];

        Http::fake([
            $jsonUrl => Http::response($fakeUsers, 200)
        ]);

        // Limit to 2 users
        $limit = 2;

        // Run the command
        Artisan::call('users:import', [
            'url' => $jsonUrl,
            'limit' => $limit,
        ]);

        // Assert only $limit users are created
        $this->assertCount($limit, User::all());

        // Assert specific user details exist in DB
        $this->assertDatabaseHas('users', [
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);

        // Assert the third user was NOT imported due to limit
        $this->assertDatabaseMissing('users', [
            'name' => 'Clementine Bauch',
            'email' => 'Nathan@yesenia.net',
        ]);
    }
}
