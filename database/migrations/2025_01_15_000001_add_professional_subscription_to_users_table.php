<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_professional')->default(false)->after('is_admin');
            $table->timestamp('subscription_started_at')->nullable()->after('is_professional');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
            $table->enum('subscription_status', ['active', 'expired', 'cancelled'])->default('expired')->after('subscription_expires_at');
            $table->string('subscription_payment_id')->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_professional',
                'subscription_started_at',
                'subscription_expires_at',
                'subscription_status',
                'subscription_payment_id',
            ]);
        });
    }
};

