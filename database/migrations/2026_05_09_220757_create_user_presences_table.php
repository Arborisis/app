<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('approximate_latitude', 10, 8);
            $table->decimal('approximate_longitude', 11, 8);
            $table->timestamp('last_seen_at');
            $table->string('visibility_mode')->default('invisible');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['approximate_latitude', 'approximate_longitude']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_presences');
    }
};
