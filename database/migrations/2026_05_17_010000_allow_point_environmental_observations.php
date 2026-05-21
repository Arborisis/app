<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environmental_observations', function (Blueprint $table) {
            $table->dropForeign(['sound_id']);
            $table->foreignId('sound_id')->nullable()->change();
            $table->foreign('sound_id')->references('id')->on('sounds')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('environmental_observations', function (Blueprint $table) {
            $table->dropForeign(['sound_id']);
            $table->foreignId('sound_id')->nullable(false)->change();
            $table->foreign('sound_id')->references('id')->on('sounds')->cascadeOnDelete();
        });
    }
};
