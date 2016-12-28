<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SmsSend extends Event
{
    use SerializesModels;
    public $input;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($input)
    {		
        $this->input = $input;
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
