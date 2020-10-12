<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Ticket;

class TicketController extends Controller
{
    public function tickets()
    {
        $tickets = Ticket::where('id_user', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    public function createTicket(Request $request)
    {
        $userid = Auth::id();

        $waitingTicket = Ticket::where('id_user', $userid)
            ->where('status', WAITING)
            ->count();

        if ($waitingTicket) {

            return response()->json([
                'success' => false,
                'message' => 'Você já possui um ticket em espera!'
            ]);

        }

        $request->validate([
            'subject' => 'required|string',
            'description' => 'required|string',
            'type' => 'required',
            'priority' => 'required'
        ]);

        $ticket = new Ticket([
            'id_user' => $userid,
            'subject' => $request->subject,
            'description' => $request->description,
            'type' => $request->type,
            'priority' => $request->priority,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {

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

            $request->image->storeAs('attachments', $fullName);

            $ticket->image = $fullName;
        }

        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Ticket registrado com sucesso!'
        ]);
    }
}
