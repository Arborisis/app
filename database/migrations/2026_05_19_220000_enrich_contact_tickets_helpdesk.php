<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_tickets', function (Blueprint $table) {
            $table->string('priority')->default('medium')->after('status');
            $table->string('category')->default('general')->after('priority');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            $table->timestamp('resolved_at')->nullable()->after('replied_at');
            $table->text('internal_notes')->nullable()->after('resolved_at');

            $table->index('priority');
            $table->index('category');
            $table->index('assigned_to');
        });

        Schema::table('contact_ticket_replies', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('contact_tickets', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropIndex(['category']);
            $table->dropIndex(['assigned_to']);
            $table->dropColumn(['priority', 'category', 'assigned_to', 'resolved_at', 'internal_notes']);
        });

        Schema::table('contact_ticket_replies', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
