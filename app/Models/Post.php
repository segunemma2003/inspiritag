<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'caption',
        'media_url',
        'media_type',
        'thumbnail_url',
        'media_metadata',
        'location',
        'is_public',
        'is_ads',
        'likes_count',
        'saves_count',
        'comments_count',
        'shares_count',
        'views_count',
        'impressions_count',
        'reach_count',
    ];

    protected function casts(): array
    {
        return [
            'media_metadata' => 'array',
            'is_public' => 'boolean',
            'is_ads' => 'boolean',
            'likes_count' => 'integer',
            'saves_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'views_count' => 'integer',
            'impressions_count' => 'integer',
            'reach_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function saves(): HasMany
    {
        return $this->hasMany(Save::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function taggedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_user_tags');
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isSavedBy(User $user): bool
    {
        return $this->saves()->where('user_id', $user->id)->exists();
    }

    public function isTaggedBy(User $user): bool
    {
        return $this->taggedUsers()->where('user_id', $user->id)->exists();
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(PostAnalytic::class);
    }
}
