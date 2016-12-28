<?php
namespace App\Services\BackEnd;
use DB;
use Session;
use Lang;
use Image;

class FinancialsServices{
	public static function subscriptionPackages(){
		return $data=DB::table('subscription_packages')
							->where('status', 1)
							->get();
	}
	public static function serviceTypes(){
		return $data=DB::table('services_type')
							->where('status', 1)
							->get();
	}
	public static function saveFinancials($data){
		try{
			$arrayName='';
			foreach ($data['services_included'] as $key => $value) {
				$arrayName[]=$value;
			}
			$data['services_included']=null;
			$data['services_included']=json_encode($arrayName);

			$db= DB::table('subscription_packages')
							->insert([
									'name' => $data['name'],
									'description' => $data['description'],
									'service_included' => $data['services_included'],
									'cost'	=> $data['cost'],
									'status' => 1
	 							]);
			return true;
	}catch(\Exception $e){
		$err_msg = \Lang::get("mysqlError.".$e->errorInfo[1]);
             return $err_msg;
		}
}
	public static function additional_services(){
		return $data=DB::table('additional_services as adds')
							->join('triggers','adds.fk_triggers','=','triggers.id')
							->where('adds.status', 1)
							->orderBy('adds.id','DESC')
							->get([
								   'adds.name',
								   'adds.description',
								   'adds.cost',
								   'adds.per',
								   'triggers.trigger_name',
								   'triggers.id as trigger_id',
								]);
	}
	public static function triggers(){
		return $data=DB::table('triggers')
							->where('status', 1)
							->get();
	}
	
	public static function saveAdditionalServices($data){
		try{
			$db= DB::table('additional_services')
							->insert([
									'name' 			=> $data['name'],
									'description' 	=> $data['description'],
									'fk_triggers' 	=> $data['triggers'],
									'cost'			=> $data['cost'],
									'per'			=> $data['per'],
									'status' 		=> 1
	 							]);
			return true;
	}catch(\Exception $e){
		$err_msg = \Lang::get("mysqlError.".$e->errorInfo[1]);
             return $err_msg;
		}
	}


	public static function restaurant(){
		return $data=DB::table('users')
						->leftjoin('subscription_packages','users.subscription','=','subscription_packages.id')
						->where('users.status', 1)
						->get([https://fontana.one/boilerplate/public/
							'users.*',
							'users.id as user_id',
							'users.name as user_name',
							'users.subscription as subscription_id',
							'subscription_packages.*',
							'subscription_packages.name as subscription_packages_name',
							]);
	}

	public static function saveRevenues($data){
		try{
			$db= DB::table('users')
							->where('id',$data['id'])
							->update([
									'name' 			=> $data['name'],
									'city' 			=> $data['city'],
									'state' 		=> $data['state'],
									'subscription'	=> $data['Subscription'],
									'status'		=> $data['status'],
	 							]);
			return true;
	}catch(\Exception $e){
		$err_msg = \Lang::get("mysqlError.".$e->errorInfo[1]);
             return $err_msg;
		}
	}

	public static function inactiveRevanue($data){
		try{
			$db= DB::table('users')
							->where('id',$data['id'])
							->update([
									'status'		=> 0,
	 							]);
			return true;
	}catch(\Exception $e){
		$err_msg = \Lang::get("mysqlError.".$e->errorInfo[1]);
             return $err_msg;
		}
	}

}
