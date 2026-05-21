<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->foreignId('listening_point_id')
                ->nullable()
                ->after('environment_id')
                ->constrained('listening_points')
                ->nullOnDelete();

            $table->text('weather_notes')->nullable()->after('equipment');
            $table->unsignedTinyInteger('perceived_activity_level')->nullable()->after('weather_notes');

            $table->index('listening_point_id');
        });
    }

    public function down(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->dropIndex(['listening_point_id']);
            $table->dropConstrainedForeignId('listening_point_id');
            $table->dropColumn(['weather_notes', 'perceived_activity_level']);
        });
    }
};
