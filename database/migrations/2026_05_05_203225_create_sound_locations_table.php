<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('exact_latitude', 10, 8)->nullable();
            $table->decimal('exact_longitude', 11, 8)->nullable();
            $table->decimal('public_latitude', 10, 8)->nullable();
            $table->decimal('public_longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->index(['public_latitude', 'public_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_locations');
    }
};
