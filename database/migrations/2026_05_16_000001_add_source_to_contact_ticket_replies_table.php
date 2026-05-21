<?php

declare(strict_types=1);

use App\Enums\ContactTicketReplySource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_ticket_replies', function (Blueprint $table): void {
            $table->string('source')
                ->default(ContactTicketReplySource::Team->value)
                ->after('user_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_ticket_replies', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
