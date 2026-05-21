<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->string('microphone_position', 255)->nullable()->after('equipment');
        });
    }

    public function down(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->dropColumn('microphone_position');
        });
    }
};
