<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('arborisis_points')
            ->where('nature_sensitivity_level', 'normal')
            ->update([
                'approximate_latitude' => DB::raw('latitude'),
                'approximate_longitude' => DB::raw('longitude'),
            ]);
    }

    public function down(): void
    {
        $latitudeExpression = DB::connection()->getDriverName() === 'pgsql'
            ? 'ROUND(latitude::numeric, 2)'
            : 'ROUND(latitude, 2)';
        $longitudeExpression = DB::connection()->getDriverName() === 'pgsql'
            ? 'ROUND(longitude::numeric, 2)'
            : 'ROUND(longitude, 2)';

        DB::table('arborisis_points')
            ->where('nature_sensitivity_level', 'normal')
            ->update([
                'approximate_latitude' => DB::raw($latitudeExpression),
                'approximate_longitude' => DB::raw($longitudeExpression),
            ]);
    }
};
