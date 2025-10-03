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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->text('caption')->nullable();
            $table->string('media_url');
            $table->string('media_type'); // 'image' or 'video'
            $table->string('thumbnail_url')->nullable(); // For videos
            $table->json('media_metadata')->nullable(); // Store dimensions, duration, etc.
            $table->string('location')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('likes_count')->default(0);
            $table->integer('saves_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
