<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AgentDiscoveryController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\CreatorController;
use App\Http\Controllers\Web\CreatorProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EchoDonationController;
use App\Http\Controllers\Web\FollowController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LikeController;
use App\Http\Controllers\Web\MapController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\RadioManagerController;
use Inertia\Inertia;
use App\Http\Controllers\Web\RadioController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\AudioAnalysisController;
use App\Http\Controllers\Web\ChatConversationController;
use App\Http\Controllers\Web\ChatMessageController;
use App\Http\Controllers\Web\ChatModerationController;
use App\Http\Controllers\Web\ChatPrivateMessageController;
use App\Http\Controllers\Web\ChatRoomController;
use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\SoundController;
use App\Http\Controllers\Web\WalletController;
use App\Http\Controllers\Web\AdminHelpdeskController;
use App\Http\Controllers\Web\HelpdeskController;
use App\Http\Controllers\Web\XenoCantoSubmissionController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/.well-known/api-catalog', [AgentDiscoveryController::class, 'apiCatalog'])
    ->name('agent-discovery.api-catalog');
Route::get('/.well-known/oauth-authorization-server', [AgentDiscoveryController::class, 'oauthAuthorizationServer'])
    ->name('agent-discovery.oauth-authorization-server');
Route::get('/.well-known/openid-configuration', [AgentDiscoveryController::class, 'openIdConfiguration'])
    ->name('agent-discovery.openid-configuration');
Route::get('/.well-known/oauth-protected-resource', [AgentDiscoveryController::class, 'protectedResource'])
    ->name('agent-discovery.oauth-protected-resource');
Route::get('/.well-known/jwks.json', [AgentDiscoveryController::class, 'jwks'])
    ->name('agent-discovery.jwks');
Route::get('/.well-known/mcp/server-card.json', [AgentDiscoveryController::class, 'mcpServerCard'])
    ->name('agent-discovery.mcp-server-card');
Route::get('/.well-known/agent-skills/index.json', [AgentDiscoveryController::class, 'agentSkillsIndex'])
    ->name('agent-discovery.agent-skills.index');
Route::get('/.well-known/agent-skills/{skill}.md', [AgentDiscoveryController::class, 'agentSkill'])
    ->where('skill', '[a-z0-9-]+')
    ->name('agent-discovery.agent-skills.show');
Route::get('/openapi.json', [AgentDiscoveryController::class, 'openApi'])
    ->name('agent-discovery.openapi');
Route::get('/docs/api', [AgentDiscoveryController::class, 'apiDocs'])
    ->name('agent-discovery.api-docs');
Route::match(['get', 'post'], '/oauth/token', [AgentDiscoveryController::class, 'unsupportedOAuthToken'])
    ->name('agent-discovery.oauth-token');

Route::get('/sounds', [SoundController::class, 'index'])->name('sounds.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sounds/create', [SoundController::class, 'create'])->name('sounds.create');
    Route::get('/record', [SoundController::class, 'record'])->name('sounds.record');
    Route::post('/sounds', [SoundController::class, 'store'])->name('sounds.store');
});

Route::get('/sounds/{slug}', [SoundController::class, 'show'])->name('sounds.show');

Route::get('/sounds/{sound}/analysis', [AudioAnalysisController::class, 'show'])->name('sounds.analysis.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/sounds/{sound}/analysis', [AudioAnalysisController::class, 'analyze'])->name('sounds.analysis.store');
    Route::get('/sounds/{sound}/analysis/export/{format}', [AudioAnalysisController::class, 'export'])->name('sounds.analysis.export');
    Route::post('/sounds/{sound}/xeno-canto', [XenoCantoSubmissionController::class, 'prepare'])->name('sounds.xeno-canto.prepare');
    Route::get('/sounds/{sound}/xeno-canto', [XenoCantoSubmissionController::class, 'show'])->name('sounds.xeno-canto.show');
    Route::post('/sounds/{sound}/xeno-canto/submitted', [XenoCantoSubmissionController::class, 'markSubmitted'])->name('sounds.xeno-canto.submitted');
});

Route::get('/api/sounds/{sound}/analysis', [AudioAnalysisController::class, 'show'])->name('api.sounds.analysis.show');
Route::get('/api/sounds/{sound}/analysis/realtime', [AudioAnalysisController::class, 'realtimeData'])->name('api.sounds.analysis.realtime');

Route::get('/map', [MapController::class, 'index'])->name('map.index');

Route::middleware(['auth', 'verified'])->get('/arborisis-map', function () {
    return Inertia::render('ArborisisMap/Index');
})->name('arborisis-map.index');

Route::get('/sound-walks', [\App\Http\Controllers\Web\SoundWalkController::class, 'index'])->name('sound-walks.index');
Route::get('/sound-walks/{slug}', [\App\Http\Controllers\Web\SoundWalkController::class, 'show'])->name('sound-walks.show');

