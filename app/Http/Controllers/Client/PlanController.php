<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\Career;
use App\User;
use App\Plan;

class PlanController extends Controller
{
    public function allPlans()
    {
        $plans =  Plan::getAll();

        return response()->json([
            'success' => false,
            'data' => $plans
        ]);
    }

    public function availablePlans()
    {
        $user = Auth::user();

        $consumption = 0;

        if ($user->id_plan != null) {

            $plan = $user->getPlan();

            $plan_gain = $user->getPlanGain();

            $consumption = value_to_percentage($plan_gain, $plan->gain_limit);

            $plans = Plan::getFrom($user->id_plan);

        } else {

            $plans = Plan::getAll();

        }

        return response()->json([
            'success' => false,
            'data' => [
                'consumption' => $consumption,
                'plans' => $plans
            ]
        ]);
    }

    public function hirePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required'
        ]);

        $user = Auth::user();

        $plan = Plan::find($request->plan);

        if ($plan != null && $plan->type == OPENED) {

            if ($plan->id < $user->id_plan) {

                return response()->json([
                    'success' => false,
                    'message' => 'Impossível regredir seu plano!'
                ]);

            }

            if ($plan->id != $user->id_plan && $user->status == ACTIVE) {

                $plan->price -= Plan::find($user->id_plan)->price;

            }

            Transaction::where('id_user', $user->id)
                ->where('type', PAYMENT)
                ->where('status', OPENED)
                ->delete();

            $transaction = new Transaction([
                'id_user' => $user->id,
                'id_plan' => $plan->id,
                'value' => $plan->price,
                'type' => PAYMENT
            ]);

            $transaction->save();

            $transaction->createToken();

            return response()->json([
                'success' => true,
                'message' => 'Transação gerada com sucesso!'
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado!'
            ]);
        }
    }

    public function currentPlan()
    {
        $user = Auth::user();

        $plan = null;

        if ($user->id_plan != null) {

            $plan = $user->getPlan();

            $plan->is_qualified = $user->isQualified();

        }

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    public function careerResume()
    {
        $user = Auth::user();

        $left_points = $user->getLeftPoints();

        $right_points = $user->getRightPoints();

        if ($left_points < $right_points)
            $points = $left_points + $user->difference;

        else
            $points = $right_points + $user->difference;

        $careers = Career::all();

        for ($i = 0; $i < count($careers); $i++) {

            $careers[$i]->percent = value_to_percentage($points, $careers[$i]->points);
        }

        return response()->json([
            'success' => true,
            'data' => $careers
        ]);
    }

    public function currentCareer()
    {
        $user = Auth::user();

        $left_points = $user->getLeftPoints();

        $right_points = $user->getRightPoints();

        if ($left_points < $right_points)
            $points = $left_points + $user->difference;

        else
            $points = $right_points + $user->difference;

        $career = Career::where('points', '>', $points)
            ->orderBy('points', 'asc')
            ->first();

        $career->percent = value_to_percentage($points, $career->points);

        $career->points = $points;

        return response()->json([
            'success' => true,
            'data' => $career
        ]);
    }
}
