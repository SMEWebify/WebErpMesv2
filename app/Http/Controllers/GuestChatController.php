<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Workflow\Quotes;
use Illuminate\Http\Request;

class GuestChatController extends Controller
{
    public function index(string $uuid)
    {
        $quote = Quotes::where('uuid', $uuid)->firstOrFail();

        $messages = Chat::with('User')
            ->where('related_id', $quote->id)
            ->where('related_type', 'Quotes')
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

    public function store(Request $request, string $uuid)
    {
        $quote = Quotes::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'label' => 'required|string|max:1000',
        ]);

        $message = Chat::create([
            'label'        => $data['label'],
            'user_id'      => null,
            'related_id'   => $quote->id,
            'related_type' => 'Quotes',
        ]);

        return response()->json([
            'id'         => $message->id,
            'label'      => $message->label,
            'user_id'    => null,
            'user_name'  => 'Guest',
            'created_at' => $message->GetPrettyCreatedAttribute(),
        ], 201);
    }
}
