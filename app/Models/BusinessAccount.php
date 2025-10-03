<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessAccount extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'business_type',
        'website',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'instagram_handle',
        'facebook_url',
        'tiktok_handle',
        'linkedin_url',
        'whatsapp_number',
        'x_handle',
        'business_hours',
        'services',
        'rating',
        'reviews_count',
        'is_verified',
        'accepts_bookings',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'services' => 'array',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_verified' => 'boolean',
            'accepts_bookings' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'user_id');
    }
}
