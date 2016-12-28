<?php

namespace App\Http\Middleware;

use Closure;
use DB;

/**
 * Class RouteNeedsRole
 * @package App\Http\Middleware
 */
class SuperAdmin
{

	/**
     * @param $request
     * @param Closure $next
     * @param $role
     * @param bool $needsAll
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       $user = \Auth::User();
	   $currentRole = DB::table('assigned_roles')->where('user_id','=',$user->id)->first();
	   if($currentRole->role_id!=1){
		    return redirect()
			->route('frontend.index')
			->withFlashDanger(trans('auth.general_error'));
	   }
	   return $next($request);
       
    }
}
