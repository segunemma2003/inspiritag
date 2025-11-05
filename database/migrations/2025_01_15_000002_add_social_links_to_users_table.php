<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('website')->nullable()->after('profession');
            $table->string('booking_link')->nullable()->after('website');
            $table->string('whatsapp_link')->nullable()->after('booking_link');
            $table->string('linkedin_link')->nullable()->after('whatsapp_link');
            $table->string('instagram_link')->nullable()->after('linkedin_link');
            $table->string('tiktok_link')->nullable()->after('instagram_link');
            $table->string('snapchat_link')->nullable()->after('tiktok_link');
            $table->string('facebook_link')->nullable()->after('snapchat_link');
            $table->string('twitter_link')->nullable()->after('facebook_link');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'website',
                'booking_link',
                'whatsapp_link',
                'linkedin_link',
                'instagram_link',
                'tiktok_link',
                'snapchat_link',
                'facebook_link',
                'twitter_link',
            ]);
        });
    }
};

