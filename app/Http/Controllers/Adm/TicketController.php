<?php

namespace App\Http\Controllers\Adm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Ticket;

class TicketController extends Controller
{
    public function tickets()
    {
        $tickets = Ticket::all();

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }
}
