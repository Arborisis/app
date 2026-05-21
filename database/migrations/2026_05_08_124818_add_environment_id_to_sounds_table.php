<?php

declare(strict_types=1);

use App\Enums\EnvironmentType;
use App\Models\Environment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create environments from existing enum values
        $environments = [];
        foreach (EnvironmentType::cases() as $env) {
            $environments[$env->value] = Environment::create([
                'name' => $env->label(),
                'slug' => $env->value,
                'order' => 0,
            ]);
        }

        Schema::table('sounds', function (Blueprint $table) {
            $table->foreignId('environment_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        // Migrate existing data
        foreach ($environments as $value => $environment) {
            DB::table('sounds')
                ->where('environment', $value)
                ->update(['environment_id' => $environment->id]);
        }

        Schema::table('sounds', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }

    public function down(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->string('environment', 30)->nullable()->after('category_id');
        });

        // Restore data from environments table
        $environments = Environment::all()->keyBy('id');
        foreach ($environments as $id => $environment) {
            DB::table('sounds')
                ->where('environment_id', $id)
                ->update(['environment' => $environment->slug]);
        }

        Schema::table('sounds', function (Blueprint $table) {
            $table->dropForeign(['environment_id']);
            $table->dropColumn('environment_id');
        });

        Environment::truncate();
    }
};
