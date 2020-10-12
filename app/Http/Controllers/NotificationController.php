<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\User;

class NotificationController extends Controller
{
    public function bitcoinPayment(Request $request)
    {
        $uri = parse_url($request->server('REQUEST_URI'));

        parse_str($uri['query'], $_GET);

		if($request->ip() == '162.144.148.21') {

            if ($_GET['confirmations'] >= 1) {

                $transaction = Transaction::where('wallet', $_GET['address'])
                    ->where('status', OPENED)
                    ->first();

                if ($transaction == null) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Esta fatura já foi avaliada!'
                    ]);

                }

                $user = User::find($transaction->id_user);

                if (dollar_to_satoshi(($transaction->value - 5), $transaction->bitcoin_price) > $_GET['value']) {

                    $transaction->status = INSUFFICIENTE;

                    $transaction->confirmation_type = TRANSACTION_BITCOIN;

                    $transaction->save();

                    return response()->json([
                        'success' => false,
                        'message' => 'Valor insuficiente para confirmação!'
                    ]);

                }

                else {

                    $user->setRootUserId();

                    $user->id_plan = $transaction->id_plan;

                    $user->status = ACTIVE;

                    $user->save();

                    $transaction->setPaid();

                    return response()->json([
                        'success' => true,
                        'message' => 'Fatura confirmada com sucesso!'
                    ]);

                }

            }

        }

    }

}
