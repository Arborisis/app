<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radio_podcasts', function (Blueprint $table) {
            $table->string('theme')->nullable()->after('description');
            $table->jsonb('research_json')->nullable()->after('script_json');
        });
    }

    public function down(): void
    {
        Schema::table('radio_podcasts', function (Blueprint $table) {
            $table->dropColumn(['theme', 'research_json']);
        });
    }
};
