<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
class Restaurant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $table = "fontana_restaurant";
	
	public function duplicateEmail($email){
	$email_count = DB::table('users')
		->select('email')
		->where('email',$email)
		->count();
		if($email_count==0)
			return false;
		else
			return true;
	}
}
