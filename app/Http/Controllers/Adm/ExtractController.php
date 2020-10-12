<?php

namespace App\Http\Controllers\Adm;

use App\Extract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\DB;

class ExtractController extends Controller
{
    public function lastBonusPayment()
    {
        $date = Extract::where('type', BONUS)->max('created_at');

        $extracts = Extract::select(DB::raw('count(*) as count, sum(value) as value, sum(points) as points'))
            ->where('type', BONUS)
            ->where('created_at', $date)
            ->first();

        $extracts->created_at = $date;

        return response()->json([
            'success' => true,
            'data' => $extracts
        ]);
    }

    public function lastIncomePayment()
    {
        $date = Extract::where('type', INCOME)->max('created_at');

        $system = System::first();

        $extracts = Extract::select(DB::raw('count(*) as count, sum(value) as value'))
            ->where('type', INCOME)
            ->where('created_at', $date)
            ->first();

        $extracts->percent = $system->income_percent;

        $extracts->created_at = $date;

        return response()->json([
            'success' => true,
            'data' => $extracts
        ]);
    }
}
