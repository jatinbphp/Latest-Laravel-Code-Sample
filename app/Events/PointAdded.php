<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;


class PointAdded extends Event
{
    use SerializesModels;
    public $visitorData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($visitorData)
    {
        $this->visitorData = $visitorData;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
