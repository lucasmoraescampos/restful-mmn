<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Log;
use App\Plan;
use App\Transaction;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function activations($limit = null)
    {
        $transactions = DB::table('transactions as t')
            ->select('t.created_at', 'u.username as user', 'p.name as plan', 'p.img', 't.value', 't.status', 't.token')
            ->leftJoin('users as u', 'u.id', 't.id_user')
            ->leftJoin('plans as p', 'p.id', 't.id_plan')
            ->where('t.type', PAYMENT)
            ->where('t.status', PAID)
            ->orderBy('t.created_at', 'desc');

        if ($limit !== null)
            $transactions = $transactions->limit($limit)->get();

        else
            $transactions = $transactions->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function invoicesResume()
    {
        $opened = Transaction::select(DB::raw('count(*) as count, sum(value) as value'))
            ->where('type', PAYMENT)
            ->where('status', OPENED)
            ->first();

        $paid = Transaction::select(DB::raw('count(*) as count, sum(value) as value'))
            ->where('type', PAYMENT)
            ->where('status', PAID)
            ->first();

        if ($opened->value === null)
            $opened->value = 0;

        if ($paid->value === null)
            $paid->value = 0;

        return response()->json([
            'success' => true,
            'data' => [
                'opened' => $opened,
                'paid' => $paid
            ]
        ]);
    }

    public function withdrawsResume()
    {
        $waiting = Transaction::select(DB::raw('count(*) as count, sum(value) as value'))
            ->where('type', WITHDRAW)
            ->where('status', WAITING)
            ->first();

        $confirmed = Transaction::select(DB::raw('count(*) as count, sum(value) as value'))
            ->where('type', WITHDRAW)
            ->where('status', CONFIRMED)
            ->first();

        if ($waiting->value === null)
            $waiting->value = 0;

        if ($confirmed->value === null)
            $confirmed->value = 0;

        return response()->json([
            'success' => true,
            'data' => [
                'waiting' => $waiting,
                'confirmed' => $confirmed
            ]
        ]);
    }

    public function invoices()
    {
        $invoices = DB::table('transactions as t')
            ->select('t.id', 't.created_at', 'u.username as user', 'p.name as plan', 'p.img', 't.value', 't.status')
            ->leftJoin('users as u', 'u.id', 't.id_user')
            ->leftJoin('plans as p', 'p.id', 't.id_plan')
            ->where('t.type', PAYMENT)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    public function invoice($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction !== null) {

            $user = $transaction->getUser();

            $manager = $user->getManager();

            $plan = $transaction->getPlan();

            $isVoucherValid = Plan::where('name', $plan->name)
                ->where('id', '<>', $transaction->id_plan)
                ->count();

            if ($isVoucherValid)
                $transaction->valid_voucher = true;

            else
                $transaction->valid_voucher = false;

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $transaction,
                    'client' => $user,
                    'manager' => $manager,
                    'plan' => $plan
                ]
            ]);
        } else {

            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada!'
            ]);
        }
    }

    public function withdrawals()
    {
        $withdraws = DB::table('transactions as t')
            ->select('t.id', 't.created_at', 'u.username as user', 't.value', 't.fee', 't.confirmation_type', 't.status')
            ->leftJoin('users as u', 'u.id', 't.id_user')
            ->where('t.type', WITHDRAW)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $withdraws
        ]);
    }

    public function withdrawal($id)
    {
        $withdraw = Transaction::find($id);

        if ($withdraw === null) {

            return response()->json([
                'success' => false,
                'message' => 'Saque não encontrado!'
            ]);
        }

        $withdraw->createLink();

        $user = $withdraw->getUser();

        $withdraw->user = $user->username;

        return response()->json([
            'success' => true,
            'data' => $withdraw
        ]);
    }

    public function payInvoice(Request $request)
    {
        $request->validate([
            'invoice' => 'required'
        ]);

        $invoice = Transaction::find($request->invoice);

        if ($invoice == null) {

            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada!'
            ]);
        }

        $invoice->setPaid();

        Log::create([
            'id_adm' => Auth::id(),
            'id_user' => $invoice->id_user,
            'id_transaction' => $invoice->id,
            'type' => PAYMENT_LOG
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fatura paga com sucesso!'
        ]);
    }

    public function applyVoucher(Request $request)
    {
        $request->validate([
            'invoice' => 'required'
        ]);

        $invoice = Transaction::find($request->invoice);

        if ($invoice == null) {

            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada!'
            ]);
        }

        $plan = $invoice->getPlan();

        $voucher = Plan::where('name', $plan->name)
            ->where('id', '<>', $invoice->id_plan)
            ->first();

        if ($voucher == null) {

            return response()->json([
                'success' => false,
                'message' => 'Esse plano não possui um voucher!'
            ]);

        }

        $invoice->id_plan = $voucher->id;

        $invoice->value = $voucher->price;

        $invoice->setPaid();

        return response()->json([
            'success' => true,
            'message' => 'Voucher aplicado com sucesso!'
        ]);
    }

    public function confirmWithdrawal(Request $request)
    {
        $request->validate([
            'withdraw' => 'required'
        ]);

        $withdraw = Transaction::find($request->withdraw);

        if ($withdraw === null) {

            return response()->json([
                'success' => false,
                'message' => 'Saque não encontrado!'
            ]);

        }

        if ($withdraw->status != WAITING) {

            return response()->json([
                'success' => false,
                'message' => 'Este saque já foi avaliado!'
            ]);

        }

        $withdraw->status = CONFIRMED;

        $withdraw->confirmed_at = date('Y-m-d H:i:s');

        $withdraw->save();

        $log = new Log([
            'id_adm' => Auth::id(),
            'id_user' =>  $withdraw->id_user,
            'id_transaction' => $withdraw->id,
            'type' => WITHDRAW_LOG
        ]);

        $log->save();

        return response()->json([
            'success' => true,
            'message' => 'Saque confirmado com sucesso!'
        ]);
    }

    public function refuseWithdrawal(Request $request)
    {
        $request->validate([
            'withdraw' => 'required',
            'message' => 'required|string'
        ]);

        $withdraw = Transaction::find($request->withdraw);

        if ($withdraw === null) {

            return response()->json([
                'success' => false,
                'message' => 'Saque não encontrado!'
            ]);

        }

        if ($withdraw->status != WAITING) {

            return response()->json([
                'success' => false,
                'message' => 'Este saque já foi avaliado!'
            ]);

        }

        $user = User::find($withdraw->id_user);

        if ($withdraw->type == WITHDRAW_BONUS) {

            $user->bonus += $withdraw->value + $withdraw->fee;

        }

        elseif ($withdraw->type == WITHDRAW_INCOME) {

            $user->income += $withdraw->value + $withdraw->fee;

        }

        $user->save();

        $withdraw->status = REFUSED;

        $withdraw->confirmed_at = date('Y-m-d H:i:s');

        $withdraw->message = $request->message;

        $withdraw->save();

        $log = new Log([
            'id_adm' => Auth::id(),
            'id_user' =>  $withdraw->id_user,
            'id_transaction' => $withdraw->id,
            'type' => WITHDRAW_LOG
        ]);

        $log->save();

        return response()->json([
            'success' => true,
            'message' => 'Saque recusado com sucesso!'
        ]);
    }

    public function evaluateWithdrawal(Request $request)
    {
        $request->validate([
            'withdraw' => 'required',
            'status' => 'required'
        ]);

        if ($request->status != CONFIRMED && $request->status != REFUSED) {

            return response()->json([
                'success' => false,
                'error' => 'Status inválido!'
            ], 400);

        }

        $withdraw = Transaction::find($request->withdraw);

        if ($withdraw === null) {

            return response()->json([
                'success' => false,
                'message' => 'Saque não encontrado!'
            ]);

        }

        if ($withdraw->status != WAITING) {

            return response()->json([
                'success' => false,
                'message' => 'Este saque já foi avaliado!'
            ]);

        }

        $withdraw->status = $request->status;

        $withdraw->confirmed_at = date('Y-m-d H:i:s');

        $withdraw->save();

        $user = User::find($withdraw->id_user);

        $log = new Log([
            'id_adm' => Auth::id(),
            'id_user' =>  $user->id,
            'id_transaction' => $withdraw->id,
            'type' => WITHDRAW_LOG
        ]);

        $log->save();

        if ($request->status == REFUSED) {

            if ($withdraw->balance_type == WITHDRAW_BONUS) {

                $user->bonus += $withdraw->value + $withdraw->fee;

            }

            elseif ($withdraw->balance_type == WITHDRAW_INCOME) {

                $user->income += $withdraw->value + $withdraw->fee;

            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Saque recusado com sucesso!'
            ]);

        }

        else {

            return response()->json([
                'success' => true,
                'message' => 'Saque confirmado com sucesso!'
            ]);

        }
    }
}
