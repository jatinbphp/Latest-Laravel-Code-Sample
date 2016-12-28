<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;
class Customer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	
	protected $table = "fontana_restaurant_customer";	
	
	public function AddCustomer($data,$id=null){
		
		$userExistorNot =  DB::table('fontana_restaurant_customer')->where('phone','=',$data['phoneNum'])->first();
		
		if(count($userExistorNot)==0){
			$customer = new static();
			$customer->phone = $data['phoneNum'];
			$customer->email = $data['UserEmail'];
			$customer->restaurant_id = $id;
			$customer->name = $data['firstName'];
			
			if(isset($data['organization']))
				$customer->organization = $data['organization'];
			
			if(isset($data['birthday']))
				 $customer->birthday = date("Y-m-d",strtotime($data['birthday']));
				
			if(isset($data['Opt_in']))
				$customer->opt_in = 1;		
				
			if(isset($data['vipClient']))
				$customer->vip = 1;			
										
			$customer->save();
			return $customer->id;
		}else{
			
			$customer = Customer::find($userExistorNot->id);				
			$customer->phone = $data['phoneNum'];
			$customer->email = $data['UserEmail'];
			$customer->name = $data['firstName'];
			
			if(isset($data['organization']))
				$customer->organization = $data['organization'];
			
			if(isset($data['birthday']))
				 $customer->birthday = date("Y-m-d",strtotime($data['birthday']));
				
			if(isset($data['Opt_in']))
				$customer->opt_in = 1;
			
			if(isset($data['vipClient']))
				$customer->vip = 1;			
			
			$customer->save();
			return $userExistorNot->id;
		}		
	}
	
	public function UpdateCustomer($data,$id=null){		
		
			$customer = static::find($data['edit_id']);
			$customer->phone = $data['phoneNum'];
			$customer->email = $data['UserEmail'];			
			$customer->name = $data['firstName'];
			
			if(isset($data['organization']))
				$customer->organization = $data['organization'];
			
			if(isset($data['birthday']))
				$customer->birthday = date("Y-m-d",strtotime($data['birthday']));
				
			if(isset($data['Opt_in']))
				$customer->opt_in = 1;		
										
			$customer->save();		
	}
	
}

