<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_total')->default(0)->after('role');
            $table->unsignedInteger('level')->default(1)->after('xp_total');
            $table->unsignedInteger('current_streak')->default(0)->after('level');
            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');
            $table->timestamp('last_activity_at')->nullable()->after('longest_streak');
            $table->timestamp('geo_consent_given_at')->nullable()->after('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'xp_total',
                'level',
                'current_streak',
                'longest_streak',
                'last_activity_at',
                'geo_consent_given_at',
            ]);
        });
    }
};
