<?php

namespace App\Events;

use App\Models\DonationPayment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DonationPaymentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $donation;

    public function __construct(DonationPayment $donation)
    {
        $this->donation = $donation;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('donation.'.$this->donation->donation_payment_id);
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->donation->donation_payment_id,
            'status' => $this->donation->status,
        ];
    }

    public function broadcastAs()
    {
        return 'donation.payment';
    }
}
