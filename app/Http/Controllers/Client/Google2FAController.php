<?php

namespace App\Http\Controllers\Client;

use App\GoogleAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Google2FA;

class Google2FAController extends Controller
{
    public function googleAuth()
    {
        $user = Auth::user();

        if ($user->google_auth_status == ACTIVE) {

            $secret = $user->google_auth_secret;

        }
        else {

            $secret = Google2FA::generateSecretKey();

        }

        $qrcode = Google2FA::getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qrcode' => $qrcode
            ]
        ]);
    }

    public function verifyGoogleAuth(Request $request)
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        if ($user->google_auth_status == ACTIVE) {

            return response()->json([
                'success' => false,
                'message' => 'Google Authenticator já está ativado!'
            ]);

        }

        if (Google2FA::verifyGoogle2FA($request->secret, $request->code)) {

            $user->google_auth_status = ACTIVE;

            $user->google_auth_secret = $request->secret;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Google Authenticator ativado com sucesso!'
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => 'Código inválido!'
        ]);
    }

    public function disableGoogleAuth(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        if ($user->google_auth_status == INACTIVE) {

            return response()->json([
                'success' => false,
                'message' => 'Google Authenticator já está desativado!'
            ]);

        }

        $status = Google2FA::verifyGoogle2FA($user->google_auth_secret, $request->code);

        if ($status) {

            $user->google_auth_status = INACTIVE;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Google Authenticator desativado com sucesso!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Código inválido!'
        ]);
    }
}
