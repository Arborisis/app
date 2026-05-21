<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->string('environment', 30)->nullable();
            $table->string('equipment')->nullable();
            $table->string('license', 30)->default('all_rights_reserved');
            $table->string('visibility', 20)->default('public');
            $table->string('status', 20)->default('draft');
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('play_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('visibility');
            $table->index(['status', 'visibility']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sounds');
    }
};
