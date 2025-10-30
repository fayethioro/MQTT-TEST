<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function send(Request $request, WhatsAppService $whatsapp)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        $response = $whatsapp->sendMessage($request->to, $request->message);

        return response()->json($response);
    }
}
