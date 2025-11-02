<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'otp',
        'type',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Generate a 6-digit OTP
     */
    public static function generateOTP(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for an email
     */
    public static function createOTP(string $email, string $type = 'registration'): self
    {
        
        self::where('email', $email)
            ->where('type', $type)
            ->where('is_used', false)
            ->delete();

        return self::create([
            'email' => $email,
            'otp' => self::generateOTP(),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }

    /**
     * Check if OTP is valid
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    /**
     * Verify OTP for an email
     */
    public static function verifyOTP(string $email, string $otp, string $type = 'registration'): ?self
    {
        $otpRecord = self::where('email', $email)
            ->where('otp', $otp)
            ->where('type', $type)
            ->where('is_used', false)
            ->first();

        if ($otpRecord && $otpRecord->isValid()) {
            return $otpRecord;
        }

        return null;
    }

    /**
     * Clean up expired OTPs
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now())->delete();
    }
}
