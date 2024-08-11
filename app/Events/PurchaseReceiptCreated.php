<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Models\Purchases\PurchaseReceipt;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PurchaseReceiptCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseReceipt;
    
    /**
     * Create a new event instance.
     */
    public function __construct(PurchaseReceipt $purchaseReceipt)
    {
        $this->purchaseReceipt = $purchaseReceipt;
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