Route::get('/creators', [CreatorController::class, 'index'])->name('creators.index');
Route::get('/creators/{slug}', [CreatorProfileController::class, 'show'])->name('creators.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/users/{user:slug}/followers', [\App\Http\Controllers\Web\SocialListController::class, 'followers'])->name('users.followers');
Route::get('/users/{user:slug}/following', [\App\Http\Controllers\Web\SocialListController::class, 'following'])->name('users.following');
Route::get('/users/{user:slug}/friends', [\App\Http\Controllers\Web\SocialListController::class, 'friends'])->name('users.friends');

Route::middleware(['throttle:scientific-api'])->group(function () {
    Route::get('/scientific-stats', [\App\Http\Controllers\Web\ScientificStatsController::class, 'index'])->name('scientific-stats.index');
});

Route::middleware(['throttle:listening-points'])->group(function () {
    Route::get('/listening-points', [\App\Http\Controllers\Web\ListeningPointController::class, 'index'])->name('listening-points.index');
    Route::get('/listening-points/{slug}', [\App\Http\Controllers\Web\ListeningPointController::class, 'show'])->name('listening-points.show');
    Route::get('/api/listening-points/heatmap', [\App\Http\Controllers\Web\ListeningPointController::class, 'heatmap'])->name('listening-points.heatmap');
    Route::get('/api/listening-points/timeline', [\App\Http\Controllers\Web\ListeningPointController::class, 'timeline'])->name('listening-points.timeline');
});

Route::get('/transparency', [PageController::class, 'transparency'])->name('transparency');
Route::get('/echo', [PageController::class, 'echoInfo'])->name('echo.info');
Route::get('/mission', [PageController::class, 'mission'])->name('mission');
Route::get('/charte', [PageController::class, 'charte'])->name('charte');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/offline', [PageController::class, 'offline'])->name('offline');

