<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_metrics', function (Blueprint $table) {
            $table->id();
            $table->morphs('measurable');

            $table->string('metric_type', 50);
            $table->string('granularity', 20)->default('overall');
            $table->string('period_key', 20)->nullable();

            $table->decimal('value', 12, 6)->nullable();
            $table->jsonb('value_distribution')->nullable();
            $table->jsonb('parameters')->nullable();
            $table->jsonb('components')->nullable();

            $table->timestamp('computed_at');
            $table->unsignedInteger('sample_size')->default(0);
            $table->string('status', 20)->default('complete');
            $table->text('computation_notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['measurable_type', 'measurable_id', 'metric_type', 'granularity', 'period_key'],
                'scientific_metrics_unique'
            );
            $table->index(['metric_type', 'granularity']);
            $table->index('computed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_metrics');
    }
};
