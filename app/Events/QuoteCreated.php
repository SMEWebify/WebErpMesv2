<?php

namespace App\Events;

use App\Models\Workflow\Quotes;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class QuoteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quote;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Workflow\Quotes  $quote
     * @return void
     */
    public function __construct(Quotes $quote)
    {
        $this->quote = $quote;
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
