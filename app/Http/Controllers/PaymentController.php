<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Extract;

class PaymentController extends Controller
{
    public function income(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'access_token' => 'required|string'
        ]);

        $date = date('Y-m-d', strtotime('-1 days', strtotime(date('Y-m-d'))));

        $day = date('N', strtotime($date));

        if ($request->access_token != env('PAYMENT_TOKEN') || $day == 6 || $day == 7 || $date > date('Y-m-d')) {

            return response()->json([
                'success' => false,
                'message' => 'Solicitação não autorizada!'
            ]);

        }

        $isPaid = Extract::where('type', INCOME)
            ->whereDate('created_at', $date)
            ->count();

        if ($isPaid) {

            return response()->json([
                'success' => false,
                'message' => 'Os Pagamentos já foram realizados!'
            ]);

        }

        if ($day == 4 || $day == 5)
            $reference = date('Y-m-d', strtotime('-4 days', strtotime($date)));

        else
            $reference = date('Y-m-d', strtotime('-6 days', strtotime($date)));

        $users = User::where('status', ACTIVE)->get();

        foreach ($users as $user) {

            $plan = DB::table('transactions as t')
                ->select('p.id', 'p.price', 'gain_limit', 'p.is_half_income', 'p.is_income_plan')
                ->leftJoin('plans as p', 'p.id', 't.id_plan')
                ->where('t.id_user', $user->id)
                ->where('t.type', PAYMENT)
                ->where('t.status', PAID)
                ->whereDate('t.confirmed_at', '<=', $reference)
                ->orderBy('t.confirmed_at', 'desc')
                ->first();

            if ($plan !== null && $plan->is_income_plan == ACTIVE) {

                $plan->gain_limit += $user->add_plan_limit;

                if ($user->category == CATEGORY_A)
                    $percent = 0.72;

                elseif ($user->category == CATEGORY_B)
                    $percent = 0.72;

                if ($plan->is_half_income == ACTIVE)
                    $percent /= 2;

                $value = percentage_to_value($plan->price, $percent);

                $plan_gain = $user->getPlanGain();

                $limit = $plan->gain_limit += $user->add_plan_limit;

                $isExpired = ($limit - $plan_gain) <= $value;

                if ($isExpired && $user->id != 15 && $user->id != 52 && $user->id != 10) {

                    $value = $limit - $plan_gain;

                    $user->status = EXPIRED;

                }

                $user->income += $value;

                $user->save();

                $extract = new Extract([
                    'id_user' => $user->id,
                    'id_plan' => $plan->id,
                    'type' => INCOME,
                    'percent' => $percent,
                    'value' => $value,
                    'created_at' => "$date 23:59:00"
                ]);

                $extract->save();

            }

        }

        return response()->json([
            'success' => true,
            'message' => 'Pagamentos dos rendimentos realizado com sucesso!'
        ]);
    }

    public function bonus(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'access_token' => 'required|string'
        ]);

        if ($request->access_token != env('PAYMENT_TOKEN')) {

            return response()->json([
                'success' => false,
                'message' => 'Solicitação não autorizada!'
            ]);

        }

        $date = date('Y-m-d', strtotime('-1 days', strtotime(date('Y-m-d'))));

        $isPaid = Extract::where('type', BONUS)
            ->whereDate('created_at', $date)
            ->count();

        if ($isPaid) {

            return response()->json([
                'success' => false,
                'message' => 'Os Pagamentos já foram realizados!'
            ]);

        }

        $users = User::where('status', ACTIVE)->get();

        foreach ($users as $user) {

            $plan = DB::table('transactions as t')
                ->select('p.id', 'p.percent', 'p.payment_limit', 'p.gain_limit', 'p.points')
                ->leftJoin('plans as p', 'p.id', 't.id_plan')
                ->where('t.id_user', $user->id)
                ->where('t.type', PAYMENT)
                ->where('t.status', PAID)
                ->orderBy('t.confirmed_at', 'desc')
                ->first();

            if ($plan) {

                $plan->payment_limit += $user->add_daily_limit;

                $plan->gain_limit += $user->add_plan_limit;

                if ($user->isQualified() && $user->status == ACTIVE) {

                    $left_points = $user->getLeftPoints();

                    $right_points = $user->getRightPoints();

                    if ($left_points > $right_points) {

						$value = percentage_to_value($right_points, $plan->percent);

						$user->difference +=  $right_points;

						$points = $right_points;

						$pay_side = 'R';
                    }

                    elseif ($left_points < $right_points) {

						$value = percentage_to_value($left_points, $plan->percent);

						$user->difference += $left_points;

						$points = $left_points;

						$pay_side = 'L';
                    }

                    else {

						$value = percentage_to_value($right_points, $plan->percent);

						$user->difference += $right_points;

						$points = $right_points;

						$pay_side = 'R';
                    }

                    if ($value > $plan->payment_limit) {

                        $value = $plan->payment_limit;

                    }

                    $plan_gain = $user->getPlanGain();

                    $isExpired = ($plan->gain_limit - $plan_gain) <= $value;

                    if ($isExpired && $user->id != 15 && $user->id != 52 && $user->id != 10) {

                        $value = $plan->gain_limit - $plan_gain;

                        $user->status = EXPIRED;

                    }

                    $user->bonus += $value;

                    $user->left_points = $left_points + $user->difference;

                    $user->right_points = $right_points + $user->difference;

                    $user->save();

                    if ($value != 0.00) {

                        $extract = new Extract([
                            'id_user' => $user->id,
                            'id_plan' => $plan->id,
                            'type' => PAYMENT,
                            'points' => $points,
                            'value' => $value,
                            'side' => $pay_side,
                            'created_at' => "$date 23:59:00"
                        ]);

                        $extract->save();

                    }

                }

            }

        }

        return response()->json([
            'success' => true,
            'message' => 'Pagamentos dos bônus realizado com sucesso!'
        ]);

    }

    public function teste()
    {
        set_time_limit(0);

        $user = User::where('id', 105)->first();

        $this->total = 0;

        $this->clients = [];

        $plan = $user->getPlan();

        if ($plan->amount_paid - $plan->plan_gain > 0) {

            $valor = format_money($plan->amount_paid - $plan->plan_gain);

            $this->clients[] = "$user->username: $ $valor";

            $this->total += ($plan->amount_paid - $plan->plan_gain);

        }

        $this->go($user->left_userid);

        $this->go($user->right_userid);

        return response()->json([
            'success' => true,
            'clients' => $this->clients,
            'total' => $this->total
        ]);
    }

    public function go($userid)
    {
        if ($userid == null) return;

        $user = User::where('id', $userid)->first();

        $plan = $user->getPlan();

        if ($plan != null) {

            if ($plan->amount_paid - $plan->plan_gain > 0) {

                $valor = format_money($plan->amount_paid - $plan->plan_gain);

                $this->clients[] = "$user->username: $ $valor";

                $this->total += ($plan->amount_paid - $plan->plan_gain);

            }

        }

        $this->go($user->left_userid);

        $this->go($user->right_userid);
    }

}
