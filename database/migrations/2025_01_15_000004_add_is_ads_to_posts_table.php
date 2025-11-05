<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_ads')->default(false)->after('is_public');
            $table->integer('views_count')->default(0)->after('shares_count');
            $table->integer('impressions_count')->default(0)->after('views_count');
            $table->integer('reach_count')->default(0)->after('impressions_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_ads', 'views_count', 'impressions_count', 'reach_count']);
        });
    }
};

