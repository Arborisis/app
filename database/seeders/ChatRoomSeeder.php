<?php

namespace Database\Seeders;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatRoomSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::first();

        if (! $admin) {
            return;
        }

        ChatRoom::create([
            'name' => 'Général',
            'slug' => 'general',
            'description' => 'Discussions générales sur la nature et le field recording.',
            'type' => 'public',
            'created_by' => $admin->id,
        ]);

        ChatRoom::create([
            'name' => 'Technique',
            'slug' => 'technique',
            'description' => 'Équipement, techniques de recording, post-production.',
            'type' => 'public',
            'created_by' => $admin->id,
        ]);

        ChatRoom::create([
            'name' => 'Modération',
            'slug' => 'moderation',
            'description' => 'Salon réservé à l\'équipe de modération.',
            'type' => 'admin_only',
            'created_by' => $admin->id,
        ]);
    }
}
