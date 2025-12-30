<?php

namespace App\Listeners;

use App\Events\AuthorAddedNewPost;
use App\Models\Favorite;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyFavoritedUsersOfNewPostListener implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AuthorAddedNewPost $event): void
    {
        $author = $event->author;
        $post = $event->post;

        $usersWhoFavorited = $author->favoritedByUsers;

        Notification::send($usersWhoFavorited, new NewPostNotification($post));
    }
}
