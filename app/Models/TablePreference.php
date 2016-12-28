<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;
class TablePreference extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	
	protected $table = "fontana_preference_tables_restaurant";	
	
	public function fetchTablePref($id){		
		return $tablePref = DB::table('fontana_preference_tables_restaurant')->where('restaurant_id','=',$id)->get();
	}

}
