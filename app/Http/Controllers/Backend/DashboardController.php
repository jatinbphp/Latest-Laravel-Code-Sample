<?php

/**
 *  Dashboard controller
 * 
 *  PHP Version 5.6
 * 
 *  @category Dashboard 
 *  
 *  @package  Fontana 
 *  
 *  @author   Jatin Bhatt <jatin.b.php@gmail.com> 
 *  
 *  @license  http://jatinbhattphp.wordpress.com/ Free 
 *  
 *  @link     As per routes
 */

namespace App\Http\Controllers\Backend;

use App;
use App\Events\PointAdded;
use App\Events\SmsSend;
use App\Helpers\CommonHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AffiliateRequest;
use App\Models\Access\User\User;
use App\Models\Customer;
use App\Models\CustomerVisit;
use App\Models\DefaultSet;
use App\Models\FeatureWaitlist;
use App\Models\ManageReward;
use App\Models\MarketingMail;
use App\Models\MarketingSms;
use App\Models\NetworkMarketing;
use App\Models\Redeem;
use App\Models\RedeemHistory;
use App\Models\Referral;
use App\Models\Restaurant;
use App\Models\TablePreference;
use App\Models\VipQualification;
use App\Models\Waitlist;
use App\Repositories\Backend\Access\Role\RoleRepositoryContract;
use App\Repositories\Backend\Access\User\UserRepositoryContract;
use App\Services\BackEnd\DashboardService;
use App\Services\BackEnd\FinancialsServices;
use Auth;
use DB;
use Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Image;
use Session;
use Yajra\Datatables\Facades\Datatables;

/**
 *  Dashboard controller
 * 
 *  PHP Version 5.6
 * 
 *  @category Dashboard 
 *  
 *  @package  Fontana 
 *  
 *  @author   Jatin Bhatt <jatin.b.php@gmail.com> 
 *  
 *  @license  http://jatinbhattphp.wordpress.com/ Free 
 *  
 *  @link     As per routes
 */

class DashboardController extends Controller
{
    /**
     * @return \Illuminate\View\View
     */

    protected $users;

    /**
     * @var RoleRepositoryContract
     */
    protected $roles;

    public $user_id;
    /**
     * @param UserRepositoryContract $users
     * @param RoleRepositoryContract $roles
     */
    public function __construct(UserRepositoryContract $users, RoleRepositoryContract $roles)
    {
        $this->users   = $users;
        $this->roles   = $roles;
        $user          = Auth::User();
        $this->user_id = $user->id;
        parent::__construct();
    }

    public function index()
    {
        return view('backend.dashboard');
    }

