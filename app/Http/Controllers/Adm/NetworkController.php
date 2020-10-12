<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;

class NetworkController extends Controller
{
    public function network()
    {
        $root = User::where('id_manager', 0)->first();

        $users = $root->getBinary();

        return response()->json([
            'success' => true,
            'data' =>  $users
        ]);
    }

    public function find($username)
    {
        $user = User::where('username', $username)->first();

        if ($user != null) {

            $users = $user->getBinary();

            return response()->json([
                'success' => true,
                'data' =>  $users
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' =>  'Usuário inexistente!'
            ]);
        }
    }

    public function rootInfo($username)
    {
        $user = User::where('username', $username)->first();

        if ($user != null) {

            $info = $user->getRootInfo();

            return response()->json([
                'success' => true,
                'data' =>  $info
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' =>  'Usuário inexistente!'
            ]);
        }
    }
}
