<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_ads');
                $table->timestamp('featured_at')->nullable()->after('is_featured');
            }

            if (!Schema::hasColumn('posts', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('featured_at');
                $table->timestamp('flagged_at')->nullable()->after('is_flagged');
                $table->string('flagged_reason')->nullable()->after('flagged_at');
            }

            if (!Schema::hasColumn('posts', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('flagged_reason');
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
                $table->string('blocked_reason')->nullable()->after('blocked_at');
            }

            if (!Schema::hasColumn('posts', 'status')) {
                $table->string('status')->default('published')->after('blocked_reason');
            }

            $table->index(['is_featured', 'is_blocked', 'status'], 'idx_posts_admin_moderation');
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_admin');
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
                $table->string('blocked_reason')->nullable()->after('blocked_at');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('blocked_reason');
            }

            $table->index(['is_blocked', 'status'], 'idx_users_admin_status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('posts', 'blocked_reason')) {
                $table->dropColumn(['blocked_reason', 'blocked_at', 'is_blocked']);
            }
            if (Schema::hasColumn('posts', 'flagged_reason')) {
                $table->dropColumn(['flagged_reason', 'flagged_at', 'is_flagged']);
            }
            if (Schema::hasColumn('posts', 'featured_at')) {
                $table->dropColumn(['featured_at', 'is_featured']);
            }

            try {
                $table->dropIndex('idx_posts_admin_moderation');
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE posts DROP INDEX idx_posts_admin_moderation');
                } catch (\Throwable $ignored) {
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('users', 'blocked_reason')) {
                $table->dropColumn(['blocked_reason', 'blocked_at', 'is_blocked']);
            }

            try {
                $table->dropIndex('idx_users_admin_status');
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE users DROP INDEX idx_users_admin_status');
                } catch (\Throwable $ignored) {
                }
            }
        });
    }
};
