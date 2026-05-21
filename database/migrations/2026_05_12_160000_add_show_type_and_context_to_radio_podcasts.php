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
            $table->string('show_type', 30)->default('podcast')->after('status')->index();
            $table->jsonb('context_json')->nullable()->after('research_json');
        });
    }

    public function down(): void
    {
        Schema::table('radio_podcasts', function (Blueprint $table) {
            $table->dropIndex(['show_type']);
            $table->dropColumn(['show_type', 'context_json']);
        });
    }
};
