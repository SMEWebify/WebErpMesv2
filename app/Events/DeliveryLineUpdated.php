<?php

namespace App\Events;

use App\Models\Workflow\DeliveryLines;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeliveryLineUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryLine;


    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryLines $deliveryLine)
    {
        $this->deliveryLine = $deliveryLine;
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
