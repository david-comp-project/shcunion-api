<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class DeleteChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $chatId)
    {
        $this->chatId = $chatId;
        $this->user = $user;
    }

    public function broadcastAs() {
        return 'chat-delete-event';
    }

    public function broadcastWith() {

        return [
            "user_id" => $this->user->user_id,
            "chat_id" => $this->chatId
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['chat-delete-channel'];
    }
}