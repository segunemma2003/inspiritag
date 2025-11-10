<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('apple_product_id')->unique()->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->unsignedInteger('duration_days')->default(30);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'is_default'], 'idx_subscription_plans_active_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
