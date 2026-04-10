<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\DashboardConfig;

class DashboardConfigController extends Controller
{
    public function show()
    {
        $config = DashboardConfig::where('user_id', Auth::id())->first();

        return response()->json($config ? $config->layout : null);
    }

    public function update(Request $request)
    {
        $request->validate([
            'layout'          => 'required|array',
            'layout.*.i'      => 'required|string',
            'layout.*.x'      => 'required|integer|min:0',
            'layout.*.y'      => 'required|integer|min:0',
            'layout.*.w'      => 'required|integer|min:1',
            'layout.*.h'      => 'required|integer|min:1',
        ]);

        DashboardConfig::updateOrCreate(
            ['user_id' => Auth::id()],
            ['layout'  => $request->layout],
        );

        return response()->json(['ok' => true]);
    }
}
