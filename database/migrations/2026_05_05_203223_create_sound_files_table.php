<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')->unique()->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size_bytes');
            $table->string('disk')->default('s3');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_files');
    }
};
