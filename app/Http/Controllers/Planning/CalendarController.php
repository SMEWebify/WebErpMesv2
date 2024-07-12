<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function calendarOders()
    {
        return view('workflow/calendar-index', ['eventType' => 'orders']);
    }

    /**
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function calendarTasks()
    {
        return view('workflow/calendar-index', ['eventType' => 'tasks']);
    }
}