Route::get('/contact', [\App\Http\Controllers\Web\ContactController::class, 'index'])->name('contact');
Route::post('/contact', [\App\Http\Controllers\Web\ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');

Route::middleware(['auth', 'verified'])->prefix('helpdesk')->name('helpdesk.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\HelpdeskController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\HelpdeskController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\HelpdeskController::class, 'store'])->name('store');
    Route::get('/{ticketNumber}', [\App\Http\Controllers\Web\HelpdeskController::class, 'show'])->name('show');
    Route::post('/{ticketNumber}/reply', [\App\Http\Controllers\Web\HelpdeskController::class, 'reply'])->name('reply');
});

Route::post('/newsletter/subscribe', [\App\Http\Controllers\Web\NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe')
    ->middleware('throttle:3,1');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\Web\NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

Route::post('/api/push-subscriptions', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'store'])->middleware('throttle:10,1');
Route::delete('/api/push-subscriptions', [\App\Http\Controllers\Api\PushSubscriptionController::class, 'destroy'])->middleware('throttle:10,1');
Route::get('/api/vapid-public-key', fn () => ['key' => config('services.vapid.public_key')]);

Route::get('/radio', [RadioController::class, 'index'])->name('radio.index');
Route::get('/radio/c/{channel:slug}', [RadioController::class, 'index'])->name('radio.channels.show');
Route::get('/radio/programmes', [RadioController::class, 'shows'])->name('radio.shows.index');
Route::get('/radio/stream', [RadioController::class, 'stream'])
    ->name('radio.stream')
    ->withoutMiddleware([
        \App\Http\Middleware\HandleInertiaRequests::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);
Route::get('/radio/stream.m3u', [RadioController::class, 'playlist'])->name('radio.playlist');
Route::get('/radio/cache/{type}/{id}', [RadioController::class, 'serveCachedAudio'])
    ->name('radio.cache.serve')
    ->whereIn('type', ['sounds', 'jingles', 'podcasts', 'dj']);

Route::middleware(['auth', 'verified'])
    ->prefix('admin/radio-manager')
    ->name('admin.radio-manager.')
    ->group(function () {
        Route::get('/', [RadioManagerController::class, 'index'])
            ->name('index')
            ->middleware('canAccessRadioManager');
        Route::get('/status', [RadioManagerController::class, 'status'])
            ->name('status')
            ->middleware('canAccessRadioManager');
        Route::put('/settings', [RadioManagerController::class, 'updateSettings'])
            ->name('settings.update')
            ->middleware('canAccessRadioManager');
        Route::post('/reload', [RadioManagerController::class, 'reload'])
            ->name('reload')
            ->middleware('canAccessRadioManager');
        Route::post('/generate', [RadioManagerController::class, 'generateContent'])
            ->name('generate')
            ->middleware('canAccessRadioManager');
        Route::post('/podcasts/{podcast}/publish', [RadioManagerController::class, 'publishPodcast'])
            ->name('podcasts.publish')
            ->middleware('canAccessRadioManager');
        Route::post('/podcasts/{podcast}/reject', [RadioManagerController::class, 'rejectPodcast'])
            ->name('podcasts.reject')
            ->middleware('canAccessRadioManager');
        Route::delete('/podcasts/{podcast}', [RadioManagerController::class, 'deletePodcast'])
            ->name('podcasts.destroy')
            ->middleware('canAccessRadioManager');
    });

Route::get('/auth/discord/callback', [\App\Http\Controllers\Auth\DiscordController::class, 'callback'])->name('discord.callback');

Route::middleware('auth')->group(function () {
    Route::get('/auth/discord/redirect', [\App\Http\Controllers\Auth\DiscordController::class, 'redirect'])->name('discord.redirect');
    Route::post('/auth/discord/unlink', [\App\Http\Controllers\Auth\DiscordController::class, 'unlink'])->name('discord.unlink');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Social
    Route::post('/sounds/{sound}/likes', [LikeController::class, 'store'])->name('likes.store');
    Route::delete('/sounds/{sound}/likes', [LikeController::class, 'destroy'])->name('likes.destroy');

    Route::post('/sounds/{sound}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('/users/{user:id}/follows', [FollowController::class, 'store'])->name('follows.store');
    Route::delete('/users/{user:id}/follows', [FollowController::class, 'destroy'])->name('follows.destroy');

    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

    // Chat
    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatRoomController::class, 'index'])->name('chat.index');
        Route::post('/rooms', [ChatRoomController::class, 'store'])->name('chat.rooms.store')->middleware('can:create,App\Models\ChatRoom');
        Route::get('/rooms/{room:slug}', [ChatRoomController::class, 'show'])->name('chat.rooms.show');
        Route::post('/rooms/{room:slug}/join', [ChatRoomController::class, 'join'])->name('chat.rooms.join');
        Route::post('/rooms/{room:slug}/leave', [ChatRoomController::class, 'leave'])->name('chat.rooms.leave');
        Route::post('/rooms/{room:slug}/messages', [ChatMessageController::class, 'store'])->name('chat.messages.store');

        Route::get('/conversations', [ChatConversationController::class, 'index'])->name('chat.conversations.index');
        Route::post('/conversations', [ChatConversationController::class, 'store'])->name('chat.conversations.store');
        Route::get('/conversations/{conversation}', [ChatConversationController::class, 'show'])->name('chat.conversations.show');
        Route::post('/conversations/{conversation}/messages', [ChatPrivateMessageController::class, 'store'])->name('chat.private_messages.store');

        Route::post('/rooms/{room:slug}/ban', [ChatModerationController::class, 'ban'])->name('chat.moderation.ban')->middleware('can:moderate,room');
        Route::post('/rooms/{room:slug}/unban', [ChatModerationController::class, 'unban'])->name('chat.moderation.unban')->middleware('can:moderate,room');
    });

    Route::delete('/messages/{message}', [ChatMessageController::class, 'destroy'])->name('chat.messages.destroy');

    // ECHO Wallet
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('/wallet/checkout', [WalletController::class, 'checkout'])->name('wallet.checkout');
    Route::get('/wallet/success', [WalletController::class, 'success'])->name('wallet.success');
    Route::get('/wallet/cancel', [WalletController::class, 'cancel'])->name('wallet.cancel');

    // ECHO Donations
    Route::post('/donations', [EchoDonationController::class, 'store'])->name('donations.store');
    Route::get('/donations/history', [EchoDonationController::class, 'history'])->name('donations.history');
});

// Helpdesk
Route::middleware(['auth', 'verified'])->prefix('helpdesk')->name('helpdesk.')->group(function () {
    Route::get('/', [HelpdeskController::class, 'index'])->name('index');
    Route::get('/create', [HelpdeskController::class, 'create'])->name('create');
    Route::post('/', [HelpdeskController::class, 'store'])->name('store');
    Route::get('/{ticket}', [HelpdeskController::class, 'show'])->name('show');
    Route::post('/{ticket}/reply', [HelpdeskController::class, 'reply'])->name('reply');
    Route::post('/{ticket}/resolve', [HelpdeskController::class, 'resolve'])->name('resolve');
    Route::post('/{ticket}/close', [HelpdeskController::class, 'close'])->name('close');
    Route::post('/{ticket}/reopen', [HelpdeskController::class, 'reopen'])->name('reopen');
    Route::post('/{ticket}/assign', [HelpdeskController::class, 'assign'])->name('assign');
});

// Admin Helpdesk
Route::middleware(['auth', 'verified'])->prefix('admin/helpdesk')->name('admin.helpdesk.')->group(function () {
    Route::get('/', [AdminHelpdeskController::class, 'index'])->name('index');
    Route::get('/{ticket}', [AdminHelpdeskController::class, 'show'])->name('show');
});

// Stripe Webhook (no auth, signed by Stripe)
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->name('webhooks.stripe')
    ->withoutMiddleware([
        VerifyCsrfToken::class,
    ]);

require __DIR__.'/auth.php';
