<?php

namespace App\Http\Controllers\Planning;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskActivities;
use App\Models\Products\Products;

class TaskController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {

        return view('workflow/task-index');
    }

        /**
     * @param  $id_type, $id_page, $id_line
     * @return View
     */
    public function manage($id_type, $id_page, $id_line)
    {
        if($id_type == 'products_id'){
            $LineInfo = SubAssembly::findOrFail($id_line); //https://github.com/SMEWebify/WebErpMesv2/issues/334
            $Document = Products::findOrFail($id_page);
        }
        elseif($id_type == 'quote_lines_id'){
            $Document = Quotes::findOrFail($id_page);
            $LineInfo = QuoteLines::findOrFail($id_line);
        }
        elseif($id_type == 'order_lines_id'){
            $Document = Orders::findOrFail($id_page);
            $LineInfo = OrderLines::findOrFail($id_line);
        }
        elseif($id_type == 'sub_assembly_id'){ //https://github.com/SMEWebify/WebErpMesv2/issues/334
            $LineInfo = SubAssembly::findOrFail($id_line);
            if($LineInfo->quote_lines_id){
                $Document = Quotes::findOrFail($id_page);
            }
            elseif($LineInfo->order_lines_id){
                $Document = Orders::findOrFail($id_page);
            }
            elseif($LineInfo->products_id){
                $Document = Products::findOrFail($id_page);
                $Document->statu = 1;
            }
        }

        return view('workflow/task-manage', compact('Document','LineInfo', 'id_type', 'id_page', 'id_line'));
    }

    /**
     * @return View
     */
    public function kanban()
    {
        $tasks = Status::orderBy('order', 'ASC')->with('tasks.OrderLines.order')->with('tasks.service')->get();
        return view('workflow/kanban-index', compact('tasks'));
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

        $StatusInProgessId = Status::select('id')->where('title', 'In Progress')->first();
        $StatusFinishId = Status::select('id')->where('title', 'Finished')->first();

        foreach ($request->columns as $status) {
            foreach ($status['tasks'] as $i => $task) {
                if ($task['status_id'] !== $status['id']) {
                    Task::find($task['id'])->update(['status_id' => $status['id']]);

                    if($status['id'] == $StatusInProgessId->id){
                         // Create Line
                        TaskActivities::create([
                            'task_id'=> $task['id'],
                            'user_id'=> Auth::user()->id,
                            'type'=>'1',
                            'timestamp' =>Carbon::now(),
                            'comment'=>'',
                        ]);
                    }
                    elseif($status['id'] == $StatusFinishId->id){
                        // Create Line
                        TaskActivities::create([
                            'task_id'=> $task['id'],
                            'user_id'=> Auth::user()->id,
                            'type'=>'3',
                            'timestamp' =>Carbon::now(),
                            'comment'=>'',
                        ]);
                    }

                    event(new TaskChangeStatu($task['id']));
                }
            }
        }

        $tasks = Status::orderBy('order', 'ASC')->with('tasks.OrderLines.order')->with('tasks.service')->get();
        return  $tasks;
    }

    /**
     * @return View
     */
    public function statu(Request $request)
    {
        return view('workflow/task-statu', ['TaskId' => $request->id]);
    }
}
