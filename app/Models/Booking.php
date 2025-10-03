<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'business_account_id',
        'service_name',
        'description',
        'appointment_date',
        'status',
        'price',
        'notes',
        'contact_phone',
        'contact_email',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessAccount::class);
    }
}
