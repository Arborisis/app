<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_channels', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->string('mount_path', 120)->default('/arborisis.mp3');
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->jsonb('vibe')->nullable();
            $table->jsonb('playlist_source')->nullable();
            $table->string('production_preset', 40)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('radio_channels')->insert([
            'slug' => 'main',
            'name' => 'Arborisis Radio',
            'mount_path' => '/arborisis.mp3',
            'color' => '#3c8c5c',
            'description' => 'Le flux principal : field recordings et sons de nature en continu.',
            'vibe' => json_encode([
                'tone' => 'naturaliste, poétique, calme',
                'audience' => 'auditeurs de field recording, amoureux de la nature',
                'keywords' => ['forêt', 'oiseaux', 'paysage sonore', 'immersion'],
            ]),
            'playlist_source' => null,
            'production_preset' => 'emission_cinematic',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_channels');
    }
};
