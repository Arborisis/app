<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('rarity')->default('bronze'); // bronze | silver | gold | platinum | legendary | seasonal | community | ecological
            $table->string('category')->default('general');
            $table->string('unlock_condition_type');
            $table->jsonb('unlock_condition_value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('rarity');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medals');
    }
};
