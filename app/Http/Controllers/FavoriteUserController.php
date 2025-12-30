<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FavoriteUserController extends Controller
{
    public function store(Request $request, User $user)
    {
        if ( $request->user()->id === $user->id ) {
            return response()->json(['message' => 'You cannot favorite yourself.'], Response::HTTP_BAD_REQUEST);
        }
        
        $user->favoritedBy()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, User $user)
    {
        $user->where('user_id', $request->user()->id)
            ->where('favorited_id', $user->id)
            ->where('favorited_type', User::class)
            ->delete();

        return response()->noContent();
    }
}
