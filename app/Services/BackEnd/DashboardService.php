<?php

namespace App\Services\BackEnd;

use App\Http\Helper;
use DB;
use Lang;
use Auth;

class DashboardService
{

    public static function affiliateMarketing()
    {
        return DB::table('affiliate_marketing')
            ->get();
    }

    public static function saveCommissionRate($data = null)
    {
        try {
            if ($data['default_commission_rate'] != $data['ex_commission_rate']) {
                $status = DB::table('affiliate_marketing')
                    ->update([
                        'default_commission_rate' => $data['default_commission_rate'],
                    ]);
                if ($status) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } catch (\Exception $e) {
            $err_msg = \Lang::get("mysqlError." . $e->errorInfo[1]);
            return $err_msg;
        }
    }

    public static function saveAffiliate($data = null)
    {
        try {
            //=====@@ get current commission rate @@=====
            $currentCommissionRate = DB::table('affiliate_marketing')
                ->select(['default_commission_rate'])
                ->first();

            //=======@@ Set some object which are not in affiliate marketing add modal @@=======
            if ($currentCommissionRate) {
                $data['default_commission_rate'] = $currentCommissionRate->default_commission_rate;
            } else {
                $data['default_commission_rate'] = 10;
            }

            $data['last_activity'] = date('Y-m-d h:m:s');
            $data['last_payment']  = 0;
            $data['total_payment'] = 0;
            $data['created_at']    = date('Y-m-d h:m:s');

            //=========@@ Set access if it is empty @@========
            if (!isset($data['access'])) {
                $data['access'] = null;
            }
            $status = DB::table('affiliate_marketing')
                ->insert([
                    'name'                    => $data['name'],
                    'city'                    => $data['city'],
                    'state'                   => $data['state'],
                    'hash_of_acts'            => $data['hash_of_accts'],
                    'access'                  => $data['access'],
                    'last_activity'           => $data['last_activity'],
                    'last_payment'            => $data['last_payment'],
                    'total_payment'           => $data['total_payment'],
                    'default_commission_rate' => $data['default_commission_rate'],
                    'status'                  => 1,
                    'created_at'              => $data['created_at'],
                ]);
            if ($status) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            $err_msg = \Lang::get("mysqlError." . $e->errorInfo[1]);
            return $err_msg;
        }
    }

    public static function saveEditAffiliate($data = null)
    {

        try {
            $status = DB::table('affiliate_marketing')
                ->where('id', $data['affiliateId'])
                ->update([
                    'name'         => $data['name'],
                    'city'         => $data['city'],
                    'state'        => $data['state'],
                    'hash_of_acts' => $data['hash_of_acts'],
                    'access'       => $data['access'],
                    'updated_at'   => date("Y-m-d h-i-s"),
                ]);
            if ($status) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $err_msg = \Lang::get("mysqlError." . $e->errorInfo[1]);
            return $err_msg;
        }
    }

    public static function deactiveAffiliate($id = null)
    {
        try {
            $status = DB::table('affiliate_marketing')
                ->where('id', $id)
                ->update([
                    'status' => 0,
                ]);
            return true;
        } catch (\Exception $e) {
            $err_msg = \Lang::get("mysqlError." . $e->errorInfo[1]);
            return $err_msg;
        }
    }
    public static function activeAffiliate($id = null)
    {
        try {
            $status = DB::table('affiliate_marketing')
                ->where('id', $id)
                ->update([
                    'status' => 1,
                ]);
            return true;
        } catch (\Exception $e) {
            $err_msg = \Lang::get("mysqlError." . $e->errorInfo[1]);
            return $err_msg;
        }
    }

    public static function getMonthWisePayment()
    {
        $data = DB::table("affiliate_wise_payment_history")
            ->select('payment_date', DB::raw("SUM(pay_amount) as total_pay_amount"))
            ->orderBy("payment_date", 'asc')
            ->groupBy(DB::raw("month(payment_date)"))
            ->get();
        $monthWisePayment = ["jan" => 0,
            "feb"                      => 0,
            "mar"                      => 0,
            "apr"                      => 0,
            "may"                      => 0,
            "june"                     => 0,
            "july"                     => 0,
            "aug"                      => 0,
            "sept"                     => 0,
            "oct"                      => 0,
            "nov"                      => 0,
            "dec"                      => 0];
        foreach ($data as $key) {
            $m = 0;
            foreach ($key as $value => $value2) {
                if ($m == 0) {
                    $i = explode('-', $value2);
                    $j = Helper::charMonth($i[1]);
                }
                if ($m == 1) {
                    $monthWisePayment[$j] = $value2;
                }
                $m++;
            }
        }
        return $monthWisePayment;
    }

    public static function getUserTimezone($id)
    {
        $data = DB::table("users")->select(array('timezone'))->where('id', '=', $id)->first();
        return $data->timezone;
    }
    public static function getUserBirthday($day)
    {

        $birthdayInNextDays = $day;
         $user = Auth::User();
        $rangeDate          = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $birthdayInNextDays, date('Y')));
        $currendMonth       = date('m');
        $rangeMonth         = date('m', strtotime($rangeDate));
        if ($rangeMonth >= $currendMonth) {
            $startDate = date('Y-m-d');
            $endDate   = $rangeDate;
        } else {
            $startDate = $rangeDate;
            $endDate   = date('Y-m-d');
        }
        //echo 'SELECT * FROM fontana_restaurant_customer WHERE DATE_FORMAT(birthday, "%m-%d") BETWEEN DATE_FORMAT("'.$startDate.'","%m-%d") AND DATE_FORMAT("'.$endDate.'","%m-%d")';exit;
        return $result = DB::select('SELECT * FROM fontana_restaurant_customer WHERE DATE_FORMAT(birthday, "%m-%d") BETWEEN DATE_FORMAT("' . $startDate . '","%m-%d") AND DATE_FORMAT("' . $endDate . '","%m-%d") and restaurant_id != '.$user->id);

    }
    public static function getUserBirthdayVip($day)
    {
        $birthdayInNextDays = $day;
        $user = Auth::User();
        $rangeDate          = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $birthdayInNextDays, date('Y')));
        $currendMonth       = date('m');
        $rangeMonth         = date('m', strtotime($rangeDate));
        if ($rangeMonth >= $currendMonth) {
            $startDate = date('Y-m-d');
            $endDate   = $rangeDate;
        } else {
            $startDate = $rangeDate;
            $endDate   = date('Y-m-d');
        }

        //echo 'SELECT fontana_restaurant_customer.*,fontana_restaurant_wait_list.vip_status FROM fontana_restaurant_customer Left Join fontana_restaurant_wait_list on fontana_restaurant_customer.id =  fontana_restaurant_wait_list.customer_id WHERE fontana_restaurant_wait_list.vip_status = "1" and DATE_FORMAT(fontana_restaurant_customer.birthday, "%m-%d") BETWEEN DATE_FORMAT("' . $startDate . '","%m-%d") AND DATE_FORMAT("' . $endDate . '","%m-%d") and restaurant_id = '.$user->id;exit;
        return $result = DB::select('SELECT fontana_restaurant_customer.*,fontana_restaurant_wait_list.vip_status FROM fontana_restaurant_customer Left Join fontana_restaurant_wait_list on fontana_restaurant_customer.id =  fontana_restaurant_wait_list.customer_id WHERE fontana_restaurant_wait_list.vip_status = "1" and DATE_FORMAT(fontana_restaurant_customer.birthday, "%m-%d") BETWEEN DATE_FORMAT("' . $startDate . '","%m-%d") AND DATE_FORMAT("' . $endDate . '","%m-%d") and restaurant_id != '.$user->id);
    }

    public static function userIsVipOrNot($cid, $rid)
    {
        $rid = 2;
        $cid = 3;        
        try {
            $resultArray            = array();
            $restaurantVipCondition = DB::table('fontana_restaurant_vip')->where('restaurant_id', '=', $rid)->get();
            foreach ($restaurantVipCondition as $condition) {
                if ($condition->points_checkin == 'Points') {

                    $todayDate = date('Y-m-d h:i:s');
                    if ($condition->duration == 'Days') {
                        $conditionlimitDate = date("Y-m-d h:i:s", strtotime($todayDate) - (60 * 60 * 24 * $condition->number));
                    }
                    if ($condition->duration == 'Months') {
                        $conditionlimitDate = date("Y-m-d h:i:s", strtotime($todayDate) - (60 * 60 * 24 * 30 * $condition->number));
                    }
                    if ($condition->duration == 'Years') {
                        $conditionlimitDate = date('Y-m-d h:i:s', mktime(0, 0, 0, date('m', strtotime($todayDate)), date('d', strtotime($todayDate)), date('Y', strtotime($todayDate)) - $condition->number));
                    }
                    $fetchRecordPoitn = DB::table('fontana_restaurant_customer_visit')->select(array('point_earned'))->where('customer_id', '=', $cid)->whereBetween('created_at', [$conditionlimitDate, $todayDate])->sum('point_earned');

                    if ($fetchRecordPoitn == "") {
                        $fetchRecordPoitn = 0;
                    }

                }
            }

        } catch (\Exception $e) {
            echo $e->getMessage();exit;
        }
    }

    /**
     * Find total Customer as per addded in input box
     * 
     * @param integer $totalCustomer total value of 
     * custoem passed form DashboardController
     * 
     * @return integer     
     */

    public static function getTotalCusotmer($totalCustomer)
    {
        try{
            $user = Auth::User();            
            return DB::table("fontana_restaurant_customer")
                    ->where('restaurant_id', '!=' ,$user->id)
                    ->take($totalCustomer)
                    ->get();

        }catch(\Exception $e){
            echo $e->getMessage();exit;
        }
    }

    /**
     * Find Vip Users     
     * 
     * @return integer     
     */

    public static function getUserVip()
    {
        $user = Auth::User();      
        $vipUsers = DB::table('fontana_restaurant_wait_list')
                    ->where('vip_status', '=', 1)
                    ->where('restaurant_id', '!=', $user->id)
                    ->groupBy('customer_id')->get();
        return $vipUsers;
    }

    public static function getFeatureWaitlistNotificationData($field)
    {
        $data = DB::table('fontana_feature_waitlist')->first();
        return $data->$field;
    }

}
