<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        return view('workflow/calendar-index');
    }
}
