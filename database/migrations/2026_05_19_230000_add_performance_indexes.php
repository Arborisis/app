<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sounds table - most queried table
        Schema::table('sounds', function (Blueprint $table) {
            if (! $this->hasIndex('sounds', 'sounds_is_public_idx')) {
                $table->index('is_public', 'sounds_is_public_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_user_id_idx')) {
                $table->index('user_id', 'sounds_user_id_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_category_id_idx')) {
                $table->index('category_id', 'sounds_category_id_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_environment_id_idx')) {
                $table->index('environment_id', 'sounds_environment_id_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_play_count_idx')) {
                $table->index('play_count', 'sounds_play_count_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_created_at_idx')) {
                $table->index('created_at', 'sounds_created_at_idx');
            }
            if (! $this->hasIndex('sounds', 'sounds_recorded_at_idx')) {
                $table->index('recorded_at', 'sounds_recorded_at_idx');
            }
        });

        // Sound locations - geo queries
        Schema::table('sound_locations', function (Blueprint $table) {
            if (! $this->hasIndex('sound_locations', 'sound_locations_public_lat_idx')) {
                $table->index('public_latitude', 'sound_locations_public_lat_idx');
            }
            if (! $this->hasIndex('sound_locations', 'sound_locations_public_lng_idx')) {
                $table->index('public_longitude', 'sound_locations_public_lng_idx');
            }
            if (! $this->hasIndex('sound_locations', 'sound_locations_location_name_idx')) {
                $table->index('location_name', 'sound_locations_location_name_idx');
            }
        });

        // Likes - social features
        Schema::table('likes', function (Blueprint $table) {
            if (! $this->hasIndex('likes', 'likes_user_id_idx')) {
                $table->index('user_id', 'likes_user_id_idx');
            }
            if (! $this->hasIndex('likes', 'likes_created_at_idx')) {
                $table->index('created_at', 'likes_created_at_idx');
            }
        });

        // Comments - social features
        Schema::table('comments', function (Blueprint $table) {
            if (! $this->hasIndex('comments', 'comments_user_id_idx')) {
                $table->index('user_id', 'comments_user_id_idx');
            }
            if (! $this->hasIndex('comments', 'comments_created_at_idx')) {
                $table->index('created_at', 'comments_created_at_idx');
            }
        });

        // Follows - social features
        Schema::table('follows', function (Blueprint $table) {
            if (! $this->hasIndex('follows', 'follows_following_id_idx')) {
                $table->index('following_id', 'follows_following_id_idx');
            }
            if (! $this->hasIndex('follows', 'follows_created_at_idx')) {
                $table->index('created_at', 'follows_created_at_idx');
            }
        });

        // Quest progress - gamification
        Schema::table('quest_progress', function (Blueprint $table) {
            if (! $this->hasIndex('quest_progress', 'qp_user_id_status_idx')) {
                $table->index(['user_id', 'status'], 'qp_user_id_status_idx');
            }
            if (! $this->hasIndex('quest_progress', 'qp_updated_at_idx')) {
                $table->index('updated_at', 'qp_updated_at_idx');
            }
        });

        // User achievements - gamification
        Schema::table('user_achievements', function (Blueprint $table) {
            if (! $this->hasIndex('user_achievements', 'ua_user_unlocked_idx')) {
                $table->index(['user_id', 'unlocked_at'], 'ua_user_unlocked_idx');
            }
        });

        // User medals - gamification
        Schema::table('user_medals', function (Blueprint $table) {
            if (! $this->hasIndex('user_medals', 'um_user_unlocked_idx')) {
                $table->index(['user_id', 'unlocked_at'], 'um_user_unlocked_idx');
            }
        });

        // Sound listens - analytics
        Schema::table('sound_listens', function (Blueprint $table) {
            if (! $this->hasIndex('sound_listens', 'sl_user_sound_idx')) {
                $table->index(['user_id', 'sound_id'], 'sl_user_sound_idx');
            }
            if (! $this->hasIndex('sound_listens', 'sl_created_at_idx')) {
                $table->index('created_at', 'sl_created_at_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sounds', function (Blueprint $table) {
            $table->dropIndex(['sounds_is_public_idx']);
            $table->dropIndex(['sounds_user_id_idx']);
            $table->dropIndex(['sounds_category_id_idx']);
            $table->dropIndex(['sounds_environment_id_idx']);
            $table->dropIndex(['sounds_play_count_idx']);
            $table->dropIndex(['sounds_created_at_idx']);
            $table->dropIndex(['sounds_recorded_at_idx']);
        });

        Schema::table('sound_locations', function (Blueprint $table) {
            $table->dropIndex(['sound_locations_public_lat_idx']);
            $table->dropIndex(['sound_locations_public_lng_idx']);
            $table->dropIndex(['sound_locations_location_name_idx']);
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex(['likes_user_id_idx']);
            $table->dropIndex(['likes_created_at_idx']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['comments_user_id_idx']);
            $table->dropIndex(['comments_created_at_idx']);
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex(['follows_following_id_idx']);
            $table->dropIndex(['follows_created_at_idx']);
        });

        Schema::table('quest_progress', function (Blueprint $table) {
            $table->dropIndex(['qp_user_id_status_idx']);
            $table->dropIndex(['qp_updated_at_idx']);
        });

        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropIndex(['ua_user_unlocked_idx']);
        });

        Schema::table('user_medals', function (Blueprint $table) {
            $table->dropIndex(['um_user_unlocked_idx']);
        });

        Schema::table('sound_listens', function (Blueprint $table) {
            $table->dropIndex(['sl_user_sound_idx']);
            $table->dropIndex(['sl_created_at_idx']);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SELECT indexname FROM pg_indexes WHERE tablename = ?",
            [$table]
        );

        return collect($indexes)->contains('indexname', $index);
    }
};
