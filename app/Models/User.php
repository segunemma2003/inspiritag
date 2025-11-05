<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'username',
        'email',
        'password',
        'bio',
        'profile_picture',
        'profession',
        'is_business',
        'is_admin',
        'is_professional',
        'last_seen',
        'interests',
        'notification_preferences',
        'fcm_token',
        'notifications_enabled',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_status',
        'subscription_payment_id',
        'website',
        'booking_link',
        'whatsapp_link',
        'linkedin_link',
        'instagram_link',
        'tiktok_link',
        'snapchat_link',
        'facebook_link',
        'twitter_link',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_business' => 'boolean',
            'is_admin' => 'boolean',
            'is_professional' => 'boolean',
            'last_seen' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'interests' => 'array',
            'notification_preferences' => 'array',
            'notifications_enabled' => 'boolean',
        ];
    }

    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function businessAccount()
    {
        return $this->hasOne(BusinessAccount::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function saves()
    {
        return $this->hasMany(Save::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function taggedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_user_tags');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function otps()
    {
        return $this->hasMany(Otp::class, 'email', 'email');
    }

    public function postAnalytics()
    {
        return $this->hasMany(PostAnalytic::class);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get active device tokens
     */
    public function getActiveDeviceTokensAttribute()
    {
        return $this->devices()->where('is_active', true)->pluck('device_token')->toArray();
    }

    /**
     * Check if user's email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark user's email as verified
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
}
