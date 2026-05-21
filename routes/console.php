<?php

use App\Jobs\CleanExpiredPresence;
use App\Jobs\GenerateDailyQuests;
use App\Jobs\GenerateWeeklyQuests;
use App\Jobs\Stats\RefreshStatsCacheJob;
use App\Jobs\ValidateSuspiciousVisits;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gamification scheduled tasks
Schedule::job(new CleanExpiredPresence)->everyFiveMinutes();
Schedule::job(new GenerateDailyQuests)->dailyAt('00:01');
Schedule::job(new GenerateWeeklyQuests)->weeklyOn(1, '00:05');
Schedule::job(new ValidateSuspiciousVisits)->everyThirtyMinutes();
Schedule::job(new \App\Jobs\GenerateDailySoundIdeas)->dailyAt('00:03');
Schedule::job(new \App\Jobs\GenerateDailyBlogPost)->dailyAt(config('blog.daily_at', '07:00'))
    ->when(fn () => config('blog.ai_enabled', true));

// Radio generated formats: one generation per day for each format.
Schedule::command('radio:generate-podcast')->dailyAt('06:00')->when(
    fn () => config('radio.podcast.enabled', false)
);

Schedule::command('radio:generate-flash')->dailyAt('06:10')->when(
    fn () => config('radio.host.flash_enabled', false)
);

Schedule::command('radio:generate-emission')->dailyAt('06:20')->when(
    fn () => config('radio.host.emission_enabled', false)
);

// Radio playlist regeneration (every 5 minutes)
Schedule::command('radio:rebuild-cache')->everyFiveMinutes();
Schedule::command('radio:listeners:purge')->everyTwoMinutes();

// Radio playlist .liq file regeneration (every 5 minutes)
Schedule::call(function () {
    $export = app(\App\Services\Radio\RadioPlaylistExportService::class);
    $liqPath = storage_path('app/radio-cache/playlist.liq');
    file_put_contents($liqPath, $export->liq(), LOCK_EX);
})->everyFiveMinutes();

// Scientific stats cache refresh (every 15 minutes)
Schedule::job(new RefreshStatsCacheJob)->everyFifteenMinutes();
