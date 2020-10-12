<?php

namespace App\Http\Controllers\Client;

use App\Banking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
use Google2FA;

class SettingController extends Controller
{
    public function changePhoto(Request $request)
    {
        if (!$request->hasFile('photo')) {

            return response()->json([
                'success' => false,
                'message' => 'Nenhuma imagem enviada!'
            ]);
        }

        if (!$request->file('photo')->isValid()) {

            return response()->json([
                'success' => false,
                'message' => 'A imagem enviada é inválida!'
            ]);
        }

        $type = $request->file('photo')->getMimeType();

        if ($type != 'image/png' && $type != 'image/jpeg') {

            return response()->json([
                'success' => false,
                'message' => 'O arquivo enviado não é uma imagem!'
            ]);
        }

        $name = uniqid(date('HisYmd'));

        $extension = $request->photo->extension();

        $fullName = "{$name}.{$extension}";

        $upload = $request->photo->storeAs('profiles', $fullName);

        if (!$upload) {

            return response()->json([
                'success' => false,
                'message' => 'Arquivo não enviado, tente novamente!'
            ]);
        }

        $user = Auth::user();

        $user->photo = $fullName;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto atualizada com sucesso!',
            'img' => $user->photo
        ]);
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
        } elseif (Auth::user()->username != $username) {

            return response()->json([
                'success' => false,
                'message' => 'Username indisponível!'
            ]);
        } else {

            return response()->json([
                'success' => true,
                'message' => 'Nenhuma alteração feita!'
            ]);
        }
    }

    public function changeProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string',
            'email' => 'required|string',
            'cpf' => 'required|string',
            'birth' => 'required|string',
            'phone' => 'required|string'
        ]);

        $user = Auth::user();

        $isRegistered = User::where('username', $request->username)->count();

        if ($isRegistered && $user->username != $request->username) {

            return response()->json([
                'success' => false,
                'message' => 'Username indisponível!'
            ]);
        }

        if (!validate_cpf($request->cpf)) {

            return response()->json([
                'success' => false,
                'message' => 'CPF inválido!'
            ]);
        }

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->cpf = $request->cpf;
        $user->birth = format_date($request->birth, 'Y-m-d H:i:s');
        $user->phone = $request->phone;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!'
        ]);
    }

    public function changeAddress(Request $request)
    {
        $request->validate([
            'cep' => 'required|string',
            'address' => 'required|string',
            'number' => 'required|string',
            'complement' => 'required|string',
            'district' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string'
        ]);

        $user = Auth::user();

        $user->cep = $request->cep;
        $user->address = $request->address;
        $user->number = $request->number;
        $user->complement = $request->complement;
        $user->district = $request->district;
        $user->state = $request->state;
        $user->city = $request->city;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Endereço atualizado com sucesso!'
        ]);
    }

    public function uploadDocument(Request $request)
    {
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

        $request->validate([
            'document_type' => 'required',
            'image_type' => 'required'
        ]);

        $name = uniqid(date('HisYmd'));

        $extension = $request->image->extension();

        $fullName = "{$name}.{$extension}";

        $upload = $request->image->storeAs('documents', $fullName);

        if (!$upload) {

            return response()->json([
                'success' => false,
                'message' => 'Arquivo não enviado, tente novamente!'
            ]);
        }

        $user = Auth::user();

        if ($request->image_type == DOCUMENT_FRONT)
            $img = $user->document_front = $fullName;

        elseif ($request->image_type == DOCUMENT_BACK)
            $img = $user->document_back = $fullName;

        elseif ($request->image_type == DOCUMENT_CPF)
            $img = $user->document_cpf = $fullName;

        elseif ($request->image_type == DOCUMENT_SELFIE)
            $img = $user->document_selfie = $fullName;

        elseif ($request->image_type == DOCUMENT_ADDRESS)
            $img = $user->document_address = $fullName;

        $user->document_type = $request->document_type;

        $user->document_status = WAITING;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Documento enviado com sucesso!',
            'img' => $img
        ]);
    }

    public function documents()
    {
        $user = Auth::user();

        $documents = $user->getDocuments();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function bankings()
    {
        $bankings = Banking::all();

        return response()->json([
            'success' => true,
            'data' => $bankings
        ]);
    }

    public function changeBanking(Request $request)
    {
        $request->validate([
            'bank_code' => 'required|string',
            'bank_type' => 'required|string',
            'bank_agency' => 'required|string',
            'bank_number' => 'required|string',
            'bank_holder' => 'required|string',
            'bank_cpf' => 'required|string'
        ]);

        if (!validate_cpf($request->bank_cpf)) {

            return response()->json([
                'success' => false,
                'message' => 'CPF inválido!'
            ]);
        }

        $user = Auth::user();

        $user->wallet = $request->wallet;

        $user->bank_code = $request->bank_code;
        $user->bank_type = $request->bank_type;
        $user->bank_agency = $request->bank_agency;
        $user->bank_number = $request->bank_number;
        $user->bank_holder = $request->bank_holder;
        $user->bank_cpf = $request->bank_cpf;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Conta bancária atualizada com sucesso!'
        ]);
    }

    public function changeWallet(Request $request)
    {
        $request->validate([
            'wallet' => 'required|string'
        ]);

        $user = Auth::user();

        $user->wallet = $request->wallet;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Carteira atualizada com sucesso!'
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if ($user->google_auth_status == ACTIVE) {

            $request->validate([
                'password' => 'required|string',
                'code' => 'required|string'
            ]);

            if (!Google2FA::verifyGoogle2FA($user->google_auth_secret, $request->code)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Código inválido!'
                ]);
            }

        }
        else {

            $request->validate([
                'password' => 'required|string'
            ]);

        }

        $user->password = bcrypt($request->password);

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Senha atualizada com sucesso!'
        ]);
    }
}
