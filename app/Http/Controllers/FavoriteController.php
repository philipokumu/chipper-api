<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\CreateFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use Illuminate\Http\Response;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favoritePosts = $request->user()->favoritePosts;
        $favoriteUsers = $request->user()->favoriteUsers;

        return response()->json([
            'data' => [
                'posts' => FavoriteResource::collection($favoritePosts),
                'users' => FavoriteResource::collection($favoriteUsers)
            ]
        ]);
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $post->favorites()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $post->favorites()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
