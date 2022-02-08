<?php

namespace App\Jobs;

use Throwable;
use App\Models\Task;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Helpers\JobHelpers\PostProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\YandexTranslate\YandexTranslateService;

class TranslateFactoryJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 30;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 3;

    /**
     * The task instance.
     *
     * @var int
     */
    protected int $taskId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public const failureStatuses = [Task::FAILURE, Task::IN_PROGRESS];

    /**
     * Execute the job.
     *
     * @throws Throwable
     * @return void
     */
    public function handle()
    {
        $task = Task::find($this->taskId);
        $task->update([
            'status' => Task::IN_PROGRESS,
            'from' => $task->from ?? SOME_SERVICE::detectLang($task->text),
        ]);
        $jobs = $task->processes->map(fn ($item) => new TranslateJob($item->id));
        $taskId = $this->taskId;

        Bus::batch($jobs)
            ->allowFailures()
            ->finally(function (Batch $batch) use ($taskId) {
                PostProcessing::run($taskId);
            })
            ->dispatch();
    }

    public function failed(Throwable $exception)
    {
        Task::find($this->taskId)->update(['status' => Task::FAILURE]);
    }
}
