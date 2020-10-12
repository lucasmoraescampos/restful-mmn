<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Plan;
use App\System;
use App\User;
use Illuminate\Support\Facades\DB;

class NetworkController extends Controller
{
    public function binary()
    {
        $user = Auth::user();

        $root_info = $user->getRootInfo();

        $binary = $user->getBinary();

        return response()->json([
            'success' => true,
            'data' =>  [
                'root_info' => $root_info,
                'binary' => $binary
            ]
        ]);
    }

    public function find($username)
    {
        $user = Auth::user();

        $client = User::where('username', $username)->first();

        if ($client != null) {

            if ($user->verifyClient($client->id)) {

                $users = $client->getBinary();

                return response()->json([
                    'success' => true,
                    'data' =>  $users
                ]);
            }
            else {

                return response()->json([
                    'success' => false,
                    'message' =>  'Este usuário não pertence a sua rede!'
                ]);
            }

        } else {

            return response()->json([
                'success' => false,
                'message' =>  'Usuário inexistente!'
            ]);
        }
    }

    public function directs()
    {
        $userid = Auth::id();

        $directs = User::select('name', 'username', 'side', 'phone', 'status')
            ->where('id_manager', $userid)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $directs
        ]);
    }

    public function changeBinaryKey(Request $request)
    {
        $request->validate([
            'binary_key' => 'required|string'
        ]);

        if ($request->binary_key != 'L' && $request->binary_key != 'R') {

            return response()->json([
                'success' => false,
                'message' => 'Chave deve ser L (para esquerda) ou R (para direita).'
            ]);
        }

        $user = Auth::user();

        $user->binary_key = $request->binary_key;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Chave da rede alterada com sucesso!'
        ]);
    }

    public function gainResume()
    {
        $user = Auth::user();

        $percent = System::getIncomePercent();

        $income_plan = $user->getIncomePlan();

        $income_today = 0.00;

        if ($income_plan !== null) {

            if ($income_plan->id == 7)
                $percent *= 0.5;

            $income_today += percentage_to_value($percent, $income_plan->price);
        }

        $plan = $user->getPlan();

        $left_points = $user->getLeftPoints();

        $right_points = $user->getRightPoints();

        if ($right_points < $left_points) {

            $bonus_today = percentage_to_value($plan->percent, $right_points);

        } else {

            $bonus_today = percentage_to_value($plan->percent, $left_points);
        }

        $today_gain = $income_today + $bonus_today;

        $percent_today = value_to_percentage($today_gain, $plan->payment_limit);

        $plan_gain = $user->getPlanGain();

        $percent_plan = value_to_percentage($plan_gain, $plan->gain_limit);

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'value' => $today_gain,
                    'percent' => $percent_today,
                    'limit' => $plan->payment_limit
                ],
                'plan' => [
                    'value' => $plan_gain,
                    'percent' => $percent_plan,
                    'limit' => $plan->gain_limit
                ]
            ]
        ]);
    }

    public function scoreResume()
    {
        $user = Auth::user();

        $left_points_total = $user->getLeftPoints() + $user->difference;

        $right_points_total = $user->getRightPoints() + $user->difference;

        $left_points_today = $left_points_total;

        $right_points_today = $right_points_total;

        $left_count_today = $user->countNewUsersLeft();

        $right_count_today = $user->countNewUsersRight();

        $left_count_total = $user->countUsersLeft();

        $right_count_total = $user->countUsersRight();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'left_points' => $left_points_today,
                    'left_count' => $left_count_today,
                    'right_points' => $right_points_today,
                    'right_count' => $right_count_today
                ],
                'total' => [
                    'left_points' => $left_points_total,
                    'left_count' => $left_count_total,
                    'right_points' => $right_points_total,
                    'right_count' => $right_count_total
                ]
            ]
        ]);
    }

    public function report()
    {
        $user = Auth::user();

        $report = $user->getReport();

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function reportByPlan(Plan $plan)
    {
        $user = Auth::user();

        if ($plan == null) {

            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado!'
            ]);
        }

        $report = $user->getReportByPlan($plan->id);

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => [
                    'name' => $plan->name,
                    'img' => $plan->img
                ],
                'clients' => $report
            ]
        ]);
    }
}
