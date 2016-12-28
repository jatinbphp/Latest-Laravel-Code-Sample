<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Services\BackEnd\DashboardService;
use App\Models\MarketingSms;


class SendSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendSms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command Sending schedule sms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
		$scheduledMessage = DB::table("fontana_marketing_sms")->where('status','=','2')->get();		
		foreach($scheduledMessage as $sm){
			
			$userTimeZone = DashboardService::getUserTimezone($sm->restaurant_id);
			date_default_timezone_set($userTimeZone);	
			$currentDate = date('Y-m-d');
			$currentTime = date('H:i').":00";
			$scheduleDate = date("Y-m-d",strtotime($sm->schedule_date));
			$scheduleTime = $sm->schedule_time;
			if($scheduleDate==$currentDate && $scheduleTime==$currentTime){								
				$content = $sm->content;
				$recipients = explode(",",$sm->sms_number);			
				
				foreach($recipients as $number) {		 					
					$number = preg_replace("/[^a-zA-Z0-9]/", "", $number);			
					$authData = "to=".$number."&message=".$sm->content;
					exec('php /var/www/fontana.one/web/tests/Fontana/send_sms_amqp.php '.$number.' '.urlencode($content),$out);							
				}
				$statusMarketingMail = MarketingSms::find($sm->id);
				$statusMarketingMail->status = 0;
				$statusMarketingMail->save();
			}			
		}		
    }
}
