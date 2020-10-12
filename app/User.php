<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;
use App\System;
use App\Plan;
use App\Transaction;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    private $users = [];

    private $all_report = [];

    private $left_report = [];

    private $right_report = [];

    private $points;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_manager', 'name', 'username', 'email', 'birth', 'phone', 'password', 'side'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'binary_key' => 'L',
        'bonus' => 0,
        'income' => 0,
        'left_points' => 0,
        'right_points' => 0,
        'add_left_points' => 0,
        'add_right_points' => 0,
        'add_plan_gain' => 0,
        'add_plan_limit' => 0,
        'difference' => 0,
        'google_auth_status' => 0,
        'email_status' => 0,
        'status' => 0
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'google_auth_secret', 'code'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function createToken()
    {
        $code = sprintf("%06d", mt_rand(1, 999999999));

        $code = substr($code, strlen($this->id));

        $code .= $this->id;

        $this->token = md5($code);

        $this->save();
    }

    public function generateCode()
    {
        $invalid = [
            100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000,
            111111, 222222, 333333, 444444, 555555, 666666, 777777, 888888, 999999,
            123456, 234567, 345678, 456789, 567891, 678912, 789123, 891234, 912345,
        ];

        while(true) {

            $this->code = rand(100000, 999999);

            if (array_search($this->code, $invalid) === false) break;

        }

        $this->save();

    }

    public function isQualified()
    {
        $plan_min = System::getMinQualificationPlan();

        $left = DB::table('users as u')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->where('u.id_manager', $this->id)
            ->where('u.id_plan', '>=', $plan_min)
            ->where('u.status', ACTIVE)
            ->where('u.side', LEFT)
            ->where('p.type', OPENED)
            ->count();

        $right = DB::table('users as u')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->where('u.id_manager', $this->id)
            ->where('u.id_plan', '>=', $plan_min)
            ->where('u.status', ACTIVE)
            ->where('u.side', RIGHT)
            ->where('p.type', OPENED)
            ->count();

        if ($left && $right)
            return true;

        return false;
    }

    public function getManager()
    {
        return $this->select('name', 'username')
            ->where('id', $this->id_manager)
            ->first();
    }

    public function getPlan()
    {
        $transaction = Transaction::where('id_user', $this->id)
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->orderBy('confirmed_at', 'desc')
            ->first();

        if ($transaction == null) return null;

        $plan = Plan::find($this->id_plan);

        if ($plan == null) return null;

        $plan->hired_at = $transaction->created_at;

        $plan->paid_at = $transaction->confirmed_at;

        $plan->amount_paid = $transaction->value;

        $plan->gain_limit += $this->add_plan_limit;

        $plan->payment_limit += $this->add_daily_limit;

        $plan->plan_gain = $this->getPlanGain();

        $plan->gain_percent = value_to_percentage($plan->plan_gain, $plan->gain_limit);

        $plan->is_qualified = $this->isQualified();

        return $plan;
    }

    public function getIncomePlan()
    {
        $day = date('N');

        if ($day == 4 || $day == 5) {
            $confirmed_at = date('Y-m-d', strtotime('-4 days'));
        } else {
            $confirmed_at = date('Y-m-d', strtotime('-6 days'));
        }

        $transaction = Transaction::select('id_plan')
            ->whereDate('confirmed_at', '<=', $confirmed_at)
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->where('id_user', $this->id)
            ->orderBy('confirmed_at', 'desc')
            ->first();

        if ($transaction != null)
            return Plan::find($transaction->id_plan);

        return null;
    }

    public function getPlanGain()
    {
        $confirmed_at = Transaction::where('id_user', $this->id)
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->max('confirmed_at');

        if ($confirmed_at == null) return 0;

        $value = Extract::where('id_user', $this->id)
            ->where('created_at', '>=', $confirmed_at)
            ->sum('value');

        $value += $this->add_plan_gain;

        return $value;
    }

    public function getPlanBonus()
    {
        $confirmed_at = Transaction::where('id_user', $this->id)
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->max('confirmed_at');

        if ($confirmed_at == null) return 0;

        $value = Extract::where('id_user', $this->id)
            ->where('created_at', '>=', $confirmed_at)
            ->where('type', '<>', INCOME)
            ->sum('value');

        return format_money($value);
    }

    public function getPlanIncome()
    {
        $confirmed_at = Transaction::where('id_user', $this->id)
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->max('confirmed_at');

        if ($confirmed_at == null) return 0;

        $value = Extract::where('id_user', $this->id)
            ->where('created_at', '>=', $confirmed_at)
            ->where('type', INCOME)
            ->sum('value');

        return format_money($value);
    }

    public function getTotalBonus()
    {
        $value = Extract::where('id_user', $this->id)
            ->where('type', '<>', INCOME)
            ->sum('value');

        return format_money($value);
    }

    public function getTotalIncome()
    {
        $value = Extract::where('id_user', $this->id)
            ->where('type', INCOME)
            ->sum('value');

        return format_money($value);
    }

    public function getDocuments()
    {
        return [
            'document_type' => $this->document_type,
            'document_front' => $this->document_front,
            'document_back' => $this->document_back,
            'document_cpf' => $this->document_cpf,
            'document_selfie' => $this->document_selfie,
            'document_address' => $this->document_address,
            'document_status' => $this->document_status
        ];
    }

    public function getRootInfo()
    {
        $info['left_points'] = $this->getLeftPoints();

        $info['right_points'] = $this->getRightPoints();

        $info['left_count'] = $this->countUsersLeft();

        $info['right_count'] = $this->countUsersRight();

        return $info;
    }

    public function countNewUsersLeft()
    {
        $this->count = 0;

        $this->date = Extract::where('type', BONUS)
            ->where('id_user', $this->id)
            ->max('created_at');

        $this->loadCountNewUsers($this->left_userid);

        return $this->count;
    }

    public function countNewUsersRight()
    {
        $this->count = 0;

        $this->date = Extract::where('type', BONUS)
            ->where('id_user', $this->id)
            ->max('created_at');

        $this->loadCountNewUsers($this->right_userid);

        return $this->count;
    }

    private function loadCountNewUsers($userid)
    {
        if ($userid == null || $userid == 1120)
            return;

        $user = $this->find($userid);

        if ($user->created_at > $this->date)
            $this->count++;

        $this->loadCountNewUsers($user->left_userid);

        $this->loadCountNewUsers($user->right_userid);
    }

    public function countUsersLeft()
    {
        $this->count = 0;

        $this->loadCountUsers($this->left_userid);

        return $this->count;
    }

    public function countUsersRight()
    {
        $this->count = 0;

        $this->loadCountUsers($this->right_userid);

        return $this->count;
    }

    private function loadCountUsers($userid)
    {
        if ($userid == null || $userid == 1120)
            return;

        $user = $this->find($userid);

        $this->count++;

        $this->loadCountUsers($user->left_userid);

        $this->loadCountUsers($user->right_userid);
    }

    public function getLeftQualifierPoints()
    {
        $plan_min = System::getMinQualificationPlan();

        $users = $this->where('id_manager', $this->id)
            ->where('id_plan', '>=', $plan_min)
            ->where('status', ACTIVE)
            ->where('side', LEFT)
            ->get();

        $qualifier = null;

        foreach ($users as $user) {

            $plan = $user->getPlan();

            if ($plan->type == OPENED && ($qualifier === null || $plan->paid_at < $qualifier['plan']->paid_at)) {

                $qualifier = [
                    'user' => $user,
                    'plan' => $plan
                ];
            }
        }

        if ($qualifier !== null)
            return $qualifier['plan']->points;

        return $qualifier;
    }

    public function getRightQualifierPoints()
    {
        $plan_min = System::getMinQualificationPlan();

        $users = $this->where('id_manager', $this->id)
            ->where('id_plan', '>=', $plan_min)
            ->where('status', ACTIVE)
            ->where('side', RIGHT)
            ->get();

        $qualifier = null;

        foreach ($users as $user) {

            $plan = $user->getPlan();

            if ($plan->type == OPENED && ($qualifier === null || $plan->paid_at < $qualifier['plan']->paid_at)) {

                $qualifier = [
                    'user' => $user,
                    'plan' => $plan
                ];
            }
        }

        if ($qualifier !== null)
            return $qualifier['plan']->points;

        return $qualifier;
    }

    public function getLeftPoints()
    {
        $this->points = 0;

        $this->loadPoints($this->left_userid);

        $qualifier_points = $this->getLeftQualifierPoints();

        return $this->points + $this->add_left_points - $this->difference - $qualifier_points;
    }

    public function getRightPoints()
    {
        $this->points = 0;

        $this->loadPoints($this->right_userid);

        $qualifier_points = $this->getRightQualifierPoints();

        return $this->points + $this->add_right_points - $this->difference - $qualifier_points;
    }

    private function loadPoints($userid)
    {
        if ($userid == null || $userid == 1120)
            return;

        $user = DB::table('users as u')
            ->select('u.left_userid', 'u.right_userid', 'p.points')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->where('u.id', $userid)
            ->first();

        $this->points += $user->points;

        $this->loadPoints($user->left_userid);

        $this->loadPoints($user->right_userid);
    }

    public function verifyClient($id)
    {
        $this->client = $id;

        $this->isClient = false;

        $this->loadVerifyClient($this->left_userid);

        if ($this->isClient) return true;

        $this->loadVerifyClient($this->right_userid);

        return $this->isClient;
    }

    private function loadVerifyClient($userid)
    {
        if ($userid == null || $userid == 1120)
            return;

        if ($userid == $this->client) {

            $this->isClient = true;

            return;
        }

        $user = DB::table('users')
            ->select('left_userid', 'right_userid')
            ->where('id', $userid)
            ->first();

        $this->loadVerifyClient($user->left_userid);

        if ($this->isClient) return;

        $this->loadVerifyClient($user->right_userid);
    }

    public function getBinary()
    {
        $root_id = $this->id;

        $this->loadBinary($root_id);

        return $this->users;
    }

    private function loadBinary($userid)
    {
        $this->level++;

        if ($this->level > 4 || $userid == 1120) return;

        if ($userid == null) {

            if ($this->level == 4) {

                $this->users[] = null;

            }

            else {

                for ($i = 0; $i < (pow(2, 5 - $this->level) - 1); $i++) {

                    $this->users[] = null;

                }

            }

            return;
        }

        $user = $this->find($userid);

        $plan = Plan::find($user->id_plan);

        $this->users[] = [
            'user' => $user->username,
            'plan' => $plan->name,
            'img' => $plan->img
        ];

        $this->loadBinary($user->left_userid);

        $this->level--;

        $this->loadBinary($user->right_userid);

        $this->level--;
    }

    public function getReport()
    {
        $plans = Plan::all();

        foreach ($plans as $plan) {

            $this->all_report[$plan->id]['id'] = $this->left_report[$plan->id]['id'] = $this->right_report[$plan->id]['id'] = $plan->id;

            $this->all_report[$plan->id]['plan'] = $this->left_report[$plan->id]['plan'] = $this->right_report[$plan->id]['plan'] = $plan->name;

            $this->all_report[$plan->id]['img'] = $this->left_report[$plan->id]['img'] = $this->right_report[$plan->id]['img'] = $plan->img;

            $this->all_report[$plan->id]['count'] = $this->left_report[$plan->id]['count'] = $this->right_report[$plan->id]['count'] = 0;

            $this->all_report[$plan->id]['points'] = $this->left_report[$plan->id]['points'] = $this->right_report[$plan->id]['points'] = 0;
        }

        $this->loadReport($this->left_userid, LEFT);

        $this->loadReport($this->right_userid, RIGHT);

        $customs = Plan::where('type', CUSTOM)->get();

        foreach ($customs as $custom) {

            $plan = Plan::where('type', OPENED)
                ->where('name', $custom->name)
                ->first();

            $this->all_report[$plan->id]['count'] += $this->all_report[$custom->id]['count'];

            $this->all_report[$plan->id]['points'] += $this->all_report[$custom->id]['points'];

            $this->left_report[$plan->id]['count'] += $this->left_report[$custom->id]['count'];

            $this->left_report[$plan->id]['points'] += $this->left_report[$custom->id]['points'];

            $this->right_report[$plan->id]['count'] += $this->right_report[$custom->id]['count'];

            $this->right_report[$plan->id]['points'] += $this->right_report[$custom->id]['points'];

            unset($this->all_report[$custom->id]);

            unset($this->left_report[$custom->id]);

            unset($this->right_report[$custom->id]);
        }

        $data = [];

        foreach ($this->all_report as $all) {

            $data['all'][] = $all;
        }

        foreach ($this->left_report as $left) {

            $data['left'][] = $left;
        }

        foreach ($this->right_report as $right) {

            $data['right'][] = $right;
        }

        return $data;
    }

    private function loadReport($userid, $side)
    {
        if ($userid == null || $userid == 1120)
            return;

        $user = $this->find($userid);

        $plan = Plan::find($user->id_plan);

        $this->all_report[$plan->id]['count']++;

        $this->all_report[$plan->id]['points'] += $plan->points;

        if ($side == LEFT) {

            $this->left_report[$plan->id]['count']++;

            $this->left_report[$plan->id]['points'] += $plan->points;
        }
        else {

            $this->right_report[$plan->id]['count']++;

            $this->right_report[$plan->id]['points'] += $plan->points;
        }

        $this->loadReport($user->left_userid, $side);

        $this->loadReport($user->right_userid, $side);
    }

    public function getReportByPlan($plan)
    {
        $this->plan = Plan::find($plan);

        $this->custom = Plan::where('type', CUSTOM)
            ->where('name', $this->plan->name)
            ->first();

        $this->loadReportByPlan($this->left_userid, LEFT);

        $this->loadReportByPlan($this->right_userid, RIGHT);

        return $this->users;
    }

    private function loadReportByPlan($userid, $side)
    {
        if ($userid == null || $userid == 1120)
            return;

        $user = $this->find($userid);

        if ($user->id_plan == $this->plan->id || ($this->custom != null && $user->id_plan == $this->custom->id)) {

            $manager = $user->getManager();

            $this->users[] = [
                'name' => $user->name,
                'username' => $user->username,
                'manager' => $manager->username,
                'side' => $side
            ];
        }

        $this->loadReportByPlan($user->left_userid, $side);

        $this->loadReportByPlan($user->right_userid, $side);
    }

    public function setRootUserId()
    {
        $this->loadSpill($this->id_manager);

        $this->save();
    }

    private function loadSpill($userid)
    {
        $user = $this->find($userid);

        if ($this->side == LEFT) {

            if ($user->left_userid == NULL) {

                $this->root_userid = $user->id;

                $user->left_userid = $this->id;

                $user->save();

            }

            else {

                $this->loadSpill($user->left_userid);

            }

        }

        elseif ($this->side == RIGHT) {

            if ($user->right_userid == NULL) {

                $this->root_userid = $user->id;

                $user->right_userid = $this->id;

                $user->save();

            }

            else {

                $this->loadSpill($user->right_userid);

            }

        }

    }

}
