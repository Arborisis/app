<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radio_dj_announcements', function (Blueprint $table) {
            $table->string('personality_slug', 60)->nullable()->after('voice_id');
            $table->foreignId('previous_sound_id')
                ->nullable()
                ->after('sound_id')
                ->constrained('sounds', 'id', 'fk_radio_dj_announcements_previous_sound')
                ->nullOnDelete();
            $table->foreignId('next_sound_id')
                ->nullable()
                ->after('previous_sound_id')
                ->constrained('sounds', 'id', 'fk_radio_dj_announcements_next_sound')
                ->nullOnDelete();
            $table->string('prompt_hash', 64)->nullable()->after('text_hash');
            $table->string('phrase_fingerprint', 64)->nullable()->after('prompt_hash');
            $table->string('daypart', 20)->nullable()->after('phrase_fingerprint');

            $table->index('personality_slug');
            $table->index('phrase_fingerprint');
            $table->index('daypart');
        });
    }

    public function down(): void
    {
        Schema::table('radio_dj_announcements', function (Blueprint $table) {
            $table->dropForeign('fk_radio_dj_announcements_previous_sound');
            $table->dropForeign('fk_radio_dj_announcements_next_sound');
            $table->dropIndex(['personality_slug']);
            $table->dropIndex(['phrase_fingerprint']);
            $table->dropIndex(['daypart']);
            $table->dropColumn([
                'personality_slug',
                'previous_sound_id',
                'next_sound_id',
                'prompt_hash',
                'phrase_fingerprint',
                'daypart',
            ]);
        });
    }
};
