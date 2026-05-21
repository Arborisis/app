<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species_facts', function (Blueprint $table) {
            $table->id();
            $table->string('latin_name')->unique();
            $table->string('common_name_fr');
            $table->string('group', 40)->nullable()->index(); // oiseau, insecte, amphibien, mammifère, batracien, etc.
            $table->text('fact_fr')->nullable();
            $table->string('habitat')->nullable();
            $table->string('seasonality', 60)->nullable();
            $table->string('source', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('species_facts');
    }
};