    public function manage_waitlist(User $user)
    {

        $user = Auth::User();
        $User = User::find($user->id);

        if ($User->reset_time != '' && $User->timezone != '') {
            $timezone_resettim_notset = 1;
            $wailistarray             = array("1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "10" => 10);
            $waitlist                 = new Waitlist;
            $total_waitlist           = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time          = ($total_waitlist + 1) * ($User->estimation_wait_time);
        } else {
            $timezone_resettim_notset = 0;
            $wailistarray             = array();
            $total_waitlist           = 0;
            $total_wait_time          = 0;
        }
        $chekin_point           = $user->checkin_point;
        $checkin_point_duration = $user->checkin_point_duration;
        $TablePreference        = new TablePreference();
        $table_pref             = $TablePreference->fetchTablePref($this->user_id);

        /*
         *Get Notify first values
         *
         */

        $conditionDate = $waitlist->fetchLessGrDate($user->reset_time);

        $notifyFirst = DB::table('fontana_restaurant_wait_list')
            ->whereBetween("created_at", array($conditionDate['lessDate'], $conditionDate['grdate']))
            ->where('restaurant_id', '=', $user->id)
            ->where('current_status', '!=', 'seated')
            ->where('current_status', '!=', 'Deleted')
            ->where('check_in_status', '=', '0')
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        return view('backend.manage_waitlist')->with('wailistarray', $wailistarray)->with('waitlist', $User->estimation_wait_time)->with('total_waitlist', $total_waitlist)->with('total_wait_time', $total_wait_time)->with('table_pref', $table_pref)->with('timezone_resettim_notset', $timezone_resettim_notset)->with('checkin_point_duration', $checkin_point_duration)->with('chekin_point', $chekin_point)->with(compact('notifyFirst'));
    }

    public function get_waitlist(Request $request)
    {
        $user          = Auth::User();
        $waitlist      = new Waitlist;
        $conditionDate = $waitlist->fetchLessGrDate($user->reset_time);

        return Datatables::of(DB::table('fontana_restaurant_wait_list')
                ->whereBetween("created_at", array($conditionDate['lessDate'], $conditionDate['grdate'])))
            ->where('restaurant_id', '=', $user->id)
            ->where('current_status', '!=', 'seated')
            ->where('current_status', '!=', 'Deleted')
            ->where('check_in_status', '=', '0')
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->make(true);
    }

    public function get_waitlist_history(Request $request)
    {
        $user          = Auth::User();
        $waitlist      = new Waitlist;
        $conditionDate = $waitlist->fetchLessGrDate($user->reset_time);
        return Datatables::of(DB::table('fontana_restaurant_wait_list')
                ->whereBetween("created_at", array($conditionDate['lessDate'], $conditionDate['grdate'])))
            ->where('restaurant_id', '=', $user->id)
            ->Where(function ($query) {
                $query->where('current_status', '=', 'seated')
                    ->orWhere('deleted_at', '!=', null);
            })
            ->where('check_in_status', '=', '0')
            ->orderBy('id', 'asc')
            ->make(true);
    }

    public function restaurant_add(Request $request)
    {

        $method = $request->method();

        if ($request->isMethod('post')) {

            $input = $request->all();

            $Restaurant = new User;

            if ($input['password'] != $input['confirm']) {
                Session::flash('error', 'Password and Confirm password does not matched!');
                return redirect('admin/restaurant_add')->withInput();
            }

            if (!$Restaurant->duplicateEmail($input['email'])) {
                $Restaurant->name       = $input['name'];
                $Restaurant->address    = $input['address'];
                $Restaurant->phone      = $input['phone'];
                $Restaurant->website    = $input['website'];
                $Restaurant->owner_name = $input['owner_name'];
                $Restaurant->email      = $input['email'];
                $Restaurant->address    = $input['address'];
                $Restaurant->state      = $input['state'];
                $Restaurant->zip        = $input['zip'];
                $Restaurant->city       = $input['city'];
                $Restaurant->status     = 1;
                $Restaurant->confirmed  = true;
                $Restaurant->password   = bcrypt($input['password']);
                $Restaurant->save();
                $Restaurant->saveRole($Restaurant->id, 2);
                Session::flash('message', 'Restaurant Saved Successfully !');
                return redirect('admin/restaurant_add');
            } else {
                Session::flash('error', 'Duplicate Email Address Found!');
                return redirect('admin/restaurant_add')->withInput();
            }
        }

        return view('backend.restaurant_add');
    }

    public function save_waitlistdata(Request $request)
    {

        if ($request->isMethod('post')) {
            $user     = Auth::User();
            $User     = User::find($user->id);
            $input    = $request->all();
            $Waitlist = new Waitlist();

            /* Add Waitlist user into master customer table Start */

            /* Add Waitlist user into master customer table end */

            if (!isset($input['edit_id'])) {
                $Waitlist->current_status = 'Initial Checkin';
                $total_waitlist           = $Waitlist->count_total_waitlist_for_day($User->reset_time, $User->id);

                $customer    = new Customer;
                $customer_id = $customer->AddCustomer($input, $this->user_id);

                if ($total_waitlist == 0) {
                    $Waitlist->rank = 1;
                } else {
                    $total_waitlist = $Waitlist->count_total_waitlist_for_day($User->reset_time, $User->id);
                    $Waitlist->rank = $total_waitlist + 1;
                }
                $Waitlist->customer_id = $customer_id;
            } else {
                $Waitlist = Waitlist::find($input['edit_id']);

                $duplicatePhone = DB::table('fontana_restaurant_wait_list')
                    ->where('customer_id', '!=', $Waitlist->customer_id)
                    ->where('phone', '=', $input['phoneNum'])
                    ->count();
                if ($duplicatePhone != "0") {
                    echo "duplicate_phone";exit;
                }
                $duplicatePhone = DB::table('fontana_restaurant_customer')
                    ->where('id', '!=', $Waitlist->customer_id)
                    ->where('phone', '=', $input['phoneNum'])
                    ->count();
                if ($duplicatePhone != "0") {
                    echo "duplicate_phone";exit;
                }
                $customer        = Customer::find($Waitlist->customer_id);
                $customer->phone = $input['phoneNum'];
                $customer->email = $input['UserEmail'];
                $customer->name  = $input['firstName'];
                $customer->save();
            }

            $Waitlist->size_of_party = $input['size_of_party'];
            $Waitlist->name          = $input['firstName'];

            $Waitlist->restaurant_id = $user->id;

            if (isset($input['seating_preference'])) {
                $Waitlist->seating_preference = implode(",", $input['seating_preference']);
            } else {
                $Waitlist->seating_preference = "No Preference";
            }

            $Waitlist->main_person_name = $input['firstName'];

            if (isset($input['Opt_in'])) {
                $Waitlist->opt_in = 1;
            } else {
                $Waitlist->opt_in = 0;
            }

            $Waitlist->phone                = $input['phoneNum'];
            $Waitlist->email                = $input['UserEmail'];
            $Waitlist->comment              = $input['user_comments'];
            $Waitlist->approximate_waittime = 0;

            if (isset($input['vipClient'])) {
                $Waitlist->vip_status = 1;
            } else {
                $Waitlist->vip_status = 0;
            }

            $Waitlist->save();

            $total_waitlist                   = $Waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time                  = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $total_wait_time_converted        = $this->convertToHoursMins($total_wait_time, '%02d:%02d');
            $data['total_waitlist']           = $total_waitlist;
            $data['total_wait_time']          = $total_wait_time;
            $data['total_wait_time_converte'] = $total_wait_time_converted;
            $waitlist                         = new Waitlist;
            $conditionDate                    = $waitlist->fetchLessGrDate($user->reset_time);
            $data['notifyFirst']              = DB::table('fontana_restaurant_wait_list')
                ->whereBetween("created_at", array($conditionDate['lessDate'], $conditionDate['grdate']))
                ->where('restaurant_id', '=', $user->id)
                ->where('current_status', '!=', 'seated')
                ->where('current_status', '!=', 'Deleted')
                ->where('check_in_status', '=', '0')
                ->whereNull('deleted_at')
                ->orderBy('id', 'asc')
                ->get();

            $dataSms['sms_recipients'] = array($input['phoneNum']);
            $dataSms['smscontent']     = DashboardService::getFeatureWaitlistNotificationData('initial_checkin');
            Event::fire(new SmsSend($dataSms));
            echo json_encode($data);exit;
        }
    }

    public function addNewformData(Request $request)
    {

        $input       = $request->all();
        $customer    = new Customer;
        $customer_id = $customer->AddCustomer($input, $this->user_id);
    }

    public function waitlist_seated(Request $request)
    {

        if ($request->isMethod('post')) {

            $input                    = $request->all();
            $Waitlist                 = Waitlist::find($input['id']);
            $Waitlist->current_status = 'seated';
            $CustomerPhone            = $Waitlist->phone;
            $Waitlist->save();
            $user                    = Auth::User();
            $User                    = User::find($user->id);
            $waitlist                = new Waitlist;
            $total_waitlist          = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time         = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $data['total_waitlist']  = $total_waitlist;
            $data['total_wait_time'] = $total_wait_time;
            $data['sms_recipients']  = array($CustomerPhone);
            $data['smscontent']      = DashboardService::getFeatureWaitlistNotificationData('seated');
            Event::fire(new SmsSend($data));
            echo json_encode($data);exit;
        }

    }

    public function waitlist_incomplete(Request $request)
    {

        if ($request->isMethod('post')) {
            $input                    = $request->all();
            $Waitlist                 = Waitlist::find($input['id']);
            $Waitlist->current_status = 'incomplete';
            $Waitlist->save();
            $user                    = Auth::User();
            $User                    = User::find($user->id);
            $waitlist                = new Waitlist;
            $total_waitlist          = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time         = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $data['total_waitlist']  = $total_waitlist;
            $data['total_wait_time'] = $total_wait_time;
            echo json_encode($data);exit;
        }
    }

    public function waitlist_note_present(Request $request)
    {

        if ($request->isMethod('post')) {
            $input                         = $request->all();
            $Waitlist                      = Waitlist::find($input['id']);
            $CustomerPhone                 = $Waitlist->phone;
            $Waitlist->current_status      = 'notpresent';
            $Waitlist->not_present_counter = $Waitlist->not_present_counter + 1;
            $Waitlist->save();
            $user                    = Auth::User();
            $User                    = User::find($user->id);
            $waitlist                = new Waitlist;
            $total_waitlist          = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time         = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $data['total_waitlist']  = $total_waitlist;
            $data['total_wait_time'] = $total_wait_time;
            $data['sms_recipients']  = array($CustomerPhone);
            $data['smscontent']      = DashboardService::getFeatureWaitlistNotificationData('not_present');
            Event::fire(new SmsSend($data));
            echo json_encode($data);exit;
        }
    }

    public function waitlist_delete(Request $request)
    {

        if ($request->isMethod('post')) {
            $input = $request->all();
            Waitlist::withTrashed()->where('id', $request->get('id'))->delete();
            $user                    = Auth::User();
            $User                    = User::find($user->id);
            $waitlist                = new Waitlist;
            $total_waitlist          = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time         = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $data['total_waitlist']  = $total_waitlist;
            $data['total_wait_time'] = $total_wait_time;
            echo json_encode($data);exit;
        }
    }

    public function waitlist_change_time(Request $request)
    {

        if ($request->isMethod('post')) {
            $input                      = $request->all();
            $user                       = Auth::User();
            $User                       = User::find($user->id);
            $User->estimation_wait_time = $input['value'];
            $User->save();
            $waitlist                = new Waitlist;
            $total_waitlist          = $waitlist->count_total_waitlist($User->reset_time, $User->id);
            $total_wait_time         = ($total_waitlist + 1) * ($User->estimation_wait_time);
            $data['total_waitlist']  = $total_waitlist;
            $data['total_wait_time'] = $total_wait_time;
            echo json_encode($data);exit;
        }
    }

    public function checkin()
    {

        $user                      = Auth::User();
        $User                      = User::find($user->id);
        $waitlist                  = new Waitlist;
        $total_waitlist            = $waitlist->count_total_waitlist($User->reset_time, $User->id);
        $total_wait_time           = ($total_waitlist + 1) * ($User->estimation_wait_time);
        $total_wait_time_converted = $this->convertToHoursMins($total_wait_time, '%02d:%02d');
        $conditionDate             = $waitlist->fetchLessGrDate($user->reset_time);

        $currnet_serv = DB::table('fontana_restaurant_wait_list')
            ->whereBetween("created_at", array($conditionDate['lessDate'], $conditionDate['grdate']))
            ->where('restaurant_id', '=', $user->id)
            ->where('current_status', '=', 'seated')
            ->orderBy('updated_at', '=', 'desc')
            ->limit(1)
            ->first();

        if (!empty($currnet_serv)) {
            $currnet_serv = $currnet_serv->rank;
        } else {
            $currnet_serv = 0;
        }

        $TablePreference = new TablePreference();
        $table_pref      = $TablePreference->fetchTablePref($this->user_id);
        return view('backend.checkin')->with('total_waitlist', $total_waitlist)->with('total_wait_time_converted', $total_wait_time_converted)->with('currnet_serv', $currnet_serv)->with('table_pref', $table_pref);
    }

    public function convertToHoursMins($time, $format = '%02d:%02d')
    {
        if ($time < 1) {
            return;
        }
        $hours   = floor($time / 60);
        $minutes = ($time % 60);
        return sprintf($format, $hours, $minutes);
    }

    public function getguestdata(Request $request)
    {

        $input  = $request->all();
        $result = DB::table('fontana_restaurant_wait_list')
            ->where('id', '=', $input['id'])
            ->first();
        return json_encode($result);
    }

    public function current_serv_time()
    {

        $user              = Auth::User();
        $current_serv_time = DB::table('fontana_restaurant_wait_list')
            ->whereDate("created_at", '=', date('Y-m-d'))
            ->where('restaurant_id', '=', $user->id)
            ->where('current_status', '=', 'seated')
            ->orderBy('updated_at', '=', 'desc')
            ->limit(1)
            ->first()->id;
        $data['current_serv_time'] = $current_serv_time;
        echo json_encode($data);exit;
    }

    public function settings()
    {

        $tzlist          = (new CommonHelpers)->generate_timezone_list();
        $TablePreference = new TablePreference();
        $table_pref      = $TablePreference->fetchTablePref($this->user_id);

        $time_in_day = array();
        foreach (range(0, 23) as $fullhour) {
            $parthour = $fullhour > 12 ? $fullhour - 12 : $fullhour;
            $sufix    = $fullhour > 11 ? " pm" : " am";

            $time_in_day["$fullhour:00"] = $parthour . ":00" . $sufix;
            $time_in_day["$fullhour:30"] = $parthour . ":30" . $sufix;
        }
        $user                      = Auth::User();
        $time_detail['timezone']   = $user->timezone;
        $time_detail['reset_time'] = $user->reset_time;
        $smtpDetails['name']       = $user->smtp_name;
        $smtpDetails['email']      = $user->smtp_email;
        $smtpDetails['password']   = $user->smtp_password;
        return view('backend.settings')->with('table_pref', $table_pref)->with('tzlist', $tzlist)->with('time_in_day', $time_in_day)->with($time_detail)->with(compact('smtpDetails'));
    }

    public function save_table_pref_restaurant(Request $request)
    {

        $input           = $request->all();
        $user            = Auth::User();
        $TablePreference = new TablePreference();
        if ($request->isMethod('post')) {
            $TablePreference->name          = $input['name'];
            $TablePreference->restaurant_id = $user->id;
            $TablePreference->save();
            echo $TablePreference->id;exit;
        }
    }

    public function remove_table_pref_restaurant(Request $request)
    {
        $input = $request->all();
        if ($request->isMethod('post')) {
            $t = TablePreference::find($input['id']);
            $t->delete();
        }
    }

    public function timezone_update(Request $request)
    {
        $input = $request->all();
        if ($request->isMethod('post')) {
            $user           = User::find($this->user_id);
            $user->timezone = $input['timezone'];
            $user->save();
        }
    }

    public function selfCheckin()
    {
        return view('backend.self-checkin');
    }

    public function selfCheckinAjax(Request $request)
    {
        $input        = $request->all();
        $fetchdeddata = DB::table('fontana_restaurant_wait_list')->where('phone', '=', $input['phone'])->where('merge_status', '0')->orderBy('created_at', 'desc')->get();
        if (count($fetchdeddata) == 1) {

            for ($i = 0; $i < count($fetchdeddata); $i++) {
                $fetchdeddata[$i]->pointEarned = $this->totalPoint($fetchdeddata[$i]->customer_id);
                if ($fetchdeddata[$i]->pointEarned != null) {
                    $fetchdeddata[$i]->eligible_reward = $this->RewardsEligible($fetchdeddata[$i]->pointEarned);
                } else {
                    $fetchdeddata[$i]->eligible_reward = array();
                }

            }
            echo json_encode($fetchdeddata);exit;
        } else if (count($fetchdeddata) == 0) {
            echo 0;exit;
        } else {
            echo "1";exit;
        }
    }

    public function selfCheckinAddAjax(Request $request)
    {
        $input = $request->all();
        $User  = Auth::User();

        $customer                 = new Customer;
        $customeData['phoneNum']  = $input['phone'];
        $customeData['firstName'] = $input['name'];
        $customeData['UserEmail'] = $input['email'];
        $customer_id              = $customer->AddCustomer($customeData, $this->user_id);

        $customeData['id']            = $customer_id;
        $CustomerVisit                = new CustomerVisit;
        $CustomerVisit->restaurant_id = $this->user_id;
        $CustomerVisit->point_earned  = $User->checkin_point;
        $CustomerVisit->customer_id   = $customer_id;
        $CustomerVisit->type_of_point = 'checkin';
        $CustomerVisit->save();

        $waitlist                  = new waitlist;
        $waitlist->name            = $input['name'];
        $waitlist->restaurant_id   = $this->user_id;
        $waitlist->customer_id     = $customer_id;
        $waitlist->phone           = $input['phone'];
        $waitlist->email           = $input['email'];
        $waitlist->check_in_status = 1;
        $waitlist->save();
        echo json_encode($customeData);
        exit;
    }

    public function resettime_update(Request $request)
    {

        $input = $request->all();
        if ($request->isMethod('post')) {
            $user             = User::find($this->user_id);
            $user->reset_time = $input['reset_time'];
            $user->save();
        }
    }
    public function update_profile_image(Request $request)
    {

        $input = $request->all();
        $image = $request->file('file');

        $input['imagename'] = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath    = public_path('/restaurant_profile_pic');
        $image->move($destinationPath, $input['imagename']);
        $img = Image::make($destinationPath . "/" . $input['imagename']);
        $img->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($destinationPath . "/" . $input['imagename']);
        $user = User::find($this->user_id);
        if ($user->image != "") {
            if (file_exists($destinationPath . "/" . $user->image)) {
                unlink($destinationPath . "/" . $user->image);
            }

        }

        $user->image = $input['imagename'];
        $user->save();
        echo App::make('url')->to('/') . "/restaurant_profile_pic/" . $input['imagename'];
        exit;
    }

    public function profile(Request $request)
    {
        $user = Auth::User();

        if ($request->isMethod('post')) {
            $input = $request->all();
            if ($input['password'] != $input['cpassword']) {
                Session::flash('error', 'Password and Confirm password does not matched!');
                return redirect('admin/profile')->withInput();
            }
            $Restaurant = User::find($user->id);
            if (!$Restaurant->duplicateEmailEdit($input['email'], $user->id)) {

                $Restaurant->name       = $input['name'];
                $Restaurant->address    = $input['address'];
                $Restaurant->phone      = $input['address'];
                $Restaurant->owner_name = $input['owner_name'];
                $Restaurant->email      = $input['email'];

                if (trim($input['password']) != "") {
                    $Restaurant->password = bcrypt($input['password']);
                }

                $Restaurant->save();
                Session::flash('message', 'Profile Updated Successfully !');
                return redirect('admin/profile');
            } else {
                Session::flash('error', 'Duplicate Email Address Found!');
                return redirect('admin/profile')->withInput();
            }

        }
        return view('backend.profile')->with('user', $user);
    }

    public function save_reff_type_setting(Request $request)
    {
        $input = $request->all();

        if (in_array('pervisit', $input['reff_type'])) {
            $per_visit   = $input['points_per_visit'];
            $limit_hours = $input['limit_hours'];
        } else {
            $per_visit   = '';
            $limit_hours = '';
        }

        if (in_array('amount_spent', $input['reff_type'])) {
            $amount_spent_points = $input['amount_spent_points'];
            $amount_spent_money  = $input['amount_spent_amount'];
        } else {
            $amount_spent_points = '';
            $amount_spent_money  = '';
        }

        if (in_array('refferals', $input['reff_type'])) {
            $referrals = $input['reff_points'];
        } else {
            $referrals = '';
        }

        if (in_array('birthday', $input['reff_type'])) {
            $birthday = $input['birthday_point'];
        } else {
            $birthday = '';
        }

        if ($input['ref_id'] == "" || $input['ref_id'] == '1') {
            $refferal = new ManageReward;
        } else {
            $refferal = ManageReward::find($input['ref_id']);
        }

        $refferal->restaurant_id      = $this->user_id;
        $refferal->per_visit          = $per_visit;
        $refferal->limit_hours        = $limit_hours;
        $refferal->amount_spent_point = $amount_spent_points;
        $refferal->amount_spent_money = $amount_spent_money;
        $refferal->referrals          = $referrals;
        $refferal->birthday           = $birthday;
        $refferal->save();
        Session::flash('message', 'Data Updated Successfully');
        return redirect('admin/managereview')->withInput();
    }

    public function save_redeem(Request $request)
    {

        $input                 = $request->all();
        $redeem                = new Redeem;
        $redeem->restaurant_id = $this->user_id;
        $redeem->value         = $input['redeem_val'];
        $redeem->point         = $input['redeem_point'];
        $redeem->save();
        echo $redeem->id;exit;
    }

    public function save_vip_qualification(Request $request)
    {
        $input               = $request->all();
        $vip                 = new VipQualification;
        $vip->restaurant_id  = $this->user_id;
        $vip->value          = $input['value'];
        $vip->points_checkin = $input['points_checkin'];
        $vip->number         = $input['number'];
        $vip->duration       = $input['duration'];
        $vip->save();
        $vipQualification       = DB::table('fontana_restaurant_vip')->where('restaurant_id', '=', $this->user_id)->get();
        $totalVipConditionCount = count($vipQualification);
        echo view('backend.vip_condition_AJAX')->with('totalVipConditionCount', $totalVipConditionCount)->with('vipQualification', $vipQualification);
        exit;
    }

    public function delete_condition(Request $request)
    {
        $input = $request->all();
        $id    = $input['id'];
        $a     = VipQualification::find($id);
        $a->delete();
        $vipQualification       = DB::table('fontana_restaurant_vip')->where('restaurant_id', '=', $this->user_id)->get();
        $totalVipConditionCount = count($vipQualification);
        echo view('backend.vip_condition_AJAX')->with('totalVipConditionCount', $totalVipConditionCount)->with('vipQualification', $vipQualification);
        exit;
    }

    public function saveVipQualCondition(Request $request)
    {
        $input               = $request->all();
        $user                = Auth::User();
        $User                = User::find($user->id);
        $User->vip_condition = implode($input['condition']);
        $User->save();
    }

    public function users()
    {

        return view('backend.users');
    }

    public function subscription()
    {
        return view('backend.subscription');
    }

    public function forum()
    {
        return view('backend.forum');
    }

    public function analytics()
    {
        return view('backend.analytics');
    }

    public function knowledgebase()
    {
        return view('backend.knowledgebase');
    }

    public function manageReview()
    {

        //$a = DashboardService::userIsVipOrNot($userId=Null,$restaurantId=Null); //$userId,$restaurantId

        $reff_user_type_data = DB::table('fontana_manage_reward')->where('restaurant_id', '=', $this->user_id)->first();
        if (!$reff_user_type_data) {
            $reff_user_type_data = DB::table('fontana_manage_reward')->where('restaurant_id', '=', 0)->first();
        }
        $redeem_data = DB::table('fontana_restaurant_redeem')->where('restaurant_id', '=', $this->user_id)->get();

        $vipQualification = DB::table('fontana_restaurant_vip')->where('restaurant_id', '=', $this->user_id)->get();

        $default_set = DB::table('fontana_default_restaurant_set')->where('restaurant_id', '=', $this->user_id)->first();

        if (count($default_set) == 0) {
            $default_set                     = new \stdClass();
            $default_set->id                 = "";
            $default_set->visit_value        = "";
            $default_set->amt_spent_value    = "";
            $default_set->visit_duration     = "";
            $default_set->amt_spent_duration = "";
        }

        $totalVipConditionCount = count($vipQualification);

        return view('backend.manage_review')->with('reff_user_type_data', $reff_user_type_data)->with('redeem_data', $redeem_data)->with('vipQualification', $vipQualification)->with('totalVipConditionCount', $totalVipConditionCount)->with('default_set', $default_set);
    }

    public function delete_redeem(Request $request)
    {
        $input = $request->all();
        $id    = $input['id'];
        $a     = Redeem::find($id);
        $a->delete();
    }
    public function update_default_visit(Request $request)
    {
        $input = $request->all();

        $id = $input['id'];

        if ($id == "") {
            $default = new DefaultSet;
        } else {
            $default = DefaultSet::find($id);
        }

        $default->restaurant_id  = $this->user_id;
        $default->visit_value    = $input['visit_value'];
        $default->visit_duration = $input['visit_duration'];
        $default->save();
        echo $default->id;exit;
    }

    public function update_default_amt_spent(Request $request)
    {

        $input = $request->all();
        $id    = $input['id'];

        if ($id == "") {
            $default = new DefaultSet;
        } else {
            $default = DefaultSet::find($id);
        }

        $default->restaurant_id      = $this->user_id;
        $default->amt_spent_value    = $input['amt_spent_value'];
        $default->amt_spent_duration = $input['amt_spent_duration'];
        $default->save();
        echo $default->id;exit;
    }

    public function saveCheckinSettings(Request $request)
    {

        $input                        = $request->all();
        $user                         = User::find($this->user_id);
        $user->checkin_point          = $input['checkin_points'];
        $user->checkin_point_duration = $input['duration'];
        $user->save();
    }

    public function checkinConfirmAjax(Request $request)
    {

        $input = $request->all();
        $User  = Auth::User();

        $newWaitlist = Waitlist::where('phone', '=', $input['phone'])->first();
        if (count($newWaitlist) != 0) {
            $newWaitlist->email = $input['email'];
            $newWaitlist->name  = $input['name'];
            $newWaitlist->save();
        }

        $CustomerVisit                = new CustomerVisit;
        $CustomerVisit->restaurant_id = $this->user_id;
        $CustomerVisit->waitlist_id   = $input['id'];
        $CustomerVisit->point_earned  = $User->checkin_point;
        $CustomerVisit->customer_id   = $input['customer_id'];
        $CustomerVisit->type_of_point = 'checkin';
        $CustomerVisit->save();

        $customer = Customer::where('phone', '=', $input['phone'])->first();
        if (count($customer) != 0) {
            $customer->email = $input['email'];
            $customer->name  = $input['name'];
            $customer->save();
        }
        exit;
    }

    public function checkinConfirmAjaxUpdate(Request $request)
    {
        $input              = $request->all();
        $newWaitlist        = Customer::find($input['id']);
        $newWaitlist->email = $input['email'];
        $newWaitlist->name  = $input['name'];
        $newWaitlist->save();
        exit;
    }

    public function staffCheckin()
    {
        $TablePreference = new TablePreference();
        $table_pref      = $TablePreference->fetchTablePref($this->user_id);
        return view('backend.staff-checkin', compact('table_pref'));
    }

    public function staffCheckinSeachAjax(Request $request)
    {
        $input         = $request->all();
        $search_result = DB::table('fontana_restaurant_wait_list');
        $search_result->select(array('fontana_restaurant_wait_list.*', 'fontana_restaurant_customer.birthday', 'fontana_restaurant_customer.organization'));
        $search_result->Leftjoin('fontana_restaurant_customer', 'fontana_restaurant_customer.id', '=', 'fontana_restaurant_wait_list.customer_id');

        $search_result->orWhere(function ($query) use ($input) {
            if ($input['name_search'] != "") {
                $query->orWhere('fontana_restaurant_wait_list.name', '=', $input['name_search']);
            }
            if ($input['email_search'] != "") {
                $query->orWhere('fontana_restaurant_wait_list.email', '=', $input['email_search']);
            }
            if ($input['phone_search'] != "") {
                $query->orWhere('fontana_restaurant_wait_list.phone', '=', $input['phone_search']);
            }
        });

        $search_result->where('merge_status', '=', '0');

        $resutl = $search_result->get();

        if (count($resutl) == 0) {
            echo "0";
            exit;
        }

        $defaultSetValue = DB::table('fontana_default_restaurant_set')->where('restaurant_id', '=', $this->user_id)->first();

        if (count($defaultSetValue) == 0) {
            $defaultSetValue                     = new DefaultSet;
            $defaultSetValue->visit_value        = 6;
            $defaultSetValue->visit_duration     = 'All';
            $defaultSetValue->amt_spent_duration = 'Weeks';
            $defaultSetValue->amt_spent_value    = 12;
        }
        if ($defaultSetValue->visit_duration == "Days") {
            $visit_duration = "day";
        }
        if ($defaultSetValue->visit_duration == "Weeks") {
            $visit_duration = "week";
        }
        if ($defaultSetValue->visit_duration == "Months") {
            $visit_duration = "month";
        }
        if ($defaultSetValue->visit_duration == "Years" || $defaultSetValue->visit_duration == "All") {
            $visit_duration = "year";
        }

        if ($defaultSetValue->amt_spent_duration == "Days") {
            $amt_spent_duration = "day";
        }
        if ($defaultSetValue->amt_spent_duration == "Weeks") {
            $amt_spent_duration = "week";
        }
        if ($defaultSetValue->amt_spent_duration == "Months") {
            $amt_spent_duration = "month";
        }
        if ($defaultSetValue->amt_spent_duration == "Years" || $defaultSetValue->amt_spent_duration == "All") {
            $amt_spent_duration = "year";
        }

        for ($i = 0; $i < count($resutl); $i++) {

            $resutl[$i]->referral = DB::table('fontana_referral_list')->where('waitlist_id', '=', $resutl[$i]->id)->get();

            $date_array = array('DATE_SUB(NOW(), INTERVAL ' . $defaultSetValue->visit_value . ' ' . $visit_duration . ')', 'NOW()');

            $from                       = date('Y-m-d h:i:s');
            $to                         = date("Y-m-d h:i:s", strtotime(date("Y-m-d", strtotime($from)) . " -" . $defaultSetValue->visit_value . " " . $visit_duration . ""));
            $resutl[$i]->total_referral = count($resutl[$i]->referral);
            $resutl[$i]->lastSixmonth   = DB::table('fontana_restaurant_wait_list')
                ->where('customer_id', '=', $resutl[$i]->customer_id)
                ->whereBetween('created_at', array($to, $from))
                ->count();

            $toSpentAmount = date("Y-m-d h:i:s", strtotime(date("Y-m-d", strtotime($from)) . " -" . $defaultSetValue->amt_spent_value . " " . $amt_spent_duration . ""));

            $resutl[$i]->amountSpentTwelveWeek = DB::table('fontana_restaurant_wait_list')
                ->select(array('spent_amount'))
                ->where('customer_id', '=', $resutl[$i]->customer_id)
                ->whereBetween('created_at', array($toSpentAmount, $from))
                ->sum('spent_amount');

            $resutl[$i]->totalAmountSpent = DB::table('fontana_restaurant_wait_list')
                ->select(array('spent_amount'))
                ->where('phone', '=', $resutl[$i]->phone)
                ->sum('spent_amount');

            $resutl[$i]->pointEarned = $this->totalPoint($resutl[$i]->customer_id);

            if ($resutl[$i]->pointEarned != null) {
                $resutl[$i]->eligible_reward = $this->RewardsEligible($resutl[$i]->pointEarned);
            } else {
                $resutl[$i]->eligible_reward = array();
            }

        }

        echo view('backend.staff_checkin_result_AJAX')->with('resutl', $resutl)->with(compact('defaultSetValue'));
        exit;
    }

    public function totalPoint($cid)
    {

        $toatl_point = DB::table('fontana_restaurant_customer_visit')->where('customer_id', '=', $cid)->where('restaurant_id', '=', $this->user_id)->sum('point_earned');
        $toatl_spent = DB::table('fontana_redeem_history')->where('customer_id', '=', $cid)->where('restaurant_id', '=', $this->user_id)->sum('reward_point');
        return $toatl_point - $toatl_spent;
    }

    public function staffCheckinSave(Request $request)
    {

        $input          = $request->all();
        $duplicatePhone = DB::table('fontana_restaurant_wait_list')
            ->where('customer_id', '!=', $input['customer_id'])
            ->where('phone', '=', $input['phone'])
            ->count();

        if ($duplicatePhone != "0") {
            echo "duplicate_phone";exit;
        }

        $duplicatePhone = DB::table('fontana_restaurant_customer')
            ->where('id', '!=', $input['customer_id'])
            ->where('phone', '=', $input['phone'])
            ->count();

        if ($duplicatePhone != "0") {
            echo "duplicate_phone";exit;
        }

        $waitlist               = Waitlist::find($input['id']);
        
        if($input['name']!="")
            $waitlist->name         = $input['name'];

        $waitlist->phone        = $input['phone'];
        $waitlist->email        = $input['email'];
        $waitlist->organization = $input['organization'];
        $waitlist->spent_amount = $input['spent_amount'];
        $waitlist->save();

        $customer               = Customer::find($input['customer_id']);
        $customer->phone        = $input['phone'];
        $customer->email        = $input['email'];
        $customer->birthday     = $input['birthday'];
        $customer->organization = $input['organization'];
        $customer->save();

        /* find checkin point of Current Restaurant */
        $ManageReward = ManageReward::where('restaurant_id', '=', $this->user_id)->first();
        if ($ManageReward) {

            if ($ManageReward->per_visit != "") {
                $perVisit = $ManageReward->per_visit;
            } else {
                $perVisit = 0;
            }

            if ($ManageReward->amount_spent_point != "") {

                if ($ManageReward->amount_spent_money == "") {
                    $ManageReward->amount_spent_money = 0;
                }

                if ($input['spent_amount'] >= $ManageReward->amount_spent_money) {
                    $amtSpentPoint = $ManageReward->amount_spent_money;
                } else {
                    $amtSpentPoint = 0;
                }

            } else {
                $amtSpentPoint = 0;
            }

        } else {
            $perVisit      = 0;
            $amtSpentPoint = 0;
        }
        /* find checkin point of Current Restaurant End*/

        $visitorAdd = array(
            array(
                'restaurant_id' => $this->user_id,
                'waitlist_id'   => $input['id'],
                'point_earned'  => $perVisit,
                'customer_id'   => $input['customer_id'],
                'type_of_point' => 'checkin',
            ),
            array(
                'restaurant_id' => $this->user_id,
                'waitlist_id'   => $input['id'],
                'point_earned'  => $amtSpentPoint,
                'customer_id'   => $input['customer_id'],
                'type_of_point' => 'amount_spent',
            ),
        );

        Event::fire(new PointAdded($visitorAdd));
        /*$User = Auth::User();
    $CustomerVisit = new CustomerVisit;
    $CustomerVisit->restaurant_id = $this->user_id;
    $CustomerVisit->waitlist_id = $input['id'];
    $CustomerVisit->point_earned = $perVisit;
    $CustomerVisit->customer_id = $input['customer_id'];
    $CustomerVisit->type_of_point = 'checkin';
    $CustomerVisit->save();
     */
    }

    public function addPointsfromStaff(Request $request)
    {

        $input                        = $request->all();
        $User                         = Auth::User();
        $CustomerVisit                = new CustomerVisit;
        $CustomerVisit->restaurant_id = $this->user_id;
        $CustomerVisit->waitlist_id   = $input['id'];
        $CustomerVisit->point_earned  = $input['point'];
        $CustomerVisit->customer_id   = $input['cid'];
        $CustomerVisit->type_of_point = 'staff_added';
        $CustomerVisit->save();
    }

    public function staffReferral(Request $request)
    {

        $input           = $request->all();
        $referral        = new Referral;
        $referral->name  = $input['name'];
        $referral->phone = $input['phone'];
        $referral->email = $input['email'];

        if (isset($input['cid']) && $input['cid'] != "") {
            $referral->customer_id = $input['cid'];
        }

        if (isset($input['id']) && $input['id'] != "") {
            $referral->waitlist_id = $input['id'];
        }

        $referral->restaurant_id = $this->user_id;
        $referral->save();

        /* find checkin point of Current Restaurant  */
        $ManageReward  = ManageReward::where('restaurant_id', '=', $this->user_id)->first();
        $refferalPoint = 0;
        if ($ManageReward) {
            if ($ManageReward->referrals != "") {
                $refferalPoint = $ManageReward->referrals;
            }

        }
        /* find checkin point of Current Restaurant End  */
        $visitorAdd = array(
            array(
                'restaurant_id' => $this->user_id,
                'waitlist_id'   => $input['id'],
                'point_earned'  => $refferalPoint,
                'customer_id'   => $referral->customer_id,
                'type_of_point' => 'referral',
            ),
        );
        Event::fire(new PointAdded($visitorAdd));
    }

    public function viewDatabase()
    {

        //$Birthday = DashboardService::getUserBirthday(12);
        return view('backend.viewdatabase');
    }

    public function databasefetch()
    {

        $a = DB::table('fontana_restaurant_customer')
            ->select(array('fontana_restaurant_wait_list.opt_in as wopt_in', DB::raw('DATE_FORMAT(fontana_restaurant_customer.birthday,\'%b %D\') as bday'), 'fontana_restaurant_customer.*', 'fontana_restaurant_wait_list.id as wid', 'fontana_restaurant_wait_list.created_at as last_visit', 'fontana_restaurant_wait_list.spent_amount', DB::raw('COUNT(fontana_referral_list.waitlist_id) as total_referral'), DB::raw('GROUP_CONCAT(fontana_referral_list.waitlist_id) as referral_customer_id'), DB::raw('SUM(fontana_restaurant_wait_list.spent_amount) as spent_amount_total')))
            ->Leftjoin('fontana_restaurant_wait_list', 'fontana_restaurant_wait_list.customer_id', '=', 'fontana_restaurant_customer.id')
            ->Leftjoin('fontana_referral_list', 'fontana_restaurant_wait_list.id', '=', 'fontana_referral_list.waitlist_id')
            ->where('fontana_restaurant_customer.restaurant_id', '=', $this->user_id)
            ->where('fontana_restaurant_wait_list.merge_status', '=', 0)
            ->groupBy('fontana_restaurant_customer.id')
            ->groupBy('fontana_restaurant_wait_list.id');
        return Datatables::of($a)->make(true);
    }

    public function customerFetch()
    {

        $a = DB::table('fontana_restaurant_customer')
            ->select(array('fontana_restaurant_wait_list.opt_in as wopt_in', 'fontana_restaurant_customer.*', 'fontana_restaurant_wait_list.id as wid', 'fontana_restaurant_wait_list.created_at as last_visit', 'fontana_restaurant_wait_list.spent_amount', DB::raw('COUNT(fontana_referral_list.waitlist_id) as total_referral'), DB::raw('GROUP_CONCAT(fontana_referral_list.waitlist_id) as referral_customer_id'), DB::raw('SUM(fontana_restaurant_wait_list.spent_amount) as spent_amount_total')))
            ->Leftjoin('fontana_restaurant_wait_list', 'fontana_restaurant_wait_list.customer_id', '=', 'fontana_restaurant_customer.id')
            ->Leftjoin('fontana_referral_list', 'fontana_restaurant_wait_list.id', '=', 'fontana_referral_list.waitlist_id')
            ->where('fontana_restaurant_customer.restaurant_id', '=', $this->user_id)
            ->groupBy('fontana_restaurant_customer.id');
        return Datatables::of($a)->make(true);
    }

    public function exportCsvDatabaseWaitlist()
    {

        $expoertData = DB::table('fontana_restaurant_customer')
            ->select(array('fontana_restaurant_customer.*', 'fontana_restaurant_wait_list.id as wid', 'fontana_restaurant_wait_list.opt_in', 'fontana_restaurant_wait_list.birthday', 'fontana_restaurant_wait_list.created_at as last_visit', 'fontana_restaurant_wait_list.spent_amount', DB::raw('COUNT(fontana_referral_list.waitlist_id) as total_referral'), DB::raw('GROUP_CONCAT(fontana_referral_list.waitlist_id) as referral_customer_id'), DB::raw('SUM(fontana_restaurant_wait_list.spent_amount) as spent_amount_total')))
            ->Leftjoin('fontana_restaurant_wait_list', 'fontana_restaurant_wait_list.customer_id', '=', 'fontana_restaurant_customer.id')
            ->Leftjoin('fontana_referral_list', 'fontana_restaurant_wait_list.id', '=', 'fontana_referral_list.waitlist_id')
            ->where('fontana_restaurant_customer.restaurant_id', '=', $this->user_id)
            ->where('fontana_restaurant_wait_list.merge_status', '=', 0)
            ->groupBy('fontana_restaurant_customer.id')
            ->get();

        $output = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=data.csv');
        fputcsv($output, array('Id', 'Name', 'SMS', 'Email', 'Opt-in', 'Birthday', 'Last visit', '$ Spent', 'Total $ spent', '#Referrals', 'Referrals_Cust ID'));

        $a = 0;
        foreach ($expoertData as $d) {
            $row[$a]['id']                   = $d->id;
            $row[$a]['name']                 = $d->name;
            $row[$a]['phone']                = $d->phone;
            $row[$a]['email']                = $d->email;
            $row[$a]['opt_in']               = $d->opt_in;
            $row[$a]['birthday']             = $d->birthday;
            $row[$a]['created_at']           = $d->created_at;
            $row[$a]['spent_amount']         = $d->spent_amount;
            $row[$a]['total_referral']       = $d->total_referral;
            $row[$a]['referral_customer_id'] = $d->referral_customer_id;
            fputcsv($output, $row[$a]);
            $a++;
        }
        exit;
    }

    public function LastVisitCounter(Request $request)
    {

        $input  = $request->all();
        $result = DB::table('fontana_restaurant_wait_list');
        $result->where('customer_id', '=', $input['cid']);
        if ($input['val'] != "all") {
            $from     = date('Y-m-d h:i:s');
            $duration = $input['txt_val'] . " " . $input['val'];
            $to       = date("Y-m-d h:i:s", strtotime(date("Y-m-d", strtotime($from)) . " -" . $duration));
            $result->whereBetween('created_at', array($to, $from));
        }
        echo $result->count();exit;
    }

    public function AmountSpentTotal(Request $request)
    {
        $input  = $request->all();
        $result = DB::table('fontana_restaurant_wait_list');
        $result->where('customer_id', '=', $input['cid']);
        if ($input['val'] != "all") {
            $from     = date('Y-m-d h:i:s');
            $duration = $input['txt_val'] . " " . $input['val'];
            $to       = date("Y-m-d h:i:s", strtotime(date("Y-m-d", strtotime($from)) . " -" . $duration));
            $result->whereBetween('created_at', array($to, $from));
        }
        echo $result->sum('spent_amount');exit;
    }

    public function UpdateMergeRecordStatus(Request $request)
    {
        $input = $request->all();        
        unset($input['id']['0']);
        $input['id'] = array_values($input['id']);        
        //$MergeUpdate = Waitlist::whereIn("id", $input['id'])->update(array('merge_status' => 1));
        $MergeUpdate = DB::statement('Update fontana_restaurant_wait_list
                                set merge_status = "1" where id in ('.implode(",",$input["id"]).')'
                        );        
        
    }

    public function importDatabaseCsv(Request $request)
    {
        $input           = $request->all();
        $destinationPath = 'import'; // upload path
        $extension       = Input::file('import')->getClientOriginalExtension(); // getting image extension
        $fileName        = rand(11111, 99999) . '.' . $extension;
        Input::file('import')->move($destinationPath, $fileName);
        if (($file = fopen(public_path() . "/import/" . $fileName, "r")) !== false) {
            $i        = 0;
            $customer = new Customer;
            while (($data = fgetcsv($file, 1000, ",")) !== false) {
                if ($i != 0) {
                    $input['phoneNum']  = $data['1'];
                    $input['UserEmail'] = $data['2'];
                    $input['firstName'] = $data['0'];
                    $customer_id        = $customer->AddCustomer($input, $this->user_id);
                }
                $i++;
            }
        }
        unlink(public_path() . "/import/" . $fileName);
        exit;
    }

    public function editDatabaseDetail(Request $request)
    {
        $input              = $request->all();
        $result['user']     = DB::table('fontana_restaurant_customer')->where('id', '=', $input['id'])->first();
        $result['waitlist'] = DB::table('fontana_restaurant_wait_list')->where('id', '=', $input['wid'])->first();
        echo json_encode($result);exit;
    }

    public function saveDatabaseDetail(Request $request)
    {
        $input       = $request->all();
        $customer    = new Customer;
        $customer_id = $customer->UpdateCustomer($input);
        exit;
    }

    public function RewardsEligible($totalPoints)
    {
        $findRewardEligibibility = DB::table('fontana_restaurant_redeem');
        $findRewardEligibibility->where('restaurant_id', '=', $this->user_id);
        $findRewardEligibibility->where('point', '<=', $totalPoints);
        return $findRewardEligibibilityResult = $findRewardEligibibility->get();
    }

    public function redeemPointStaff(Request $request)
    {
        $input                        = $request->all();
        $RedeemHistory                = new RedeemHistory;
        $RedeemHistory->reward_id     = $input['rid'];
        $RedeemHistory->reward_point  = $input['rpoint'];
        $RedeemHistory->waitlist_id   = $input['wid'];
        $RedeemHistory->restaurant_id = $this->user_id;
        $RedeemHistory->customer_id   = $input['cid'];
        $RedeemHistory->save();
    }

    public function marketing($id = null)
    {
        $savedMessage     = DB::table('fontana_marketing_mail')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 1)->get();
        $scheduledMessage = DB::table('fontana_marketing_mail')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 2)->get();

