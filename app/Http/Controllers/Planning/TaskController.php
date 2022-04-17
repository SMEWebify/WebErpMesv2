<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OrderLines;
use App\Http\Requests\Planning\StoreTaskRequest;
use App\Http\Requests\Planning\UpdateTaskRequest;
use App\Models\Planning\Status;

class TaskController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }
        return view('workflow/task-index', [
            'Factory' => $Factory
        ]);
    }

    /**
     * @return View
     */
    public function kanban()
    {
        $tasks = Status::orderBy('order', 'ASC')->with('tasks.OrderLines.order')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }
        return view('workflow/kanban-index', compact('tasks', 'Factory'));
    }

    /**
     * @param Request $request
     * @return View
     */
    public function sync(Request $request)
    {
        $this->validate(request(), [
            'columns' => ['required', 'array']
        ]);

        foreach ($request->columns as $status) {
            foreach ($status['tasks'] as $i => $task) {
                if ($task['status_id'] !== $status['id']) {
                    Task::find($task['id'])->update(['status_id' => $status['id']]);
                }
            }
        }

        return $request->status()->with('tasks')->get();
    }

    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreTaskRequest $request, $id)
    {
        $Task = Task::create($request->only('label', 
                                            'ORDER',
                                            'quote_lines_id',
                                            'order_lines_id',
                                            'products_id',
                                            'methods_services_id', 
                                            'component_id',
                                            'seting_time', 
                                            'unit_time', 
                                            'remaining_time', 
                                            'advancement', 
                                            'status_id', 
                                            'type',
                                            'delay',
                                            'qty',
                                            'qty_init',
                                            'qty_aviable',
                                            'unit_cost',
                                            'unit_price',
                                            'methods_units_id',
                                            'x_size', 
                                            'y_size', 
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
            return redirect()->to(route('quotes.show', ['id' => $id]).'#QuoteLines')->with('success', 'Successfully created new task');
        }
        elseif(isset($request->order_lines_id)){
            $OrderLine = OrderLines::find($request->order_lines_id);
            $OrderLine->tasks_status = 2;
            $OrderLine->save();
            return redirect()->to(route('orders.show', ['id' => $id]).'#OrderLines')->with('success', 'Successfully created new task');
        }
    }

    /**
     * @param $id_type, $id_page ,$id_task
     * @return View
     */
    public function delete($id_type, $id_page, $id_task)
    {
        $Task =  Task::find($id_task);
        $Task->delete();

        if($id_type == 'products_id'){
            return redirect()->route('products.show', ['id' => $id_page])->with('success', 'Successfully delete task #'. $id_task);
        }
        elseif($id_type == 'quote_lines_id'){
            return redirect()->to(route('quotes.show', ['id' => $id_page]).'#QuoteLines')->with('success', 'Successfully delete task #'. $id_task);
        }
        elseif($id_type == 'order_lines_id'){
            return redirect()->to(route('orders.show', ['id' => $id_page]).'#OrderLines')->with('success', 'Successfully delete task #'. $id_task);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update(UpdateTaskRequest $request, $id)
    {
        $task = Task::find($request->id);
        $task->label=$request->label;
        $task->ORDER=$request->ORDER;
        $task->products_id=$request->products_id;
        $task->methods_services_id=$request->methods_services_id;
        $task->component_id=$request->component_id;
        $task->seting_time=$request->seting_time;
        $task->unit_time=$request->unit_time;
        $task->type=$request->type;
        $task->qty=$request->qty;
        $task->unit_cost=$request->unit_cost;
        $task->unit_price=$request->unit_price;
        $task->methods_units_id=$request->methods_units_id;
        $task->save();
        
        if(isset($request->products_id)){
            return redirect()->route('products.show', ['id' => $id])->with('success', 'Successfully updated task');
        }
        elseif(isset($request->quote_lines_id)){
            return redirect()->to(route('quotes.show', ['id' => $id]).'#QuoteLines')->with('success', 'Successfully updated task');
        }
        elseif(isset($request->order_lines_id)){
            return redirect()->to(route('orders.show', ['id' => $id]).'#OrderLines')->with('success', 'Successfully updated task');
        }
    }
}
