<?php

namespace App\Handlers\Events;

use App\Events\PointAdded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserCheckin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PointAdded  $event
     * @return void
     */
    public function handle(PointAdded $event)
    {
        //
    }
}
