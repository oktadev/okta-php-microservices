<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TranscriptionController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'audio-file-url' => 'required|url'
        ]);

        $message = [
            'user-email'          => $request->input('email'),
            'user-audio-file-url' => $request->input('audio-file-url')
        ];

        return response()->json($message, 201);
    }
}
