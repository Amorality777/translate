<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessedFormRequest;
use App\Models\Task;
use Illuminate\Contracts\Support\Responsable;
use Kodeks\Responses\Success;

class ProcessedController extends Controller
{

    /**
     * Update resource in storage.
     *
     * @param ProcessedFormRequest $request
     * @return Responsable
     */
    public function store(ProcessedFormRequest $request): Responsable
    {
        return new Success(['updated' => Task::whereIn('id', $request['tasks'])->where('processed', false)
            ->update(['processed' => true])]);
    }
}
