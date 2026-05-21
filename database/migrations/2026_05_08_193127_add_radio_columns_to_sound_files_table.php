<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sound_files', function (Blueprint $table) {
            $table->string('radio_path')->nullable()->after('path');
            $table->string('radio_mime_type')->nullable()->after('radio_path');
            $table->unsignedBigInteger('radio_size_bytes')->nullable()->after('radio_mime_type');
            $table->timestamp('radio_converted_at')->nullable()->after('radio_size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('sound_files', function (Blueprint $table) {
            $table->dropColumn(['radio_path', 'radio_mime_type', 'radio_size_bytes', 'radio_converted_at']);
        });
    }
};
