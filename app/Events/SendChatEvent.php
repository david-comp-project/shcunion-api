<?php

namespace App\Events;

use App\Models\User;
use App\Models\GroupChat;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class SendChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, HasFileTrait;

    public $groupChatId;
    public $userId;
    public $chats;

    /**
     * Create a new event instance.
     */
    public function __construct($chats, $userId, $groupChatId)
    {
        $this->groupChatId = $groupChatId;
        $this->chats = $chats;
        $this->userId = $userId;
    }

    public function broadcastAs() {
        return 'chat-event';
    }

    public function broadcastQueue(): string
    {
        return 'default';
    }
    
    public function broadcastWith() {
        $sender_name = $this->chats->sender->first_name;
        $image_path = $this->chats->media_path;
        $sender_id = $this->chats->sender_id;
        $sender = User::find($sender_id);

        if ($this->userId == null) {
            $chats = [
                'group_chat_id' => $this->groupChatId,
                'sender_id' => $sender_id,
                'sender_name' => $sender_name,
                'chat_text' =>  $this->chats->group_chat_text,
                'image_path' =>  $this->getUrlFile($image_path),
                'chat_send_time' =>$this->chats->dateFormat,
                'avatar' => $this->getUrlFile($sender->profile_picture)

            ];

 
        } else if ($this->groupChatId == null) {
            $chats =  [
                'user_id' => $this->userId,
                'sender_id' => $sender_id,
                'sender_name' => $sender_name,
                'chat_text' =>  $this->chats->private_chat_text,
                'image_path' =>  $this->getUrlFile($image_path),
                'chat_send_time' =>$this->chats->dateFormat,
                'avatar' => $this->getUrlFile($sender->profile_picture)
            ];
        }

        return [
            "chats" => $chats
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['chat-channel'];
    }
}
