<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('birdnet_detections', function (Blueprint $table) {
            $table->boolean('is_validated')->default(false)->after('source');
            $table->foreignId('validated_by')->nullable()->after('is_validated')->constrained('users')->nullOnDelete();

            $table->index('is_validated');
            $table->index('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('birdnet_detections', function (Blueprint $table) {
            $table->dropIndex(['validated_by']);
            $table->dropIndex(['is_validated']);
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn('is_validated');
        });
    }
};
