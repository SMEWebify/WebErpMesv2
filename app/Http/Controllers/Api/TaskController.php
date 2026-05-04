<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\CompanieResource;

class TaskController  extends Controller
{
    public function index()
    {
        return TaskResource::collection(Task::paginate(50));
    }

    public function show(Task $task)
    {
        return new TaskResource($task);
    }
}
