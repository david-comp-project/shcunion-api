<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $notification)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    /**
     * Menentukan nama event yang akan diterima di frontend
     */
    public function broadcastAs()
    {
        return 'notify-event';
    }

    public function broadcastWith() {
        return [
            'target_id' => $this->userId,
            'notification' => $this->notification
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel("notify.{$this->userId}");
    }
}
