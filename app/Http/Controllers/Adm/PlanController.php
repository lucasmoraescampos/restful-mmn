<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Plan;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function plans()
    {
        $plans = Plan::all();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    public function ratioActivePlans()
    {
        $ratio = DB::table('plans as p')
            ->select(DB::raw('p.id, p.name as plan, count(u.id_plan) as total'))
            ->leftJoin('users as u', 'u.id_plan', 'p.id')
            ->groupBy('p.name', 'p.id')
            ->orderBy('p.id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ratio
        ]);
    }
}
