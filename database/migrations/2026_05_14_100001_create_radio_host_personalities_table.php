<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_host_personalities', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('display_name');
            $table->string('voice_provider', 30)->default('elevenlabs');
            $table->string('voice_id')->nullable();
            $table->jsonb('voice_settings')->nullable();
            $table->text('prose_brief')->nullable();
            $table->jsonb('forbidden_phrases')->nullable();
            $table->jsonb('preferred_lexicon')->nullable();
            $table->jsonb('dayparts')->nullable();
            $table->jsonb('show_types')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('priority')->default(10);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_host_personalities');
    }
};
