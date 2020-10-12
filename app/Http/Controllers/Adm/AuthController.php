<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use App\Admin;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $jwt_token = null;

        if (!$jwt_token = JWTAuth::attempt($credentials)) {

            return response()->json([
                'success' => false,
                'message' => 'E-mail ou Senha Inválidos',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticado com sucesso',
            'token' => $jwt_token,
        ]);
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
                'message' => 'Administrador desconectado com successo'
            ]);
        }
        catch (JWTException $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Administrador não pode ser desconectado'
            ]);
        }
    }

    public function auth()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
