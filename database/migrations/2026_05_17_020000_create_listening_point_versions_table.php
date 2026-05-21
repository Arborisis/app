<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_point_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listening_point_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('version_number');
            $table->string('version_hash', 64)->unique();
            $table->string('parent_version_hash', 64)->nullable()->index();
            $table->string('event', 40);
            $table->string('source', 40)->default('system');
            $table->string('summary')->nullable();

            $table->jsonb('public_payload');
            $table->jsonb('diff')->nullable();
            $table->timestampTz('captured_at');
            $table->timestamps();

            $table->unique(['listening_point_id', 'version_number']);
            $table->index(['listening_point_id', 'captured_at']);
            $table->index(['listening_point_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_point_versions');
    }
};
