<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('follows', 'deleted_at')) {
            Schema::table('follows', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        if (!Schema::hasColumn('likes', 'deleted_at')) {
            Schema::table('likes', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('follows', 'deleted_at')) {
            Schema::table('follows', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('likes', 'deleted_at')) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
