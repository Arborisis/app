<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Enums\ChatMessageType;
use App\Enums\ChatRoomType;
use App\Events\MessageSent;
use App\Events\PrivateMessageSent;
use App\Events\UserBanned;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPrivateMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    public function createRoom(string $name, ?string $description, ChatRoomType $type, User $creator): ChatRoom
    {
        return DB::transaction(function () use ($name, $description, $type, $creator) {
            return ChatRoom::create([
                'name' => $name,
                'slug' => Str::slug($name.'-'.uniqid()),
                'description' => $description,
                'type' => $type->value,
                'created_by' => $creator->id,
            ]);
        });
    }

    public function sendRoomMessage(User $user, ChatRoom $room, string $body): ChatMessage
    {
        if (! $room->isMember($user)) {
            throw new \InvalidArgumentException('Vous ne participez pas à ce salon.');
        }

        if ($room->isBanned($user)) {
            throw new \InvalidArgumentException('Vous êtes exclu de ce salon.');
        }

        return DB::transaction(function () use ($user, $room, $body) {
            $message = ChatMessage::create([
                'chat_room_id' => $room->id,
                'user_id' => $user->id,
                'body' => $body,
                'type' => ChatMessageType::Text->value,
            ]);

            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    public function sendPrivateMessage(User $user, ChatConversation $conversation, string $body): ChatPrivateMessage
    {
        if (! $conversation->users()->where('users.id', $user->id)->exists()) {
            throw new \InvalidArgumentException('Vous ne participez pas à cette conversation.');
        }

        return DB::transaction(function () use ($user, $conversation, $body) {
            $message = ChatPrivateMessage::create([
                'chat_conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => $body,
            ]);

            broadcast(new PrivateMessageSent($message))->toOthers();

            return $message;
        });
    }

    public function joinRoom(User $user, ChatRoom $room): void
    {
        if ($room->type === ChatRoomType::AdminOnly && ! $user->isModerator()) {
            throw new \InvalidArgumentException('Ce salon est réservé aux modérateurs.');
        }

        if ($room->isBanned($user)) {
            throw new \InvalidArgumentException('Vous êtes exclu de ce salon.');
        }

        DB::transaction(function () use ($user, $room) {
            $room->users()->syncWithoutDetaching([
                $user->id => ['joined_at' => now()],
            ]);
        });
    }

    public function leaveRoom(User $user, ChatRoom $room): void
    {
        DB::transaction(function () use ($user, $room) {
            $room->users()->detach($user->id);
        });
    }

    public function banUserFromRoom(User $target, ChatRoom $room, User $admin): void
    {
        if ($target->id === $admin->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous bannir vous-même.');
        }

        DB::transaction(function () use ($target, $room, $admin) {
            $room->users()->updateExistingPivot($target->id, [
                'banned_at' => now(),
            ]);

            broadcast(new UserBanned($room, $target, $admin))->toOthers();
        });
    }

    public function unbanUserFromRoom(User $target, ChatRoom $room): void
    {
        DB::transaction(function () use ($target, $room) {
            $room->users()->updateExistingPivot($target->id, [
                'banned_at' => null,
            ]);
        });
    }

    public function deleteMessage(ChatMessage $message, User $deleter): void
    {
        DB::transaction(function () use ($message, $deleter) {
            $message->update([
                'deleted_at' => now(),
                'deleted_by' => $deleter->id,
            ]);
        });
    }

    public function createConversation(User $userA, User $userB): ChatConversation
    {
        return DB::transaction(function () use ($userA, $userB) {
            $existing = ChatConversation::whereHas('users', function ($q) use ($userA) {
                $q->where('users.id', $userA->id);
            })->whereHas('users', function ($q) use ($userB) {
                $q->where('users.id', $userB->id);
            })->whereRaw('(select count(*) from chat_conversation_user where chat_conversation_id = chat_conversations.id) = 2')
                ->first();

            if ($existing) {
                return $existing;
            }

            $conversation = ChatConversation::create(['type' => 'private']);
            $conversation->users()->attach([$userA->id, $userB->id]);

            return $conversation;
        });
    }
}
