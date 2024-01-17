<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeliveryLineUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryLineId;


    /**
     * Create a new event instance.
     */
    public function __construct($deliveryLineId)
    {
        $this->deliveryLineId = $deliveryLineId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
