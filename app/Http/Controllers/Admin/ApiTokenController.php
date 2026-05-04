<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = PersonalAccessToken::with('tokenable')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => [
                'id'           => $t->id,
                'name'         => $t->name,
                'expires_at'   => $t->expires_at?->toISOString(),
                'last_used_at' => $t->last_used_at?->toISOString(),
                'created_at'   => $t->created_at->toISOString(),
                'user'         => $t->tokenable?->name ?? '—',
            ]);

        return view('admin.api-tokens', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'duration' => 'required|in:week,month,infinite',
        ]);

        $expiresAt = match ($request->duration) {
            'week'  => now()->addWeek(),
            'month' => now()->addMonth(),
            default => null,
        };

        $token = $request->user()->createToken(
            $request->name,
            ['*'],
            $expiresAt
        );

        $model = $token->accessToken;

        return response()->json([
            'plain_token' => $token->plainTextToken,
            'token' => [
                'id'           => $model->id,
                'name'         => $model->name,
                'expires_at'   => $model->expires_at?->toISOString(),
                'last_used_at' => null,
                'created_at'   => $model->created_at->toISOString(),
                'user'         => $request->user()->name,
            ],
        ]);
    }

    public function destroy(PersonalAccessToken $token)
    {
        $token->delete();

        return response()->json(['success' => true]);
    }
}
