<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\System;
use App\Plan;
use App\User;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_user', 'id_plan', 'value', 'type', 'fee', 'bitcoin_price', 'confirmation_type', 'wallet', 'balance_type', 'bank_code', 'bank_type', 'bank_agency', 'bank_number', 'bank_holder', 'bank_cpf'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 0
    ];

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    public function createToken()
    {
        $code = sprintf("%06d", mt_rand(1, 999999999));

        $code = substr($code, strlen($this->id));

        $code .= $this->id;

        $this->token = md5($code);

        $this->save();
    }

    public function createWallet()
    {
        $curl = curl_init();

        $url = 'https://api.lcpag.com/v1/lc_pag/wallet/' . GUID . '/subcreate?onback=' . route('notificationBitcoin');

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ]);

        $res = curl_exec($curl);

        curl_close($curl);

        $config = json_decode($res);

        $this->wallet = $config->sub_address;

        $this->save();
    }

    public function createLink()
    {
        if ($this->status == WAITING) {

            $bitcoin_price = System::getBitcoinPrice();

            $this->bitcoin_price = $bitcoin_price;

            $this->save();

        }

        $this->amount = dollar_to_bitcoin($this->value, $this->bitcoin_price);

        $this->link = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=bitcoin:$this->wallet?amount=$this->amount&choe=UTF-8";
    }

    public function getUser()
    {
        return User::select('name', 'username', 'id_manager')
            ->where('id', $this->id_user)
            ->first();
    }

    public function getPlan()
    {
        return Plan::select('name', 'img')
            ->where('id', $this->id_plan)
            ->first();
    }

    public function setPaid()
    {
        $user = User::find($this->id_user);

        $plan = Plan::find($this->id_plan);

        if ($user->root_userid == NULL) {

            $user->setRootUserId();

        }

        if ($user->id_plan != $this->id_plan && $user->status == ACTIVE) { // Upgrade

            $user->add_plan_gain = $user->getPlanGain();

            $user->add_plan_limit = 0;

        }

        elseif ($user->id_plan == $this->id_plan && $user->status == ACTIVE) { // Renovation

            $user->add_plan_gain = 0;

            $user->add_plan_limit = $plan->gain_limit - $user->getPlanGain();

        }

        else {

            $user->add_plan_gain = 0;

            $user->add_plan_limit = 0;

        }

        $user->status = ACTIVE;

        $user->id_plan = $this->id_plan;

        $user->save();

        $this->status = PAID;

        $this->confirmed_at = date('Y-m-d H:i:s');

        $this->save();

        $this->payIndication();
    }

    public function payIndication()
    {
        $plan = Plan::find($this->id_plan);

        if ($plan->indication !== null) {

            $user = User::find($this->id_user);

            $manager = User::find($user->id_manager);

            $manager->bonus += $plan->indication;

            $manager->save();

            $extract = new Extract([
                'id_user' => $manager->id,
                'id_client' => $user->id,
                'id_plan' => $plan->id,
                'value' => $plan->indication,
                'type' => INDICATION,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $extract->save();

        }

    }

    public static function incomeWithdrawalDay()
    {
        return false;
    }

    public static function bonusWithdrawalDay()
    {
        return false;
    }

    public static function isChargebackAvailable()
    {
        $clients = [
            11,
            2926,
            2955,
            2956,
            2960,
            2994,
            3575,
            3583,
            3904,
            3923,
            3972,
            4086,
            3989,
            3999,
            3950,
            3992,
            4031,
            3978,
            3934,
            3991,
            4040,
            4006,
            4018,
            4099,
            4100,
            4088,
            4120,
            4108,
            4168,
            4137,
            4073,
            4140,
            4189,
            4203,
            4191,
            4177,
            4103,
            4238,
            4227,
            4186,
            4025,
            4150,
            4295,
            4268,
            4134,
            3929,
            4089,
            4215,
            4303,
            4255,
            3915,
            4210,
            4270,
            4181,
            4205,
            3925,
            4008,
            4216,
            4085,
            4327,
            4118,
            4102,
            4332,
            4304,
            4299,
            4278,
            4426,
            4261,
            4456,
            4066,
            4290,
            4002,
            4676,
            4057,
            4805,
            4221,
            4824,
            3980,
            4158,
            4143,
            4211,
            4122,
            4297,
            4288,
            4187,
            4853,
            4842,
            4839,
            4185,
            4828,
            4856,
            4145,
            4865,
            4887,
            4889,
            4836,
            3985,
            4821,
            4136,
            3955,
            2997,
            4819,
            4065,
            4818,
            4121,
            4851,
            5037,
            5052,
            5068,
            4117,
            3897,
            5106,
            5142,
            5139,
            4041,
            5158,
            4820,
            4335,
            5249,
            4855,
            4127,
            5246,
            5257,
            5268,
            5273,
            5261,
            5277,
            5279,
            5264,
            4050,
            5291,
            5295,
            5315,
            5323,
            5326,
            5327,
            5334,
            5243,
            4287,
            4883,
            3943,
            5281,
            5350,
            4027,
            5392,
            5263,
            4109,
            5319,
            5476,
            5337,
            4794,
            5324,
            5290,
            4800,
            5271,
            5364,
            5312,
            5382,
            5652,
            5713,
            5732,
            5699,
            5717,
            5723,
            5711,
            5621,
            5244,
            5786,
            5230,
            5816,
            5722,
            5779,
            5738,
            5282,
            5719,
            5803,
            5801,
            5818,
            5810,
            5767,
            5780,
            5776,
            5860,
            5863,
            5885,
            5733,
            5876,
            5706,
            5844,
            5826,
            4166,
            5778,
            4875,
            5891,
            5730,
            5752,
            5867,
            5878,
            5945,
            5458,
            5967,
            5974,
            5992,
            6001,
            5701,
            6025,
            5903,
            6007,
            6028,
            6014,
            5897,
            5965,
            6034,
            5906,
            6059,
            5938,
            6005,
            6046,
            5251,
            6032,
            5895,
            6047,
            6254,
            6257,
            6465,
            6459,
            6490,
            6515,
            5084,
            6639,
            6530,
            5232,
            6493,
            5015,
            6519,
            6611,
            6565,
            6727,
            6728,
            6664,
            6400,
            5969,
            6653,
            6609,
            6405,
            6734,
            5890,
            5925,
            5835,
            5999,
            5477,
            5770,
            5395,
            6675,
            5396,
            5260,
            5408,
            5771,
            5773,
            5774,
            5087,
            6665,
            5592,
            4892,
            4918,
            5067,
            6133,
            6846,
            4893,
            4901,
            5090,
            6008,
            4881,
            5228,
            5103,
            5321,
            5344,
            5313,
            5377,
            5896,
            6111,
            6119,
            6169,
            6240,
            6680,
            6732,
            6650,
            5727,
            5127,
            4681,
            4422,
            5541,
            5642,
            4380,
            4899,
            5242,
            5847,
            4894,
            5146,
            4482,
            5089,
            4697,
            4832,
            5141,
            4220,
            5046,
            4950,
            4331,
            4154,
            5704,
            4106,
            3584,
            3397,
            3407,
            3844,
            3983,
            5724,
            5555,
            5743,
            5769,
            5747,
            5748,
            3875,
            3854,
            3853,
            5940,
            3852,
            3861,
            5785,
            5735,
            4617,
            3860,
            3878,
            3997,
            3859,
            3868,
            4378,
            4351,
            4826,
            5056,
            4388,
            4397,
            4368,
            4416,
            4624,
            4431,
            4455,
            4448,
            4476,
            4514,
            4439,
            4530,
            4429,
            4779,
            4900,
            4963,
            5094,
            5692,
            5854,
            5942,
            6546,
            4385,
            4537,
            4522,
            4577,
            4612,
            4623,
            4649,
            6307,
            4630,
            4616,
            4556,
            4650,
            4629,
            4600,
            4548,
            4655,
            4663,
            5550,
            6284,
            4685,
            4662,
            4679,
            4717,
            5144,
            5111,
            3873,
            4707,
            4516,
            4996,
            5010,
            5012,
            4737,
            4884,
            5017,
            5239,
            5240,
            4735,
            4776,
            4782,
            4535,
            4673,
            4441,
            4714,
            4542,
            4569,
            4552,
            4571,
            4365,
            4517,
            4812,
            4375,
            5645,
            5650,
            4750,
            5071,
            4615,
            4868,
            4361,
            4407,
            4343,
            4890,
            4565,
            5739,
            4618,
            4602,
            5123,
            5101,
            5638,
            5823,
            5922,
            4572,
            4722,
            4641,
            4906,
            4563,
            4467,
            4692,
            4937,
            5293,
            4946,
            5320,
            4929,
            5286,
            4994,
            4680,
            4452,
            5005,
            5176,
            4974,
            4562,
            4780,
            4434,
            5062,
            4777,
            5051,
            5014,
            4425,
            4581,
            5070,
            5038,
            5020,
            4554,
            5079,
            4389,
            5091,
            5080,
            4449,
            4558,
            5114,
            4597,
            5148,
            5122,
            5162,
            5124,
            5172,
            5179,
            5182,
            5185,
            5192,
            5205,
            4480,
            5216,
            5137,
            5120,
            4657,
            5355,
            4729,
            4942,
            4989,
            4721,
            5454,
            5045,
            5460,
            5617,
            5479,
            5485,
            5138,
            5549,
            5869,
            4626,
            5024,
            5791,
            6153,
            5603,
            5624,
            5637,
            5613,
            5211,
            4352,
            5055,
            5212,
            5110,
            4354,
            5519,
            4481,
            4939,
            5557,
            5590,
            5495,
            5632,
            4665,
            5627,
            4465,
            5628,
            4627,
            5646,
            4564,
            5113,
            5600,
            5740,
            5520,
            5197,
            5889,
            4511,
            4496,
            5805,
            5472,
            5612,
            4768,
            5586,
            5498,
            3866,
            5608,
            5465,
            5053,
            5532,
            5579,
            4756,
            4382,
            5935,
            5923,
            5936,
            4653,
            4968,
            4438,
            6095,
            6100,
            6093,
            4658,
            6158,
            6161,
            6172,
            5107,
            6229,
            6180,
            5126,
            6237,
            6171,
            6230,
            6246,
            6285,
            6292,
            6287,
            5153,
            4958,
            6297,
            6302,
            6303,
            6298,
            6304,
            6305,
            5960,
            5151,
            4930,
            6293,
            4436,
            6705,
            6710,
            6718,
            6716,
            6693,
            6725,
            6819,
            6820,
            6825,
            6826,
            6822
        ];

        return (array_search(Auth::id(), $clients));
    }
}
