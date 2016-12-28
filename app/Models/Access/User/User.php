<?php

namespace App\Models\Access\User;

use App\Models\Access\User\Traits\UserAccess;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Access\User\Traits\Attribute\UserAttribute;
use App\Models\Access\User\Traits\Relationship\UserRelationship;
use DB;

/**
 * Class User
 * @package App\Models\Access\User
 */
class User extends Authenticatable
{

    use SoftDeletes, UserAccess, UserAttribute, UserRelationship;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'status', 'confirmation_code', 'confirmed'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    
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
	
	public function duplicateEmailEdit($email,$id){
		
	$email_count = DB::table('users')
		->select('email')
		->where('email',$email)
		->where('id','!=',$id)
		->toSql();
		if($email_count==0)
			return false;
		else
			return true;
	}
	
	public function saveRole($id,$rid)
	{
		DB::table('assigned_roles')->insert(
				['user_id' => $id, 'role_id' => $rid]
		);
	}

    /**
     * @var array
     */
    protected $dates = ['deleted_at'];
}
