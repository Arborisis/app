<?php

namespace App\Providers;

use App\Events\DiscordNotification;
use App\Events\Gamification\ArborisisPointApproved;
use App\Events\Gamification\ArborisisPointSubmitted;
use App\Events\Gamification\ArborisisPointVisited;
use App\Events\Gamification\CommentPosted;
use App\Events\Gamification\PointReported;
use App\Events\Gamification\ProfileUpdated;
use App\Events\Gamification\SoundLiked;
use App\Events\Gamification\SoundListened;
use App\Events\Gamification\UserLoggedIn;
use App\Events\AudioAnalysisCompleted;
use App\Events\SoundAnalyzed;
use App\Events\SoundPublished;
use App\Jobs\OpenSearch\IndexSoundInOpenSearch;
use App\Jobs\OpenSearch\IndexListeningPointInOpenSearch;
use App\Jobs\Scientific\ComputeScientificMetricsJob;
use App\Listeners\AwardMedals;
use App\Listeners\AwardMedalsOnPointApproved;
use App\Listeners\AwardMedalsOnSoundListened;
use App\Listeners\AwardXp;
use App\Listeners\CheckAchievements;
use App\Listeners\CheckAchievementsOnPointApproved;
use App\Listeners\CheckAchievementsOnPointSubmitted;
use App\Listeners\CheckAchievementsOnSoundListened;
use App\Listeners\CheckAchievementsOnSoundPublished;
use App\Listeners\CheckAchievementsOnProfileUpdated;
use App\Listeners\CheckAchievementsOnUserLoggedIn;
use App\Listeners\DispatchDiscordNotification;
use App\Listeners\NotifyDiscordOnRegistration;
use App\Listeners\SendNewSoundPushNotification;
use App\Listeners\UpdateQuestProgress;
use App\Listeners\UpdateQuestProgressOnCommentPosted;
use App\Listeners\UpdateQuestProgressOnPointApproved;
use App\Listeners\UpdateQuestProgressOnPointReported;
use App\Listeners\UpdateQuestProgressOnPointSubmitted;
use App\Listeners\UpdateQuestProgressOnSoundLiked;
use App\Listeners\UpdateQuestProgressOnSoundListened;
use App\Listeners\UpdateQuestProgressOnSoundPublished;
use App\Listeners\UpdateQuestProgressOnUserLoggedIn;
use Illuminate\Auth\Events\Registered;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use App\Models\ArborisisPoint;
use App\Models\ContactTicket;
use App\Models\ListeningPoint;
use App\Models\SoundFile;
use App\Models\User;
use App\Observers\ListeningPointObserver;
use App\Observers\SoundFileObserver;
use App\Observers\UserObserver;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\HelpdeskTicket;
use App\Policies\ArborisisPointPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\ChatRoomPolicy;
use App\Policies\ContactTicketPolicy;
use App\Policies\HelpdeskTicketPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\Storage\SignedUrlService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SignedUrlService::class, function () {
            return new SignedUrlService(
                signingKey: (string) config('services.r2.signing_key', ''),
                customDomain: rtrim((string) config('filesystems.disks.r2.url', ''), '/'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        User::observe(UserObserver::class);
        SoundFile::observe(SoundFileObserver::class);
        ListeningPoint::observe(ListeningPointObserver::class);

        Gate::policy(ArborisisPoint::class, ArborisisPointPolicy::class);
        Gate::policy(ContactTicket::class, ContactTicketPolicy::class);
        Gate::policy(ChatRoom::class, ChatRoomPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);
        Gate::policy(HelpdeskTicket::class, HelpdeskTicketPolicy::class);

        Event::listen(SoundPublished::class, SendNewSoundPushNotification::class);
        Event::listen(DiscordNotification::class, DispatchDiscordNotification::class);
        Event::listen(Registered::class, \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class);
        Event::listen(Registered::class, NotifyDiscordOnRegistration::class);
        Event::listen(SocialiteWasCalled::class, DiscordExtendSocialite::class);

        // Gamification listeners — Visits
        Event::listen(ArborisisPointVisited::class, UpdateQuestProgress::class);
        Event::listen(ArborisisPointVisited::class, CheckAchievements::class);
        Event::listen(ArborisisPointVisited::class, AwardXp::class);
        Event::listen(ArborisisPointVisited::class, AwardMedals::class);

        // Scientific / OpenSearch listeners — Sound published
        Event::listen(SoundPublished::class, function (SoundPublished $event) {
            IndexSoundInOpenSearch::dispatch($event->sound->id)->onQueue('search');
        });

        Event::listen(SoundAnalyzed::class, function (SoundAnalyzed $event) {
            IndexSoundInOpenSearch::dispatch($event->sound->id)->onQueue('search');
            ComputeScientificMetricsJob::dispatch($event->sound->id)->onQueue('metrics');

            if ($event->sound->listening_point_id) {
                IndexListeningPointInOpenSearch::dispatch($event->sound->listening_point_id)->onQueue('search');
            }
        });

        Event::listen(AudioAnalysisCompleted::class, function (AudioAnalysisCompleted $event) {
            IndexSoundInOpenSearch::dispatch($event->sound->id)->onQueue('search');
            ComputeScientificMetricsJob::dispatch($event->sound->id)->onQueue('metrics');

            if ($event->sound->listening_point_id) {
                IndexListeningPointInOpenSearch::dispatch($event->sound->listening_point_id)->onQueue('search');
            }
        });

        // Gamification listeners — Sound published
        Event::listen(SoundPublished::class, UpdateQuestProgressOnSoundPublished::class);
        Event::listen(SoundPublished::class, CheckAchievementsOnSoundPublished::class);

        // Gamification listeners — Point submitted / approved
        Event::listen(ArborisisPointSubmitted::class, UpdateQuestProgressOnPointSubmitted::class);
        Event::listen(ArborisisPointSubmitted::class, CheckAchievementsOnPointSubmitted::class);
        Event::listen(ArborisisPointApproved::class, UpdateQuestProgressOnPointApproved::class);
        Event::listen(ArborisisPointApproved::class, CheckAchievementsOnPointApproved::class);
        Event::listen(ArborisisPointApproved::class, AwardMedalsOnPointApproved::class);

        // Gamification listeners — Sound listened
        Event::listen(SoundListened::class, UpdateQuestProgressOnSoundListened::class);
        Event::listen(SoundListened::class, CheckAchievementsOnSoundListened::class);
        Event::listen(SoundListened::class, AwardMedalsOnSoundListened::class);

        // Gamification listeners — Social interactions
        Event::listen(SoundLiked::class, UpdateQuestProgressOnSoundLiked::class);
        Event::listen(CommentPosted::class, UpdateQuestProgressOnCommentPosted::class);
        Event::listen(PointReported::class, UpdateQuestProgressOnPointReported::class);

        // Gamification listeners — Login / streak
        Event::listen(UserLoggedIn::class, UpdateQuestProgressOnUserLoggedIn::class);
        Event::listen(UserLoggedIn::class, CheckAchievementsOnUserLoggedIn::class);

        // Gamification listeners — Profile
        Event::listen(ProfileUpdated::class, CheckAchievementsOnProfileUpdated::class);

        RateLimiter::for('discord', function () {
            return Limit::perMinute(60);
        });

        RateLimiter::for('search', function ($request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('scientific-api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('listening-points', function ($request) {
            return Limit::perMinute(45)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai-agent-chat', function ($request) {
            $user = $request->user();
            $identity = $user ? "user:{$user->id}" : "ip:{$request->ip()}";
            $minuteLimit = (int) config(
                $user ? 'services.arborisis_agent.rate_limit_per_minute' : 'services.arborisis_agent.guest_rate_limit_per_minute',
                $user ? 6 : 3,
            );
            $dailyQuota = (int) config(
                $user ? 'services.arborisis_agent.daily_quota' : 'services.arborisis_agent.guest_daily_quota',
                $user ? 60 : 15,
            );

            return [
                Limit::perMinute($minuteLimit)
                    ->by("ai-agent-chat:minute:{$identity}")
                    ->response(fn ($request, array $headers) => response()->json([
                        'message' => 'Sylve reçoit trop de demandes à la suite. Réessaie dans quelques instants.',
                        'code' => 'sylve_rate_limited',
                    ], 429, $headers)),
                Limit::perDay($dailyQuota)
                    ->by("ai-agent-chat:day:{$identity}")
                    ->response(fn ($request, array $headers) => response()->json([
                        'message' => 'Le quota quotidien de Sylve est atteint pour ce compte. Réessaie demain.',
                        'code' => 'sylve_daily_quota_exceeded',
                    ], 429, $headers)),
            ];
        });

        RateLimiter::for('agent-action', function ($request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('helpdesk', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
