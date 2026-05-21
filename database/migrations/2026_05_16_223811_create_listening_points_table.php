<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Coordonnées exactes (admin uniquement)
            $table->decimal('exact_latitude', 10, 8)->nullable();
            $table->decimal('exact_longitude', 11, 8)->nullable();

            // Coordonnées publiques approximatives
            $table->decimal('public_latitude', 10, 8);
            $table->decimal('public_longitude', 11, 8);
            $table->unsignedSmallInteger('public_accuracy_meters')->default(1000);

            // Métadonnées
            $table->foreignId('environment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('habitat_type', 50)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('admin_level_1')->nullable();
            $table->string('elevation_meters')->nullable();

            // Modération & confidentialité
            $table->string('moderation_status', 20)->default('pending');
            $table->string('nature_sensitivity_level', 20)->default('normal');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Statistiques dénormalisées (mises à jour async)
            $table->unsignedInteger('recordings_count')->default(0);
            $table->unsignedInteger('species_detected_count')->default(0);
            $table->timestamp('first_recorded_at')->nullable();
            $table->timestamp('last_recorded_at')->nullable();
            $table->jsonb('dominant_tags')->nullable();
            $table->jsonb('stats_cache')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['public_latitude', 'public_longitude']);
            $table->index('moderation_status');
            $table->index('environment_id');
            $table->index('habitat_type');
            $table->index(['moderation_status', 'public_latitude', 'public_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_points');
    }
};
