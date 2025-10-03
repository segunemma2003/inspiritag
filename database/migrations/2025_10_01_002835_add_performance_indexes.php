<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['is_business', 'is_admin'], 'idx_users_business_admin');
            $table->index(['notifications_enabled', 'fcm_token'], 'idx_users_notifications');
            $table->index('last_seen', 'idx_users_last_seen');
        });

        // Posts table indexes
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_posts_user_created');
            $table->index(['category_id', 'is_public', 'created_at'], 'idx_posts_category_public_created');
            $table->index(['is_public', 'created_at'], 'idx_posts_public_created');
            $table->index(['likes_count', 'created_at'], 'idx_posts_likes_created');
            $table->index(['media_type', 'created_at'], 'idx_posts_media_created');
        });

        // Follows table indexes
        Schema::table('follows', function (Blueprint $table) {
            $table->index('follower_id', 'idx_follows_follower');
            $table->index('following_id', 'idx_follows_following');
            $table->index(['follower_id', 'created_at'], 'idx_follows_follower_created');
        });

        // Likes table indexes
        Schema::table('likes', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_likes_user_created');
            $table->index(['post_id', 'created_at'], 'idx_likes_post_created');
        });

        // Saves table indexes
        Schema::table('saves', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_saves_user_created');
            $table->index(['post_id', 'created_at'], 'idx_saves_post_created');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'created_at'], 'idx_notifications_user_read_created');
            $table->index(['user_id', 'type', 'created_at'], 'idx_notifications_user_type_created');
            $table->index(['from_user_id', 'created_at'], 'idx_notifications_from_user_created');
        });

        // Business accounts table indexes
        Schema::table('business_accounts', function (Blueprint $table) {
            $table->index(['is_verified', 'accepts_bookings'], 'idx_business_verified_bookings');
            $table->index(['business_type', 'is_verified'], 'idx_business_type_verified');
            $table->index(['rating', 'reviews_count'], 'idx_business_rating_reviews');
        });

        // Bookings table indexes
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['business_account_id', 'status', 'appointment_date'], 'idx_bookings_business_status_date');
            $table->index(['user_id', 'status', 'created_at'], 'idx_bookings_user_status_created');
        });

        // Tags table indexes
        Schema::table('tags', function (Blueprint $table) {
            $table->index('usage_count', 'idx_tags_usage');
            $table->index(['name', 'usage_count'], 'idx_tags_name_usage');
        });

        // Post tags table indexes
        Schema::table('post_tags', function (Blueprint $table) {
            $table->index(['tag_id', 'created_at'], 'idx_post_tags_tag_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('idx_post_tags_tag_created');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('idx_tags_usage');
            $table->dropIndex('idx_tags_name_usage');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_business_status_date');
            $table->dropIndex('idx_bookings_user_status_created');
        });

        Schema::table('business_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_business_verified_bookings');
            $table->dropIndex('idx_business_type_verified');
            $table->dropIndex('idx_business_rating_reviews');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read_created');
            $table->dropIndex('idx_notifications_user_type_created');
            $table->dropIndex('idx_notifications_from_user_created');
        });

        Schema::table('saves', function (Blueprint $table) {
            $table->dropIndex('idx_saves_user_created');
            $table->dropIndex('idx_saves_post_created');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex('idx_likes_user_created');
            $table->dropIndex('idx_likes_post_created');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('idx_follows_follower');
            $table->dropIndex('idx_follows_following');
            $table->dropIndex('idx_follows_follower_created');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_user_created');
            $table->dropIndex('idx_posts_category_public_created');
            $table->dropIndex('idx_posts_public_created');
            $table->dropIndex('idx_posts_likes_created');
            $table->dropIndex('idx_posts_media_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_business_admin');
            $table->dropIndex('idx_users_notifications');
            $table->dropIndex('idx_users_last_seen');
        });
    }
};
