<?php

use App\Enums\AnalysisStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')
                ->unique()
                ->constrained('sounds')
                ->cascadeOnDelete();
            $table->string('status', 20)->default(AnalysisStatus::PENDING->value);
            $table->jsonb('features_json')->nullable();
            $table->jsonb('features_detailed_json')->nullable();
            $table->jsonb('parameters_json')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_analyses');
    }
};
