<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get all favorites where this user is the favoritable (polymorphic).
     */
    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function favoritePosts()
    {
        return $this->morphedByMany(
            Post::class,
            'favoritable',
            'favorites',
            'user_id',
            'favoritable_id'
        );
    }

    public function favoriteUsers()
    {
        return $this->morphedByMany(
            User::class,
            'favoritable',
            'favorites',
            'user_id',
            'favoritable_id'
        );
    }

    public function favoritedByUsers()
    {
        return $this->morphedByMany(
            User::class,
            'favoritable',
            'favorites',
            'favoritable_id',
            'user_id'
        );
    }
}
