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
        $tasks = Task::all();
        return TaskResource::collection($tasks);
    }

    public function show(Task $task)
    {
        return new TaskResource($task);
    }
}
