<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexFormRequest;
use App\Models\Task;
use Illuminate\Support\Arr;
use Kodeks\Responses\Success;
use App\Jobs\TranslateFactoryJob;
use App\Services\TranslateManager;
use App\Http\Requests\TaskFormRequest;
use Illuminate\Contracts\Support\Responsable;

class TaskController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param IndexFormRequest $request
     * @return Responsable
     */
    public function index(IndexFormRequest $request): Responsable
    {
        return new Success(Task::with('processes')->filter($request->validated())->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TaskFormRequest $request
     * @return Responsable
     */
    public function store(TaskFormRequest $request): Responsable
    {
        $data = $request->safe()->except('services');
        $services = Arr::wrap($request->get('services', TranslateManager::SERVICES));
        $task = Task::create($data);
        $task->processes()->createMany(array_map(fn($item) => ['parent_id' => $task->id, 'service' => $item], $services));

        TranslateFactoryJob::dispatch($task->id);

        return new Success(['task_id' => $task->id], 200);
    }

    /**
     * Show resource in storage.
     *
     * @param int $id
     * @return Responsable
     */
    public function show(int $id): Responsable
    {
        return new Success(Task::with(['processes' => function ($query) {
            $query->where('subprocess', false);
        }])->findOrFail($id));
    }
}
