<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use App\Repositories\Backend\Access\User\UserRepositoryContract;
use Auth;
use View;
/**
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{	
	protected $users;
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;
     public function __construct(){
			$user = Auth::User();					
			 
			if(!empty($user)){
				View::share ( 'user_image', $user->image );
				if($user->timezone!="")	{
					\Config::set('app.timezone', $user->timezone);
					date_default_timezone_set($user->timezone);	 
				}
			}
	 }
     
}
