<?php

namespace App\Http\Controllers\Adm;

use App\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function administrators()
    {
        $admins = Admin::get();

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    public function logs()
    {
        $logs = DB::table('logs as l')
            ->select('l.id', 'a.name', 'l.type', 'l.created_at')
            ->leftJoin('admins as a', 'a.id', 'l.id_adm')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function incomeToday()
    {
        $income = System::getIncomePercent();

        return response()->json([
            'success' => true,
            'data' => $income
        ]);
    }

    public function withdrawInfo()
    {
        $status = System::getWithdrawStatus();

        $fee = System::getWithdrawFee();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'fee' => $fee
            ]
        ]);
    }
}
