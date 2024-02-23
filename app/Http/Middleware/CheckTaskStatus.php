<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Planning\Status;
use Symfony\Component\HttpFoundation\Response;

class CheckTaskStatus
{
    public function handle($request, Closure $next)
    {
        $status = Status::select('id')->orderBy('order')->first();

        if (!$status) {
            return redirect()->route('admin.factory')->withErrors('Please add Kanban information before');
        }

        return $next($request);
    }
}
