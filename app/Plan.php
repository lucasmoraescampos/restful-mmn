<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Plan extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'price', 'points', 'percent', 'payment_limit', 'gain_limit', 'indication', 'img', 'token', 'type', 'is_income_plan', 'is_half_income'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 0
    ];

    public static function getAll($type = 0)
    {
        if ($type)
            $plans = DB::table('plans')->get();

        else
            $plans = DB::table('plans')->where('type', OPENED)->get();


        return $plans;
    }

    public static function getFrom($id)
    {
        $plans = DB::table('plans')
            ->where('type', OPENED)
            ->where('id', '>=', $id)
            ->get();

        return $plans;
    }

    public static function getByToken($token)
    {
        return DB::table('plans')->where('token', $token)->first();
    }
}
