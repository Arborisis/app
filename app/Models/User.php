<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\Profile\ProfileAvatarService;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'slug', 'email', 'password', 'role', 'xp_total', 'level', 'current_streak', 'longest_streak', 'last_activity_at', 'geo_consent_given_at'];

    protected $hidden = ['password', 'remember_token'];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->slug)) {
                $user->slug = Str::slug($user->name.'-'.uniqid());
            }
            if (empty($user->role)) {
                $user->role = UserRole::User->value;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class);
    }

    public function soundListens(): HasMany
    {
        return $this->hasMany(SoundListen::class);
    }

    public function pointReports(): HasMany
    {
        return $this->hasMany(PointReport::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('followed_id', $user->id)->exists();
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('follows as f2')
                    ->whereColumn('f2.follower_id', 'follows.followed_id')
                    ->whereColumn('f2.followed_id', 'follows.follower_id')
                    ->whereNull('f2.deleted_at');
            });
    }

    public function isFriend(User $user): bool
    {
        return $this->isFollowing($user) && $user->isFollowing($this);
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function echoTransactions(): HasMany
    {
        return $this->hasMany(EchoTransaction::class);
    }

    public function donationsSent(): HasMany
    {
        return $this->hasMany(EchoDonation::class, 'donor_id');
    }

    public function donationsReceived(): HasMany
    {
        return $this->hasMany(EchoDonation::class, 'recipient_id');
    }

    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user')
            ->withPivot(['banned_at', 'joined_at'])
            ->withTimestamps();
    }

    public function chatConversations(): BelongsToMany
    {
        return $this->belongsToMany(ChatConversation::class, 'chat_conversation_user')
            ->withTimestamps();
    }

    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'xp_total' => 'integer',
            'level' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_at' => 'datetime',
            'geo_consent_given_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator || $this->role === UserRole::Admin;
    }

    public function isCreator(): bool
    {
        return $this->role === UserRole::Creator || $this->isModerator() || $this->isAdmin();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $this->loadMissing('profile');

        return app(ProfileAvatarService::class)->url($this->profile?->avatar);
    }

    public function discordAccount(): HasOne
    {
        return $this->hasOne(UserDiscordAccount::class);
    }

    public function isLinkedToDiscord(): bool
    {
        return $this->discordAccount()->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isModerator();
    }

    public function arborisisPoints(): HasMany
    {
        return $this->hasMany(ArborisisPoint::class);
    }

    public function soundWalks(): HasMany
    {
        return $this->hasMany(SoundWalk::class);
    }

    public function arborisisVisits(): HasMany
    {
        return $this->hasMany(ArborisisVisit::class);
    }

    public function questProgress(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at', 'progress_snapshot')
            ->withTimestamps();
    }

    public function medals(): BelongsToMany
    {
        return $this->belongsToMany(Medal::class, 'user_medals')
            ->withPivot('unlocked_at', 'source_type', 'source_id')
            ->withTimestamps();
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(XpEvent::class);
    }

    public function soundIdeaProgress(): HasMany
    {
        return $this->hasMany(UserSoundIdeaProgress::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
