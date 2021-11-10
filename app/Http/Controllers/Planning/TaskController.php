<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\StoreTaskRequest;

class TaskController extends Controller
{
    //
    public function store(StoreTaskRequest $request, $id)
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
        if(isset($request->products_id)){
            return redirect()->route('products.show', ['id' => $id])->with('success', 'Successfully created new task');
        }
        elseif(isset($request->quote_lines_id)){
            return redirect()->to(route('quote.show', ['id' => $id]).'#QuoteLines')->with('success', 'Successfully created new task');
        }
    }

    public function delete($id_type, $id_page, $id_task)
    {
        $Task =  Task::find($id_task);
        $Task->delete();

        if($id_type == 'products_id'){
            return redirect()->route('products.show', ['id' => $id_page])->with('success', 'Successfully delete task #'. $id_task);
        }
        elseif($id_type == 'quote_lines_id'){
            return redirect()->route('quote.show', ['id' => $id_page])->with('success', 'Successfully delete task #'. $id_task);
        }
    }
}
