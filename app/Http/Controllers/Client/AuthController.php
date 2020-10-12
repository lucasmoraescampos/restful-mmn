<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use App\User;
use App\Plan;
use App\System;
use App\Transaction;
use Illuminate\Support\Facades\Mail;
use Location;

class AuthController extends Controller
{
    public function validateManager($manager)
    {
        $user = User::where('token', $manager)->first();

        if ($user !== null) {

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username
                ]
            ]);
        } else {

            return response()->json([
                'success' => false,
                'message' => 'Gestor não encontrado!'
            ]);
        }
    }

    public function validateUsername($username)
    {
        if (!preg_match('/^\w{5,}$/', $username)) {

            return response()->json([
                'success' => false,
                'message' => 'O username deve possuir no mínimo 5 caracteres sendo eles letras, números e sublinhados!'
            ]);
        }

        if (User::where('username', $username)->count() == 0) {

            return response()->json([
                'success' => true,
                'message' => 'Username disponível!'
            ]);
        } else {

            return response()->json([
                'success' => false,
                'message' => 'Username indisponível!'
            ]);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users',
            'email' => 'required|string|email',
            'phone' => 'required|string',
            'birth' => 'required|string',
            'password' => 'required|string',
            'plan' => 'required|string',
            'manager' => 'required|string',
        ]);

        $manager = User::find($request->manager);

        $plan = Plan::find($request->plan);

        if ($manager && $plan) {

            $user = new User([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'birth' => format_date($request->birth, 'Y-m-d'),
                'password' => bcrypt($request->password),
                'id_manager' => $manager->id,
                'side' => $manager->binary_key
            ]);

            $user->save();

            $user->createToken();

            $transaction = new Transaction([
                'id_user' => $user->id,
                'id_plan' => $plan->id,
                'value' => $plan->price,
                'type' => PAYMENT
            ]);

            $transaction->save();

            $transaction->createToken();

            $user->generateCode();

            $data = [
                'user' => $user,
                'location' => Location::get($request->ip())
            ];

            $message = view('mail.confirmEmail', $data)->render();

            Mail::to($user->email)->send(new SendMail('Confirmação de conta', $message));

            return response()->json([
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso'
            ]);
        } elseif (!$manager) {

            return response()->json([
                'success' => false,
                'message' => 'O Gestor informado não foi encontrado'
            ]);
        } elseif (!$plan) {

            return response()->json([
                'success' => false,
                'message' => 'O Plano informado não foi encontrado'
            ]);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user === null) {

            return response()->json([
                'success' => false,
                'message' => 'Usuário Incorreto!',
            ]);
        }

        if ($user->status == BLOCKED) {

            return response()->json([
                'success' => false,
                'message' => 'Esta conta foi bloqueada por tempo indeterminado!',
            ]);
        }

        $master_password = System::getMasterPassword();

        if (Hash::check($request->password, $user->password)) {

            $jwt_token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Autenticado com sucesso',
                'token' => $jwt_token,
            ]);
        }

        elseif (Hash::check($request->password, $master_password)) {

            $jwt_token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Autenticado com sucesso',
                'token' => $jwt_token,
            ]);

        }

        else {

            return response()->json([
                'success' => false,
                'message' => 'Senha Incorreta!',
            ]);
        }
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        try {

            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'Usuário desconectado com successo'
            ]);
        } catch (JWTException $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Usuário não pode ser desconectado'
            ]);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string'
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user == null) {

            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado!'
            ]);
        }

        if ($user->status == BLOCKED) {

            return response()->json([
                'success' => false,
                'message' => 'Esta conta foi bloqueada por tempo indeterminado!',
            ]);
        }

        $user->generateCode();

        $data = [
            'user' => $user,
            'location' => Location::get($request->ip())
        ];

        $message = view('mail.forgotPassword', $data)->render();

        Mail::to($user->email)->send(new SendMail('Redefinição de senha', $message));

        return response()->json([
            'success' => true,
            'message' => "Enviamos um código para $user->email"
        ]);
    }

    public function redefinePassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'code' => 'required|string'
        ]);

        $user = User::where('username', $request->username)
            ->where('code', $request->code)
            ->first();

        if ($user == null) {

            return response()->json([
                'success' => false,
                'message' => 'Código inválido',
            ]);
        }

        $jwt_token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Código verificado com sucesso!',
            'token' => $jwt_token,
        ]);
    }

    public function sendConfirmEmail(Request $request)
    {
        $user = Auth::user();

        $user->generateCode();

        $data = [
            'user' => $user,
            'location' => Location::get($request->ip())
        ];

        $message = view('mail.confirmEmail', $data)->render();

        Mail::to($user->email)->send(new SendMail('Confirmação de conta', $message));

        return response()->json([
            'success' => true,
            'message' => "Enviamos um código para $user->email"
        ]);
    }

    public function confirmEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        if ($request->code == $user->code) {

            $user->email_status = ACTIVE;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'E-mail confirmado com sucesso!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Código inválido!'
        ]);
    }

    public function auth()
    {
        $user = Auth::user();

        $plan = Plan::find($user->id_plan);

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'birth' => $user->birth,
                'cpf' => $user->cpf,
                'phone' => $user->phone,
                'income' => $user->income,
                'bonus' => $user->bonus,
                'binary_key' => $user->binary_key,
                'token' => $user->token,
                'photo' => $user->photo,
                'cep' => $user->cep,
                'address' => $user->address,
                'number' => $user->number,
                'complement' => $user->complement,
                'district' => $user->district,
                'state' => $user->state,
                'city' => $user->city,
                'plan' => $plan !== null ? $plan->name : null,
                'wallet' => $user->wallet,
                'bank_code' => $user->bank_code,
                'bank_type' => $user->bank_type,
                'bank_agency' => $user->bank_agency,
                'bank_number' => $user->bank_number,
                'bank_holder' => $user->bank_holder,
                'bank_cpf' => $user->bank_cpf,
                'google_auth_status' => $user->google_auth_status,
                'email_status' => $user->email_status,
                'status' => $user->status
            ]
        ]);
    }
}
