<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
class Waitlist extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	use SoftDeletes;
	protected $table = "fontana_restaurant_wait_list";	
	protected $dates = ['deleted_at'];
	
	function last_added_waitlist($waitlist,$id){
		
		$reset_time_array = explode(":",$waitlist);		
		$current_time = strtotime(date('H:i'));
		if($current_time<=strtotime($waitlist)){
			$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d"),date("Y")));				
			$previous_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d")-1,date("Y")));						
			$lessDate = $previous_day;
			$grdate =  $today_date;
		}
		else{
			$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d"),date("Y")));				
			$next_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d")+1,date("Y")));						
			$lessDate = $today_date;
			$grdate =  $next_day;
		}
		
		
		return $last_added_rank =  DB::table('fontana_restaurant_wait_list')
					->whereBetween("created_at",array($lessDate, $grdate))
					->where('restaurant_id','=',$id)			
					->where('current_status','=','Initial Checkin')						
					->orderBy('id','desc')
					->first();
	}
	
	function count_total_waitlist($waitlist,$id){
		
			$reset_time_array = explode(":",$waitlist);
			$current_time = strtotime(date('H:i'));
			if($current_time<=strtotime($waitlist)){
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d"),date("Y")));				
				$previous_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d")-1,date("Y")));						
				$lessDate = $previous_day;
				$grdate =  $today_date;
			}
			else{
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d"),date("Y")));				
				$next_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d")+1,date("Y")));						
				$lessDate = $today_date;
				$grdate =  $next_day;
			}
			return	$total_waitlist =  DB::table('fontana_restaurant_wait_list')
				->whereBetween("created_at",array($lessDate, $grdate))
				->where('restaurant_id','=',$id)							
				->where('current_status','!=','seated')
				->where('current_status','!=','Deleted')
				->whereNull('deleted_at')					
				->count();
	
	}
	function count_total_waitlist_for_day($waitlist,$id){
		
			$reset_time_array = explode(":",$waitlist);
			$current_time = strtotime(date('H:i'));
			if($current_time<=strtotime($waitlist)){
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d"),date("Y")));				
				$previous_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d")-1,date("Y")));						
				$lessDate = $previous_day;
				$grdate =  $today_date;
			}
			else{
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d"),date("Y")));				
				$next_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d")+1,date("Y")));						
				$lessDate = $today_date;
				$grdate =  $next_day;
			}
			
			return $total_waitlist =  DB::table('fontana_restaurant_wait_list')
				->whereBetween("created_at",array($lessDate, $grdate))
				->where('restaurant_id','=',$id)																		
				->count();
	
	}
	
	
	function fetchLessGrDate($reset){
		
			$current_time = strtotime(date('H:i'));
			$reset_time_array = explode(":",$reset);	
			if($current_time<=strtotime($reset)){
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d"),date("Y")));				
				$previous_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d")-1,date("Y")));						
				$lessDate = $previous_day;
				$grdate =  $today_date;
			}else{
				$today_date = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1]+1,00,date("m"),date("d"),date("Y")));				
				$next_day = date("Y-m-d H:i:s",mktime($reset_time_array[0],$reset_time_array[1],00,date("m"),date("d")+1,date("Y")));						
				$lessDate = $today_date;
				$grdate =  $next_day;
			}
			$data['lessDate'] = $lessDate;
			$data['grdate'] = $grdate;
			return $data;

	}
}
