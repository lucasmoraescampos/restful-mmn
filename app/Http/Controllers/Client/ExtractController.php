<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Extract;
use App\User;
use App\Plan;

class ExtractController extends Controller
{
    public function extracts()
    {
        $userid = Auth::id();

        $extracts = Extract::where('id_user', $userid)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $extracts
        ]);
    }

    public function extract($id)
    {
        $userid = Auth::id();

        $extract = Extract::find($id);

        if ($extract != null && $userid == $extract->id_user) {

            $plan = Plan::find($extract->id_plan);

            $data = [
                'extract' => [
                    'type' => $extract->type,
                    'value' => $extract->value,
                    'points' => $extract->points,
                    'percent' => $extract->percent,
                    'side' => $extract->side,
                    'created_at' => format_date($extract->created_at, 'Y-m-d H:i:s')
                ],
                'plan' => [
                    'name' => $plan->name,
                    'img' => $plan->img
                ]
            ];

            if ($extract->type == INDICATION) {

                $client = User::find($extract->id_client);

                $data['client'] = [
                    'name' => $client->name,
                    'username' => $client->username
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Extrato não encontrado!'
            ]);
        }
    }
}
