<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import {url} {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from JSON URL up to a given limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = (int) $this->argument('limit');

        $this->info("Fetching users from: $url");

        // Fetch JSON from URL
        $response = Http::get($url);

        if (!$response->ok()) {
            $this->error("Failed to fetch users from the URL.");
            return 1;
        }

        $users = $response->json();

        if (empty($users)) {
            $this->info("No users found at the URL.");
            return 0;
        }

        $usersToImport = array_slice($users, 0, $limit);

        foreach ($usersToImport as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'), // Default password
                ]
            );

            $this->info("Imported user: {$userData['name']} ({$userData['email']})");
        }

        $this->info("Successfully imported " . count($usersToImport) . " user(s).");

        return 0;
    }
}