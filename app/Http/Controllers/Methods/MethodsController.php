<?php

namespace App\Http\Controllers\Methods;

use App\Services\SelectDataService;

class MethodsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('methods/methods-index');
    }
}
