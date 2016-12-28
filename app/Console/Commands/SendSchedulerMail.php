<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Models\Access\User\User;
use App\Services\BackEnd\DashboardService;
use App\Models\MarketingMail;

class SendSchedulerMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'command:Send Scheduler Mail';
    
    protected $signature = 'SendSchedulerMail';
    
    public $logger;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will send scheduler mail of marketing set by RA';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        \Log::useDailyFiles(storage_path().'/logs/cron123.log');

		$this->logger = \Log::getMonolog();

		
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
     
    public function handle()
    {   
		$scheduledMessage = DB::table("fontana_marketing_mail")->where('status','=','2')->get();			
		
		foreach($scheduledMessage as $sm){
			$userTimeZone = DashboardService::getUserTimezone($sm->restaurant_id);
			date_default_timezone_set($userTimeZone);	
			 $currentDate = date('Y-m-d');
			 $currentTime = date('H:i').":00";
			 
			$scheduleDate = date("Y-m-d",strtotime($sm->schedule_date));
			$scheduleTime = date("h:i",strtotime($sm->schedule_time)).":00";
			$this->logger->info($sm->restaurant_id." ".$scheduleTime." ".date("h:i:s"));
			$subject = $sm->subject;
			if($scheduleDate==$currentDate && $scheduleTime==$currentTime){				
				$content = $sm->content;
				$recipients = explode(",",$sm->email_id);
				
				$UserSMTP = DB::table("users")->where('id','=',$sm->restaurant_id)->first();					
				
				if($UserSMTP->smtp_email!="" && $UserSMTP->smtp_name!="" && $UserSMTP->smtp_password!=""){				
					
					\Config::set('mail.username', $UserSMTP->smtp_email);	
					\Config::set('mail.host','smtp.gmail.com');		 
					\Config::set('mail.password', $UserSMTP->smtp_password);					
					(new \Illuminate\Mail\MailServiceProvider(app()))->register();
				}
				
				\Mail::send('backend.marketingmail', ['content' => $content], function ($message) use ($recipients,$UserSMTP,$subject)
				{					
					$message->from($UserSMTP->email, $UserSMTP->name);
					$message->to($recipients);
					$message->subject($subject);
				});	
				
				/*
				\Mail::send('backend.marketingmail', ['content' => $content], function ($message) use ($recipients)
				{					
					$message->from('jatin.b.php@gmail.com', 'Jatin Bhatt');
					$message->to($recipients);
					$message->subject("Mail from Fontana");            	
				});
				*/
				$statusMarketingMail = MarketingMail::find($sm->id);
				$statusMarketingMail->status = 0;
				$statusMarketingMail->save();
			}			
		}
    }
    
    public function sendMail(){
		
	}
}
