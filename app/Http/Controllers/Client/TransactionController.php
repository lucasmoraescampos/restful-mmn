<?php

namespace App\Http\Controllers\Client;

use App\Chargeback;
use App\Extract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Transaction;
use App\Plan;
use App\System;
use App\User;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function transactions()
    {
        $userid = Auth::id();

        $transactions = Transaction::where('id_user', $userid)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => false,
            'data' => $transactions
        ]);
    }

    public function transaction($id)
    {
        $userid = Auth::id();

        $transaction = Transaction::find($id);

        if ($transaction != null && $transaction->id_user == $userid) {

            $transaction->createLink();

            $data['transaction'] = $transaction;

            if ($transaction->type == PAYMENT) {

                $plan = Plan::find($transaction->id_plan);

                $data['plan'] = [
                    'name' => $plan->name,
                    'img' => $plan->img
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada!'
            ]);
        }
    }

    public function openPayment()
    {
        $userid = Auth::id();

        $payment = Transaction::where('status', OPENED)
            ->where('id_user', $userid)
            ->where('type', PAYMENT)
            ->first();

        if ($payment != null) {

            $plan = Plan::find($payment->id_plan);

            $payment->description = $plan->name;

            $payment->img = $plan->img;
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    public function payWithBitcoin(Request $request)
    {
        $request->validate([
            'transaction' => 'required'
        ]);

        $transaction = Transaction::find($request->transaction);

        if ($transaction->wallet === null)
            $transaction->createWallet();

        $transaction->createLink();

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => $transaction->wallet,
                'amount' => $transaction->amount,
                'link' => $transaction->link
            ]
        ]);
    }

    public function sendReceipt(Request $request)
    {
        $request->validate([
            'transaction' => 'required'
        ]);

        $transaction = Transaction::find($request->transaction);

        if ($transaction->receipt !== null) {

            return response()->json([
                'success' => false,
                'message' => 'Comprovante já enviado!'
            ]);

        }

        if (!$request->hasFile('image')) {

            return response()->json([
                'success' => false,
                'message' => 'Nenhuma imagem enviada!'
            ]);
        }

        if (!$request->file('image')->isValid()) {

            return response()->json([
                'success' => false,
                'message' => 'A imagem enviada é inválida!'
            ]);
        }

        $type = $request->file('image')->getMimeType();

        if ($type != 'image/png' && $type != 'image/jpeg') {

            return response()->json([
                'success' => false,
                'message' => 'O arquivo enviado não é uma imagem!'
            ]);
        }

        $name = uniqid(date('HisYmd'));

        $extension = $request->image->extension();

        $fullName = "{$name}.{$extension}";

        $upload = $request->image->storeAs('receipts', $fullName);

        if (!$upload) {

            return response()->json([
                'success' => false,
                'message' => 'Arquivo não enviado, tente novamente!'
            ]);
        }

        $transaction->receipt = $fullName;

        $transaction->receipt_sent_at = date('Y-m-d H:i:s');

        $transaction->receipt_ip_sent = $request->ip();

        $transaction->save();

        return response()->json([
            'success' => true,
            'message' => 'Comprovante enviado com sucesso!',
            'img' => $transaction->receipt
        ]);
    }

    public function withdrawDay()
    {
        $is_bonus_withdraw_day = Transaction::bonusWithdrawalDay();

        $is_income_withdraw_day = Transaction::incomeWithdrawalDay();

        if (!$is_bonus_withdraw_day && !$is_income_withdraw_day) {

            return response()->json([
                'success' => false,
                'message' => 'Saque não disponível hoje!'
            ]);

        }

        $data = [];

        if ($is_bonus_withdraw_day) {

            $data['balance'][] = [
                'id' => WITHDRAW_BONUS,
                'type' => 'Saldo de Bônus'
            ];

        }

        if ($is_income_withdraw_day) {

            $data['balance'][] = [
                'id' => WITHDRAW_INCOME,
                'type' => 'Saldo de Rendimento'
            ];

        }

        $data['fee'] = System::getWithdrawFee();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function withdrawIncome(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'value' => 'required'
        ]);

        $user = Auth::user();

        $value = format_money($request->value);

        $fee = percentage_to_value(System::getWithdrawFee(), $value);

        if (!Transaction::incomeWithdrawalDay()) {

            return response()->json([
                'success' => false,
                'message' => 'Saque de rendimento não disponível hoje!'
            ]);

        }

        if ($value > $user->income) {

            return response()->json([
                'success' => false,
                'message' => 'Saldo de rendimento indisponível!'
            ]);

        }

        if ($request->type == TRANSACTION_BITCOIN) {

            if ($user->wallet === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma carteira bitcoin cadastrada em sua conta!'
                ]);

            }

            $transaction = new Transaction([
                'id_user' => $user->id,
                'type' => WITHDRAW,
                'value' => $value - $fee,
                'fee' => $fee,
                'bitcoin_price' => System::getBitcoinPrice(),
                'balance_type' => WITHDRAW_INCOME,
                'confirmation_type' => TRANSACTION_BITCOIN,
                'wallet' => $user->wallet
            ]);

            $transaction->save();

        }

        elseif ($request->type == TRANSACTION_SOPAGUE) {

            if ($user->bank_code === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma conta bancária cadastrada em sua conta!'
                ]);

            }

            $transaction = new Transaction([
                'id_user' => $user->id,
                'type' => WITHDRAW,
                'value' => $value - $fee,
                'fee' => $fee,
                'confirmation_type' => TRANSACTION_SOPAGUE,
                'bitcoin_price' => System::getBitcoinPrice(),
                'balance_type' => WITHDRAW_INCOME,
                'bank_code' => $user->bank_code,
                'bank_type' => $user->bank_type,
                'bank_agency' => $user->bank_agency,
                'bank_number' => $user->bank_number,
                'bank_holder' => $user->bank_holder,
                'bank_cpf' => $user->bank_cpf
            ]);

            $transaction->save();

        }

        else {

            return response()->json([
                'error' => 'Transaction Type Invalid!'
            ], 400);

        }

        $user->income -= $value;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Saque solicitado com sucesso!'
        ]);
    }

    public function withdrawBonus(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'value' => 'required'
        ]);

        $user = Auth::user();

        $value = format_money($request->value);

        $fee = percentage_to_value(System::getWithdrawFee(), $value);

        if (!Transaction::bonusWithdrawalDay()) {

            return response()->json([
                'success' => false,
                'message' => 'Saque de bônus não disponível hoje!'
            ]);

        }

        if ($value > $user->bonus) {

            return response()->json([
                'success' => false,
                'message' => 'Saldo de bônus indisponível!'
            ]);

        }

        if ($request->type == TRANSACTION_BITCOIN) {

            if ($user->wallet === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma carteira bitcoin cadastrada em sua conta!'
                ]);

            }

            $transaction = new Transaction([
                'id_user' => $user->id,
                'type' => WITHDRAW,
                'value' => $value - $fee,
                'fee' => $fee,
                'bitcoin_price' => System::getBitcoinPrice(),
                'balance_type' => WITHDRAW_BONUS,
                'confirmation_type' => TRANSACTION_BITCOIN,
                'wallet' => $user->wallet
            ]);

            $transaction->save();

        }

        elseif ($request->type == TRANSACTION_SOPAGUE) {

            if ($user->bank_code === null) {

                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma conta bancária cadastrada em sua conta!'
                ]);

            }

            $transaction = new Transaction([
                'id_user' => $user->id,
                'type' => WITHDRAW,
                'value' => $value - $fee,
                'fee' => $fee,
                'confirmation_type' => TRANSACTION_SOPAGUE,
                'bitcoin_price' => System::getBitcoinPrice(),
                'balance_type' => WITHDRAW_BONUS,
                'bank_code' => $user->bank_code,
                'bank_type' => $user->bank_type,
                'bank_agency' => $user->bank_agency,
                'bank_number' => $user->bank_number,
                'bank_holder' => $user->bank_holder,
                'bank_cpf' => $user->bank_cpf
            ]);

            $transaction->save();

        }

        else {

            return response()->json([
                'error' => 'Transaction Type Invalid!'
            ], 400);

        }

        $user->bonus -= $value;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Saque solicitado com sucesso!'
        ]);
    }

    public function chargebackStatus()
    {
        $duplicity = Chargeback::where('id_user', Auth::id())->count();

        if (!Transaction::isChargebackAvailable() || $duplicity) {

            return response()->json([
                'success' => false,
                'message' => 'Devolução indisponível!'
            ]);

        }

        if (Transaction::isChargebackAvailable()) {

            return response()->json([
                'success' => true,
                'message' => 'Devolução disponível!'
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => 'Devolução indisponível!'
        ]);
    }

    public function calculateChargeback()
    {
        $user = Auth::user();

        $duplicity = Chargeback::where('id_user', $user->id)->count();

        if (!Transaction::isChargebackAvailable() || $duplicity) {

            return response()->json([
                'success' => false,
                'message' => 'Devolução indisponível!'
            ]);

        }

        $plan = $user->getPlan();

        if ($plan == null) {

            return response()->json([
                'success' => false,
                'message' => 'Você não possui valores a recuperar!'
            ]);

        }

        $value = $plan->price - $plan->plan_gain;

        if ($value <= 0) {

            return response()->json([
                'success' => false,
                'message' => 'Você já recuperou todo o valor investido!'
            ]);

        }

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => format_money($value)
            ]
        ]);

    }

    public function chargeback(Request $request)
    {
        $request->validate([
            'wallet' => 'required'
        ]);

        $user = Auth::user();

        $duplicity = Chargeback::where('id_user', $user->id)->count();

        if (!Transaction::isChargebackAvailable() || $duplicity) {

            return response()->json([
                'success' => false,
                'message' => 'Devolução indisponível!'
            ]);

        }

        $plan = $user->getPlan();

        $value = $plan->price - $plan->plan_gain;

        if ($value <= 0) {

            return response()->json([
                'success' => false,
                'message' => 'Você já recuperou todo o valor investido!'
            ]);

        }

        Chargeback::create([
            'id_user' => $user->id,
            'value' => format_money($value),
            'wallet' => $request->wallet
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Devolução solicitada com sucesso!'
        ]);
    }

    public function dead()
    {
        set_time_limit(0);

        $users = User::all();

        foreach ($users as $user) {

            $withdraws = Transaction::selectRaw('sum(value) + sum(fee) as total')
                ->where('id_user', $user->id)
                ->where('type', WITHDRAW)
                ->where('status', 1)
                ->first();

            $payments = Transaction::selectRaw('sum(value) as total')
                ->where('id_user', $user->id)
                ->where('type', PAYMENT)
                ->where('status', 1)
                ->first();

            $value = $payments->total - $withdraws->total;

            if ($value <= 0) {

                $user->bonus = 0;
                $user->income = 0;
                $user->save();

            }

            else {

                $user->bonus = $value;
                $user->income = 0;
                $user->save();

                Extract::create([
                    'id_user' => $user->id,
                    'id_plan' => 0,
                    'value' => $value,
                    'type' => 10
                ]);

            }

        }

        return response()->json([
            'success' => true,
            'message' => 'Salve Salve!'
        ]);
    }

    public function lastDraw(Request $request)
    {
        $request->validate([
            'wallet' => 'required'
        ]);

        $user = Auth::user();

        if ($user->bonus < 0) {

            return response()->json([
                'success' => false,
                'message' => 'Solicitação não autorizada!'
            ]);

        }

        for ($i = 1; $i <= 6; $i++) {

            $t = Transaction::create([
                'id_user' => $user->id,
                'type' => WITHDRAW,
                'value' => $user->bonus/6,
                'balance_type' => WITHDRAW_BONUS,
                'confirmation_type' => TRANSACTION_BITCOIN,
                'wallet' => $request->wallet
            ]);

            $t->created_at = date('Y-m-d', strtotime("+$i months"));

            $t->save();

        }

        $user->bonus = 0;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Solicitação realizada com sucesso!'
        ]);
    }

    public function listLastDraw()
    {
        $draws = Transaction::where('id_user', Auth::id())
            ->where('status', INACTIVE)
            ->where('type', WITHDRAW)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $draws
        ]);
    }
}
