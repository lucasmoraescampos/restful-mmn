<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use App\User;
use App\Log;

class ClientController extends Controller
{
    public function clients()
    {
        $clients = DB::table('users as u')
            ->select('u.created_at', 'u.name', 'u.username', 'm.username as manager', 'p.name as plan', 'u.status', 'u.token')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->leftJoin('users as m', 'm.id', 'u.id_manager')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    public function client($token)
    {
        $client = User::where('token', $token)->first();

        if ($client !== null) {

            $client->left_points = $client->getLeftPoints();

            $client->right_points = $client->getRightPoints();

            $client->left_count = $client->countUsersLeft();

            $client->right_count = $client->countUsersRight();

            $manager = $client->getManager();

            $plan = $client->getPlan();

            $document = $client->getDocuments();

            return response()->json([
                'success' => true,
                'data' => [
                    'client' =>  $client,
                    'manager' => $manager,
                    'plan' => $plan,
                    'document' => $document
                ]
            ]);
        }
        else {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);
        }
    }

    public function verifyUsername(Request $request)
    {
        $request->validate([
            'client' => 'required|string',
            'username' => 'required|string'
        ]);

        $client = User::where('token', $request->client)->first();

        if ($client == null) {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);

        }

        $isUnavailable = User::where('id', '<>', $client->id)
            ->where('username', $request->username)
            ->count();

        if ($isUnavailable) {

            return response()->json([
                'success' => false,
                'message' => 'Username indisponível!'
            ]);

        }

        return response()->json([
            'success' => true,
            'message' => 'Username disponível!'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'name' => 'required|string',
            'birth' => 'required|string',
            'email' => 'required|string',
            'username' => 'required|string',
            'phone' => 'required|string'
        ]);

        $client = User::where('token', $request->token)->first();

        if ($client == null) {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);

        }

        $isUnavailable = User::where('id', '<>', $client->id)
            ->where('username', $request->username)
            ->count();

        if ($isUnavailable) {

            return response()->json([
                'success' => false,
                'message' => 'Username indisponível!'
            ]);

        }

        $client->name = $request->name;

        $client->username = $request->username;

        $client->birth = format_date($request->birth, 'Y-m-d');

        $client->email = $request->email;

        $client->phone = $request->phone;

        $client->save();

        return response()->json([
            'success' => true,
            'message' => 'Cliente atualizado com sucesso!'
        ]);
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'client' => 'required|string'
        ]);

        $client = User::where('token', $request->client)->first();

        if ($client == null) {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);

        }

        $jwt_token = JWTAuth::fromUser($client);

        return response()->json([
            'success' => true,
            'token' => $jwt_token
        ]);
    }

    public function block(Request $request)
    {
        $request->validate([
            'client' => 'required|string'
        ]);

        $client = User::where('token', $request->client)->first();

        if ($client == null) {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);

        }

        $client->status = BLOCKED;

        $client->save();

        Log::create([
            'id_adm' => Auth::id(),
            'id_user' => $client->id,
            'type' => BLOCK_USER_LOG
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente bloqueado com sucesso!'
        ]);
    }

    public function unlock(Request $request)
    {
        $request->validate([
            'client' => 'required|string'
        ]);

        $client = User::where('token', $request->client)->first();

        if ($client == null) {

            return response()->json([
                'success' => false,
                'message' => 'Cliente não encontrado!'
            ]);

        }

        $plan = $client->getPlan();

        if ($plan == null) {

            $client->status = INACTIVE;

        }


        elseif ($plan->gain_percent >= 100) {

            $client->status = EXPIRED;

        }

        else {

            $client->status = ACTIVE;

        }

        $client->save();

        Log::create([
            'id_adm' => Auth::id(),
            'id_user' => $client->id,
            'type' => UNLOCK_USER_LOG
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente desbloqueado com sucesso!'
        ]);
    }

    public function additionalScore()
    {
        $clients = DB::table('users as u')
            ->select('u.name', 'u.username', 'p.name as plan', 'u.add_left_points as left_points', 'u.add_right_points as right_points')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->where('u.add_left_points', '>', 0)
            ->orWhere('u.add_right_points', '>', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    public function gainLimits()
    {
        $clients = DB::table('users as u')
            ->select('u.name', 'u.username', 'p.name as plan', 'u.add_plan_limit as plan_limit', 'u.add_daily_limit as daily_limit')
            ->leftJoin('plans as p', 'p.id', 'u.id_plan')
            ->whereNotNull('u.add_plan_limit')
            ->orWhereNotNull('u.add_daily_limit')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    public function documents()
    {
        $documents = DB::table('documents as d')
            ->select('d.created_at', 'u.name', 'u.username', 'd.type', 'd.status')
            ->leftJoin('users as u', 'u.id', 'd.id_user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function balanceInfo()
    {
        $total['bonus'] = User::sum('bonus');

        $total['income'] = User::sum('income');

        $total['count'] = User::count();

        return response()->json([
            'success' => true,
            'data' => $total
        ]);
    }
}
