<?php

use App\Enums\XenoCantoSubmissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xeno_canto_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default(XenoCantoSubmissionStatus::Prepared->value);
            $table->string('xeno_canto_id', 30)->nullable()->unique();
            $table->string('xeno_canto_url')->nullable();
            $table->jsonb('metadata_snapshot');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('sound_id');
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xeno_canto_submissions');
    }
};
