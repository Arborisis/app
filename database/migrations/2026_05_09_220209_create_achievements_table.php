<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->unsignedInteger('points')->default(0);
            $table->string('condition_type');
            $table->jsonb('condition_payload')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('condition_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
