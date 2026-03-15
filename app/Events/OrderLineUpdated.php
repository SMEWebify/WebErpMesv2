<?php

namespace App\Events;

use App\Models\Workflow\OrderLines;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderLineUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderLine;

    /**
     * Create a new event instance.
     */
    public function __construct(OrderLines $orderLine)
    {
        $this->orderLine = $orderLine;
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
