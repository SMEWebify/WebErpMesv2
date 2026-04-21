<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'related_id'   => 'required|integer',
            'related_type' => 'required|string',
        ]);

        $messages = Chat::with('User')
            ->where('related_id', $request->related_id)
            ->where('related_type', $request->related_type)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'label'      => $m->label,
                'user_id'    => $m->user_id,
                'user_name'  => $m->user_id ? $m->User->name : 'Guest',
                'created_at' => $m->GetPrettyCreatedAttribute(),
            ]);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'        => 'required|string|max:1000',
            'related_id'   => 'required|integer',
            'related_type' => 'required|string',
        ]);

        $message = Chat::create([
            'label'        => $data['label'],
            'user_id'      => Auth::id(),
            'related_id'   => $data['related_id'],
            'related_type' => $data['related_type'],
        ]);

        $message->load('User');

        return response()->json([
            'id'         => $message->id,
            'label'      => $message->label,
            'user_id'    => $message->user_id,
            'user_name'  => $message->user_id ? $message->User->name : 'Guest',
            'created_at' => $message->GetPrettyCreatedAttribute(),
        ], 201);
    }
}
