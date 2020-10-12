<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class System extends Model
{
    protected $table = 'system';

    public static function getMinQualificationPlan()
    {
        return DB::table('system')->select('qualification_plan')->first()->qualification_plan;
    }

    public static function getIncomePercent()
    {
        return DB::table('system')->select('income_percent')->first()->income_percent;
    }

    public static function getWithdrawStatus()
    {
        return DB::table('system')->select('withdraw_status')->first()->withdraw_status;
    }

    public static function getWithdrawFee()
    {
        return DB::table('system')->select('withdrawal_fee')->first()->withdrawal_fee;
    }

    public static function getBitcoinPrice()
    {
        return DB::table('system')->select('bitcoin_price')->first()->bitcoin_price;
    }

    public static function getMasterPassword()
    {
        return DB::table('system')->select('master_password')->first()->master_password;
    }
}