        $savedSms     = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 1)->get();
        $scheduledSms = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 2)->get();

        if (isset($id)) {
            $CurrentMessage           = DB::table('fontana_marketing_mail')->where('id', '=', $id)->first();
            $CurrentMessage->email_id = explode(",", $CurrentMessage->email_id);
        }
        return view('backend.marketing', compact('savedMessage'))->with(compact('CurrentMessage'))->with(compact('id'))->with(compact('scheduledMessage'))->with(compact('savedSms'))->with(compact('scheduledSms'));
    }

    public function networkMarketing($id = null)
    {

        $editResult = array();
        if ($id != null) {
            $editResult = DB::table('fontana_network_marketing')->where('id', '=', $id)->first();
        }
        $savedMarketingMail    = DB::table('fontana_network_marketing')->where('status', '=', 1)->where('type', '=', 0)->get();
        $aprrovedMarketingMail = DB::table('fontana_network_marketing')->where('status', '=', 3)->where('type', '=', 0)->get();

        $savedMarketingSms    = DB::table('fontana_network_marketing')->where('status', '=', 1)->where('type', '=', 1)->get();
        $aprrovedMarketingSms = DB::table('fontana_network_marketing')->where('status', '=', 3)->where('type', '=', 1)->get();

        $scheduledMail = DB::table('fontana_marketing_mail')->where('restaurant_id', '=', $this->user_id)->where('type', '=', '1')->where('status', '=', 2)->get();
        $scheduledSMS  = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('type', '=', '1')->where('status', '=', 2)->get();

        return view('backend.network_marketing')->with(compact('savedMarketingMail'))->with(compact('aprrovedMarketingMail'))->with(compact('editResult'))->with('id', $id)->with(compact('savedMarketingSms'))->with(compact('aprrovedMarketingSms'))->with(compact('scheduledMail'))->with(compact('scheduledSMS'));
    }

    public function removeDraft(Request $request)
    {
        $input = $request->all();
        $draft = MarketingMail::find($input['id'])->delete();
    }

    public function removeSavedMarketingMail(Request $request)
    {
        $input = $request->all();
        $draft = NetworkMarketing::find($input['id'])->delete();
    }

    public function removeScheduledMarketingMail(Request $request)
    {
        $input = $request->all();
        $draft = MarketingMail::find($input['id'])->delete();
    }

    public function removeDraftSms(Request $request)
    {
        $input = $request->all();
        $draft = MarketingSms::find($input['id'])->delete();
    }

    public function fetchCustomerEmail(Request $request)
    {
        $input        = $request->all();
        $CustomerData = DB::table('fontana_restaurant_customer')->select(array('id', 'email', 'phone'))->whereIn('id', $input['id'])->get();
        echo json_encode($CustomerData);exit;
    }

    public function fetchSmsData(Request $request)
    {
        $input                  = $request->all();
        $id                     = $input['id'];
        $smsData                = DB::table('fontana_marketing_sms')->where('id', '=', $id)->first();
        $smsData->schedule_time = date("h:i A", strtotime($smsData->schedule_time));
        echo json_encode($smsData);exit;
    }

    public function marketingMail(Request $request, $id = null)
    {
        $input = $request->all();
        if (empty($input['selected_recipients'])) {
            Session::flash('flash_success', "Please select at least One Recipients");
            return redirect('admin/marketing');
        }

        $subject = $input['subject'];
        if (isset($input['submit_button']) && $input['submit_button'] == "send") {

            $content    = $input['emailbrod'];
            $recipients = $input['selected_recipients'];
            $UserSMTP   = Auth::User();

            try {
                if ($UserSMTP->smtp_email != "" && $UserSMTP->smtp_name != "" && $UserSMTP->smtp_password != "") {

                    \Config::set('mail.username', $UserSMTP->smtp_email);
                    \Config::set('mail.host', 'smtp.gmail.com');
                    \Config::set('mail.password', $UserSMTP->smtp_password);

                    (new \Illuminate\Mail\MailServiceProvider(app()))->register();
                }
                \Mail::send('backend.marketingmail', ['content' => $content], function ($message) use ($recipients, $UserSMTP, $subject) {

                    $message->from($UserSMTP->email, $UserSMTP->name);
                    $message->to($recipients);
                    $message->subject($subject);

                });
            } catch (\Exception $e) {

                Session::flash('flash_success', $e->getMessage());
                return redirect('admin/marketing');
            }

            $status  = 0;
            $message = "Mail Sent Successfully";
        } else if (isset($input['submit_button']) && $input['submit_button'] == "save_draft") {
            $status  = 1;
            $message = "Mail saved as Draft Successfully";
        } else {
            $status  = 2;
            $message = "Mail Scheduled Successfully";
        }

        if ($id != null) {
            $MarketingMail = MarketingMail::find($id);
        } else {
            $MarketingMail = new MarketingMail;
        }
        $MarketingMail->restaurant_id = $this->user_id;
        $MarketingMail->email_id      = implode(",", $input['selected_recipients']);
        $MarketingMail->content       = $input['emailbrod'];
        $MarketingMail->subject       = $subject;
        $MarketingMail->status        = $status;
        if ($input['buttone_schedule'] == 'schedule') {
            $MarketingMail->schedule_date = date("Y-m-d", strtotime($input['schedule_date']));
            $MarketingMail->schedule_time = date("H:i:s", strtotime($input['schedule_time']));
        }
        $MarketingMail->save();

        Session::flash('flash_success', $message);
        return redirect('admin/marketing');
    }

    public function smsStore(Request $request)
    {

        $input = $request->all();
        if ($input['sms_edit_id'] == 0) {
            $SmsMarketing = new MarketingSms;
        } else {
            $SmsMarketing = MarketingSms::find($input['sms_edit_id']);
        }
        $SmsMarketing->restaurant_id = $this->user_id;
        $SmsMarketing->sms_number    = implode(",", $input['sms_recipients']);
        $SmsMarketing->content       = $input['smscontent'];
        if ($input['actionPerformed'] == 'send') {
            $SmsMarketing->status = 0;
        } else if ($input['actionPerformed'] == 'draft') {
            $SmsMarketing->status = 1;
        } else if ($input['actionPerformed'] == 'schedule') {
            $SmsMarketing->status        = 2;
            $SmsMarketing->schedule_date = date("Y-m-d", strtotime($input['datePickerHiddenSms']));
            $SmsMarketing->schedule_time = date("H:i:s", strtotime($input['timePickerHiddenSms']));
        }
        $SmsMarketing->save();

        if ($input['actionPerformed'] == 'send') {
            Event::fire(new SmsSend($input));
        }

        $savedSms     = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 1)->get();
        $scheduledSms = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('status', '=', 2)->get();
        echo view('backend.smsMarketingAjax')->with(compact('savedSms'))->with(compact('scheduledSms'));exit;

    }

    public function saveSmtpCredential(Request $request)
    {
        $input                      = $request->all();
        $SmptDetails                = User::find($this->user_id);
        $SmptDetails->smtp_name     = $input['name'];
        $SmptDetails->smtp_email    = $input['email'];
        $SmptDetails->smtp_password = $input['password'];
        $SmptDetails->save();
        Session::flash('success', "SMTP details saved successfully!");
        return redirect('admin/settings');
    }

    public function SaveMarketingMail(Request $request)
    {

        // status 0 For aproval, status 1 for save draft status 3 = approvedByAdmin
        $input   = $request->all();
        $subject = $input['subject'];
        if (isset($input['editIdMail'])) {
            $networkMarketing = NetworkMarketing::find($input['editIdMail']);
        } else {
            $networkMarketing = new NetworkMarketing;
        }

        $networkMarketing->restaurant_id = $this->user_id;
        $networkMarketing->content       = addslashes($input['emailbrod']);

        if ($input['button'] == 'aprroval') {
            $status = 0;
        }

        if ($input['button'] == 'draft') {
            $status = 1;
        }

        $networkMarketing->status  = $status;
        $networkMarketing->subject = $subject;
        $networkMarketing->save();
        $data['status']        = $status;
        $savedMarketingMessage = DB::table('fontana_network_marketing')->where('status', '=', 1)->where('type', '=', 0)->get();
        echo view('backend.savedMarketingMailAjax')->with(compact('savedMarketingMessage'));exit;
    }

    public function SaveMarketingSMS(Request $request)
    {
        // status 0 For aproval, status 1 for save draft status 3 = approvedByAdmin
        $input = $request->all();

        if ($input['sms_edit_id'] != 0) {
            $networkMarketing = NetworkMarketing::find($input['sms_edit_id']);
        } else {
            $networkMarketing = new NetworkMarketing;
        }
        $networkMarketing->restaurant_id = $this->user_id;
        $networkMarketing->content       = $input['sms_content'];
        if ($input['button'] == 'aprroval') {
            $status = 0;
        }

        if ($input['button'] == 'draft') {
            $status = 1;
        }

        $networkMarketing->status = $status;
        $networkMarketing->type   = 1;
        $networkMarketing->save();
        $data['status']    = $status;
        $savedMarketingSms = DB::table('fontana_network_marketing')->where('status', '=', 1)->where('type', '=', 1)->get();
        echo view('backend.savedMarketingSmsAjax')->with(compact('savedMarketingSms'));exit;
    }

    public function marketingApproval()
    {
        return view('backend.marketing_approval');
    }
    public function marketingApprovalData()
    {

        $data = DB::table('fontana_network_marketing')
            ->select(array('fontana_network_marketing.content', 'users.name', 'fontana_network_marketing.created_at', 'fontana_network_marketing.id', 'fontana_network_marketing.type'))
            ->Leftjoin('users', 'fontana_network_marketing.restaurant_id', '=', 'users.id')
            ->where('fontana_network_marketing.status', '=', 0);
        return Datatables::of($data)->make(true);

    }
    public function approveMarketingMail($id)
    {
        // status 3 = approvedByAdmin
        $networkMarketing         = NetworkMarketing::find($id);
        $networkMarketing->status = 3;
        $networkMarketing->save();
        Session::flash('flash_success', "Mail Approved Successfully");
        return redirect('admin/marketingApproval');
    }

    public function ApprovalSendMarketingMail(Request $request)
    {
        $input                    = $request->all();
        $id                       = $input['id'];
        $networkMarketing         = NetworkMarketing::find($id);
        $networkMarketing->status = 0;
        $networkMarketing->save();
    }

    public function fetchMarketingSMSData(Request $request)
    {
        $input = $request->all();
        $id    = $input['id'];
        $data  = DB::table('fontana_network_marketing')->where('id', '=', $id)->first();
        echo json_encode($data);exit;
    }

    public function fetchMarketingMailCost(Request $request)
    {
        $input                     = $request->all();
        $data['customerTotal']     = $input['totalCust'];
        $data['customerCost']      = $data['customerTotal'] * 0.10;
        $data['birthdayTotalCost'] = 0;
        $data['vipTotalCost']      = 0;
        $data['birthdayTotalMail'] = 0;
        $data['vipCount']          = 0;
        $data['total_Customer']    = 0;

        if ($input['birthday_days_status'] == 0 && $input['vips'] == 0) {
            $data['Customer_data']  = DashboardService::getTotalCusotmer($data['customerTotal']);
            $data['total_Customer'] = count(DashboardService::getTotalCusotmer($data['customerTotal']));
        }

        if ($input['birthday_days_status'] == 1 && $input['vips'] == 0) {
            $data['total_Customer'] = count(DashboardService::getUserBirthday($input['birthday_days']));
            $data['Customer_data']  = DashboardService::getUserBirthday($input['birthday_days']);
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }
            $data['birthdayTotalMail'] = $data['total_Customer'];
            $data['birthdayTotalCost'] = $data['birthdayTotalMail'] * 0.05;
        }

        if ($input['birthday_days_status'] == 1 && $input['vips'] == 1) {
            $data['total_Customer'] = count(DashboardService::getUserBirthdayVip($input['birthday_days']));
            $data['Customer_data']  = DashboardService::getUserBirthdayVip($input['birthday_days']);
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }

            $data['birthdayTotalMail'] = $data['total_Customer'];
            $data['vipCount']          = $data['total_Customer'];

            $data['birthdayTotalCost'] = $data['total_Customer'] * 0.05;
            $data['vipTotalCost']      = $data['total_Customer'] * 0.15;
        }

        if ($input['birthday_days_status'] == 0 && $input['vips'] == 1) {
            $data['total_Customer'] = count(DashboardService::getUserVip());
            $data['Customer_data']  = DashboardService::getUserVip();
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }

            $data['vipCount']     = $data['total_Customer'];
            $data['vipTotalCost'] = $data['total_Customer'] * 0.15;
        }
        $data['customerTotalCost'] = $data['total_Customer'] * 0.10;
        $data['subTotal']          = $data['birthdayTotalCost'] + $data['vipTotalCost'] + $data['customerTotalCost'];

        if ($data['total_Customer'] != 0) {
            for ($i = 0; $i < $data['total_Customer']; $i++) {
                $data['email'][$i] = $data['Customer_data'][$i]->email;
            }

            if (count($data['email']) != 0) {
                $data['email'] = implode(",", $data['email']);
            } else {
                $data['email'] = "";
            }

        } else {
            $data['email'] = "";
        }

        echo view("backend.marketing_mail_cost_Ajax")->with(compact('data'));exit;
    }
    
    public function fetchMarketingSmsCost(Request $request)
    {
        $input                     = $request->all();
        $data['customerTotal']     = $input['totalCust'];
        $data['customerCost']      = $data['customerTotal'] * 0.10;
        $data['birthdayTotalCost'] = 0;
        $data['vipTotalCost']      = 0;
        $data['birthdayTotalMail'] = 0;
        $data['vipCount']          = 0;
        $data['total_Customer']    = 0;

        if ($input['birthday_days_status'] == 1 && $input['vips'] == 0) {

            $data['total_Customer'] = count(DashboardService::getUserBirthday($input['birthday_days']));
            $data['Customer_data']  = DashboardService::getUserBirthday($input['birthday_days']);
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }
            $data['birthdayTotalMail'] = $data['total_Customer'];
            $data['birthdayTotalCost'] = $data['birthdayTotalMail'] * 0.05;
        }

        if ($input['birthday_days_status'] == 1 && $input['vips'] == 1) {

            $data['total_Customer'] = count(DashboardService::getUserBirthdayVip($input['birthday_days']));
            $data['Customer_data']  = DashboardService::getUserBirthdayVip($input['birthday_days']);
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }

            $data['birthdayTotalMail'] = $data['total_Customer'];
            $data['vipCount']          = $data['total_Customer'];

            $data['birthdayTotalCost'] = $data['total_Customer'] * 0.05;
            $data['vipTotalCost']      = $data['total_Customer'] * 0.15;
        }

        if ($input['birthday_days_status'] == 0 && $input['vips'] == 1) {

            $data['total_Customer'] = count(DashboardService::getUserVip());
            $data['Customer_data']  = DashboardService::getUserVip();
            if ($data['customerTotal'] != "") {
                if ($data['total_Customer'] > $data['customerTotal']) {
                    $data['total_Customer'] = $data['customerTotal'];
                }
            }

            $data['vipCount']     = $data['total_Customer'];
            $data['vipTotalCost'] = $data['total_Customer'] * 0.15;
        }

        $data['customerTotalCost'] = $data['total_Customer'] * 0.10;
        $data['subTotal']          = $data['birthdayTotalCost'] + $data['vipTotalCost'] + $data['customerTotalCost'];

        if ($data['total_Customer'] != 0) {

            for ($i = 0; $i < $data['total_Customer']; $i++) {
                $data['phone'][$i] = $data['Customer_data'][$i]->phone;
            }

            if (count($data['phone']) != 0) {
                $data['phone'] = implode(",", $data['phone']);
            } else {
                $data['phone'] = "";
            }

        } else {
            $data['phone'] = "";
        }

        echo view("backend.marketing_sms_cost_Ajax")->with(compact('data'));exit;
    }

    public function sendNetworkMarketingMail(Request $request)
    {

        try {

            $input          = $request->all();
            $getMailContent = DB::table('fontana_network_marketing')->select(array('content', 'subject'))->where('id', '=', $input['approveMail']['0'])->first();
            $UserSMTP       = Auth::User();
            $rec            = $input['emails_hidden'];
            $subject        = $getMailContent->subject;
            if ($UserSMTP->smtp_email != "" && $UserSMTP->smtp_name != "" && $UserSMTP->smtp_password != "") {

                \Config::set('mail.username', $UserSMTP->smtp_email);
                \Config::set('mail.host', 'smtp.gmail.com');
                \Config::set('mail.password', $UserSMTP->smtp_password);

                (new \Illuminate\Mail\MailServiceProvider(app()))->register();
            }
            \Mail::send('backend.netWorkMarketingmail', ['content' => stripslashes($getMailContent->content)], function ($message) use ($rec, $UserSMTP, $subject) {
                $message->from($UserSMTP->email, $UserSMTP->name);
                $message->to(explode(",", $rec));
                if ($subject == "") {
                    $message->subject($UserSMTP->name);
                } else {
                    $message->subject($subject);
                }

            });

        } catch (\Exception $e) {
            echo $e->getMessage();exit;
        }
        echo "Mail Sent Successfully";exit;
    }

    public function fetchEmailContent(Request $request)
    {
        $input       = $request->all();
        $id          = $input['id'];
        $mailMessage = DB::table('fontana_network_marketing')->select(array('content', 'subject'))->where('id', '=', $id)->first();
        echo json_encode($mailMessage);exit;
    }

    public function sendNetworkMarketingSms(Request $request)
    {
        try {
            $input                     = $request->all();
            $getMailContent            = DB::table('fontana_network_marketing')->select(array('content'))->where('id', '=', $input['approveSms']['0'])->first();
            $UserSMTP                  = Auth::User();
            $smsData['sms_recipients'] = explode(",", $input['phone_hidden_sms']);
            $smsData['smscontent']     = stripslashes($getMailContent->content);
            Event::fire(new SmsSend($smsData));
        } catch (\Exception $e) {
            echo $e->getMessage();exit;
        }
        echo "SMS Sent Successfully";exit;
    }

    public function sendScheduleMarketingMail(Request $request)
    {
        try {
            $input = $request->all();

            if ($input['schedule_mail_editId'] == "" || $input['schedule_mail_editId'] == "0") {

                $getMailContent = DB::table('fontana_network_marketing')->select(array('content', 'subject'))->where('id', '=', $input['approveMail']['0'])->first();

                $MarketingMail                = new MarketingMail;
                $MarketingMail->restaurant_id = $this->user_id;
                $MarketingMail->email_id      = $input['emails_hidden'];
                $MarketingMail->content       = stripslashes($getMailContent->content);
                $MarketingMail->subject       = stripslashes($getMailContent->subject);
                $MarketingMail->schedule_date = date("Y-m-d", strtotime($input['schedule_mail_date']));
                $MarketingMail->schedule_time = date("H:i:s", strtotime($input['schedule_mail_time']));
                $MarketingMail->status        = 2;
                $MarketingMail->type          = 1;
                $MarketingMail->save();
                $msg = "Mail Schedule Successfully!";

            } else {

                $MarketingMail                = MarketingMail::find($input['schedule_mail_editId']);
                $MarketingMail->schedule_date = date("Y-m-d", strtotime($input['schedule_mail_date']));
                $MarketingMail->schedule_time = date("H:i:s", strtotime($input['schedule_mail_time']));
                $MarketingMail->save();
                $msg = "Mail Re-Schedule Successfully!";

            }
        } catch (\Exception $e) {
            $output['msg'] = $e->getMessage();
        }
        $output['msg']         = $msg;
        $scheduledMail         = DB::table('fontana_marketing_mail')->where('restaurant_id', '=', $this->user_id)->where('type', '=', '1')->where('status', '=', 2)->get();
        $view                  = view("backend.schedule_mail_Ajax")->with(compact('scheduledMail'));
        $output['sheduleMail'] = (string) $view;
        echo json_encode($output);exit;
    }

    public function sendScheduleMarketingSMS(Request $request)
    {
        try {
            $input = $request->all();

            if ($input['schedule_sms_editId'] == "" || $input['schedule_sms_editId'] == 0) {
                $getMailContent              = DB::table('fontana_network_marketing')->select(array('content'))->where('id', '=', $input['approveSms']['0'])->first();
                $SmsMarketing                = new MarketingSms;
                $SmsMarketing->restaurant_id = $this->user_id;
                $SmsMarketing->sms_number    = $input['phone_hidden_sms'];
                $SmsMarketing->content       = stripslashes($getMailContent->content);
                $SmsMarketing->status        = 2;
                $SmsMarketing->type          = 1;
                $SmsMarketing->schedule_date = date("Y-m-d", strtotime($input['schedule_sms_date']));
                $SmsMarketing->schedule_time = date("H:i:s", strtotime($input['schedule_sms_time']));
                $SmsMarketing->save();
                $msg = "SMS Schedule Successfully!";
            } else {

                $SmsMarketing                = MarketingSms::find($input['schedule_sms_editId']);
                $SmsMarketing->schedule_date = date("Y-m-d", strtotime($input['schedule_sms_date']));
                $SmsMarketing->schedule_time = date("H:i:s", strtotime($input['schedule_sms_time']));
                $SmsMarketing->save();
                $msg = "SMS Re-Schedule Successfully!";
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        $output['msg']        = $msg;
        $scheduledSMS         = DB::table('fontana_marketing_sms')->where('restaurant_id', '=', $this->user_id)->where('type', '=', '1')->where('status', '=', 2)->get();
        $view                 = view("backend.schedule_sms_Ajax")->with(compact('scheduledSMS'));
        $output['sheduleSms'] = (string) $view;
        echo json_encode($output);exit;
    }

    public function notifyFirst(Request $request)
    {
        $input                  = $request->all();
        $data['sms_recipients'] = array($input['sms']);
        $data['smscontent']     = "Your Turn is Near.";
        Event::fire(new SmsSend($data));
        exit;
    }

    public function featureWaitlist(Request $request)
    {

        if ($request->isMethod('post')) {

            $featureWaitlistAlreadyExistOrNot = DB::table('fontana_feature_waitlist')->where('restaurant_id', '=', $this->user_id)->first();
            if (count($featureWaitlistAlreadyExistOrNot) != 0) {

                $featureWaitlist = FeatureWaitlist::find($featureWaitlistAlreadyExistOrNot->id);
            } else {
                $featureWaitlist = new FeatureWaitlist;
            }
            $input = $request->all();

            $featureWaitlist->restaurant_id = $this->user_id;
            if (!isset($input['mail'])) {

                $featureWaitlist->initial_checkin = $input['initial_checkin'];
                $featureWaitlist->table_ready     = $input['table_ready'];
                $featureWaitlist->not_present     = $input['not_present'];
                $featureWaitlist->seated          = $input['seated'];
                $featureWaitlist->save();
                Session::flash('flash_success', 'Data Updated Successfully !');
                return redirect('admin/featureWaitlist');
            } else {
                $featureWaitlist->mail = $input['mail'];
                $featureWaitlist->save();
                Session::flash('flash_success', 'Data Updated Successfully !');
                return redirect('admin/featureWaitlist');
            }
        }
        $featureWaitlistAlreadyExistOrNot = DB::table('fontana_feature_waitlist')->where('restaurant_id', '=', $this->user_id)->first();
        if (count($featureWaitlistAlreadyExistOrNot) == 0) {
            $featureWaitlistAlreadyExistOrNot                  = new \stdclass();
            $featureWaitlistAlreadyExistOrNot->initial_checkin = '';
            $featureWaitlistAlreadyExistOrNot->table_ready     = '';
            $featureWaitlistAlreadyExistOrNot->not_present     = '';
            $featureWaitlistAlreadyExistOrNot->seated          = '';
            $featureWaitlistAlreadyExistOrNot->mail            = '';

        }
        return view('backend.featureWaitlist')->with(compact('featureWaitlistAlreadyExistOrNot'));
    }

//===========@@ Azim Work @@ ==================
    public function affiliateMarketing()
    {
        $getAllData = DashboardService::affiliateMarketing();
        return view('backend.affiliateMarketing', compact('getAllData'));
    }

    public function saveCommissionRate(Request $request)
    {
        $result = DashboardService::saveCommissionRate($request->all());
        if ($result === true) {
            return response()->json(['success' => true, 'status' => "Default Commission for all is Successfully Updated."]);
        } elseif ($result == false) {
            return response()->json(['error' => true, 'status' => "No Change Occour."]);
        } else {
            return response()->json(['error' => true, 'status' => $result]);
        }
    }

    public function loadAddAffiliateModal()
    {
        return view('backend.loadAddAffiliateModal');
    }

    public function saveAffiliate(AffiliateRequest $request)
    {
        $result = DashboardService::saveAffiliate($request->all());
        if ($result === true) {
            Session::flash('flash_success', 'Affiliate Marketer Saved Successfully !');
        } elseif ($result == false) {
            Session::flash('flash_warning', 'Duplicate Email Address Found!');
        } else {
            Session::flash('flash_error', $result);
        }
        return redirect('admin/affiliateMarketing');
    }

    public function saveEditAffiliate(Request $request)
    {
        $editAffiliate = DashboardService::saveEditAffiliate($request->all());
        if ($editAffiliate === true) {
            return response()->json(['success' => true, 'status' => "Update Affiliate Successfull."]);
        } elseif ($editAffiliate == false) {
            return response()->json(['error' => true, 'status' => "No Change Occour."]);
        } else {
            return response()->json(['error' => true, 'status' => $editAffiliate]);
        }
    }

    public function deactiveAffiliate($id = null)
    {
        $deactiveAffiliate = DashboardService::deactiveAffiliate($id);
        if ($deactiveAffiliate === true) {
            return response()->json(['success' => true, 'status' => "Affiliate Deactivated Successfull."]);
        } else {
            return response()->json(['error' => true, 'status' => $deactiveAffiliate]);
        }
    }

    public function activeAffiliate($id = null)
    {
        $activeAffiliate = DashboardService::activeAffiliate($id);
        if ($activeAffiliate === true) {
            return response()->json(['success' => true, 'status' => "Affiliate Active Successfull."]);
        } else {
            return response()->json(['error' => true, 'status' => $activeAffiliate]);
        }
    }

    public function getMonthWisePayment()
    {
        //return $request->all();
        return $result = DashboardService::getMonthWisePayment();
    }

    //============@@Azim Work @@============

    //-------------start use for financial code by neyamul

    public function financial()
    {
        $subscription_packages = FinancialsServices::subscriptionPackages();
        $service_type          = FinancialsServices::serviceTypes();
        $additional_services   = FinancialsServices::additional_services();
        $triggers              = FinancialsServices::triggers();
        $restaurant            = FinancialsServices::restaurant();
        return view('backend.financial', compact('subscription_packages', 'service_type', 'additional_services', 'triggers', 'restaurant'));
    }

    public function financialModal()
    {
        $service_type = FinancialsServices::serviceTypes();
        return view('backend.financialAddModal', compact('service_type'));
    }
    /**
     * @param  Request
     * @return [type]
     */
    public function saveFinancial(Request $request)
    {
        $saveFinancials = FinancialsServices::saveFinancials($request);
        if ($saveFinancials == true) {
            Session::flash('flash_success', 'Data Add Successfull');
        } else {
            Session::flash('flash_warning', $saveFinancials);
        }
        return redirect('admin/financials');
    }

    public function addAdditionalServicesModal()
    {

        $triggers = FinancialsServices::triggers();
        return view('backend.addAdditionalServiceModal', compact('triggers'));
    }

    public function saveAdditionalService(Request $request)
    {
        $saveFinancials = FinancialsServices::saveAdditionalServices($request);
        if ($saveFinancials == true) {
            Session::flash('flash_success', 'Data Add Successfull');
        } else {
            Session::flash('flash_warning', $saveFinancials);
        }
        return redirect('admin/financials');
    }

    public function saveEditRevenue(Request $request)
    {
        $saveRevenues = FinancialsServices::saveRevenues($request);
        if ($saveRevenues === true) {
            return response()->json(['success' => true, 'status' => "Brand Active Successfull."]);
        } else {
            return response()->json(['error' => true, 'status' => $saveRevenues]);
        }
    }
    /**
     *
     *  Function for inactive revenue.
     */
    public function inactiveRevanues(Request $request)
    {

        $inactiveRevanue = FinancialsServices::inactiveRevanue($request);
        if ($inactiveRevanue === true) {
            return response()->json([
                'success' => true,
                'status'  => "Brand Active Successfull.",
            ]);
        } else {
            return response()->json([
                'error'  => true,
                'status' => $inactiveRevanue,
            ]);
        }
    }
    //-----------------end code by neyamul

}
