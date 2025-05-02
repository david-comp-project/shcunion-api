<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getPrivateMessage() {
        $messages = Message::latest()->get();


        return response()->json($messages, 200);
    }
}
