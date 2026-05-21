<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sound_locations', function (Blueprint $table) {
            $table->foreignId('listening_point_id')
                ->nullable()
                ->after('sound_id')
                ->constrained('listening_points')
                ->nullOnDelete();

            $table->index('listening_point_id');
            $table->index(['listening_point_id', 'is_sensitive']);
        });
    }

    public function down(): void
    {
        Schema::table('sound_locations', function (Blueprint $table) {
            $table->dropIndex(['listening_point_id', 'is_sensitive']);
            $table->dropIndex(['listening_point_id']);
            $table->dropConstrainedForeignId('listening_point_id');
        });
    }
};
