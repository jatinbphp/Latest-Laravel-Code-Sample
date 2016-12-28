<?php

namespace App\Handlers\Events;

use App\Events\SmsSend;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
class SmsFire
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
    public function handle(SmsSend $event)
    {	
		foreach($event->input['sms_recipients'] as $number) {		 			
			
			$number = preg_replace("/[^a-zA-Z0-9]/", "", $number);		
			$gaUrl = "https://www.fontana.one/tests/Fontana/send_sms_amqp_Fontana.php";
			$authData = "to=".$number."&message=".$event->input['smscontent'];
			exec('php /var/www/fontana.one/web/tests/Fontana/send_sms_amqp.php '.$number.' '.urlencode($event->input['smscontent']),$out);							
			
		}
    }
}
