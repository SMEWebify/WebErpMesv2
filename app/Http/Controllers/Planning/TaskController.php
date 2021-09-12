<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\StoreTaskRequest;

class TaskController extends Controller
{
    //
    public function store(StoreTaskRequest $request)
    {
       
        $Task = Task::create($request->only('LABEL', 
                                            'ORDER',
                                            'quote_lines_id',
                                            'order_lines_id',
                                            'products_id',
                                            'methods_services_id', 
                                            'component_id',
                                            'SETING_TIME', 
                                            'UNIT_TIME', 
                                            'REMAINING_TIME', 
                                            'ADVANCEMENT', 
                                            'STATU', 
                                            'TYPE',
                                            'DELAY',
                                            'QTY',
                                            'QTY_INIT',
                                            'QTY_AVIABLE',
                                            'UNIT_COST',
                                            'UNIT_PRICE',
                                            'methods_units_id',
                                            'x_size', 
                                            'Y_size', 
                                            'z_size', 
                                            'x_oversize',
                                            'y_oversize',
                                            'z_oversize',
                                            'diameter',
                                            'diameter_oversize',
                                            'to_schedule',
                                            'material', 
                                            'thickness', 
                                            'weight', 
                                            'quality_non_conformities_id',
                                            'methods_tools_id'));


        return redirect()->route('products.show', ['id' => $Task->products_id])->with('success', 'Successfully created new task');

    }
}
