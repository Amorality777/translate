<?php

namespace App\Jobs;

use Throwable;
use App\Models\Task;
use App\Models\Process;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Services\BaseTranslate;
use App\Services\TranslateManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TranslateJob implements ShouldQueue
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
    public int $backoff = 10;

    /**
     * The process instance.
     *
     * @var int
     */
    protected int $processId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $processId)
    {
        $this->processId = $processId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }
        $process = Process::find($this->processId);
        $process->update(['status' => Task::IN_PROGRESS]);

        $translateService = TranslateManager::getTranslateService($process->service);
        $task = $process->task;
        $text = $process->text ?? $task->text;
        /** @var BaseTranslate $translator */
        $translator = new $translateService($text, $task->from, $task->to);
        if (!$process->text) {
            if ($translator->needSplit()) {
                $data = [
                    'parent_id' => $process->id,
                    'subprocess' => true,
                    'service' => $process->service,
                ];
                $subprocesses = [];
                $split = $translator->splitText();
                for (; $split->valid(); $split->next()) {
                    $data['text'] = $split->current();
                    $subprocess = Process::create($data);
                    $subprocesses[] = new TranslateJob($subprocess->id);
                }
                $this->batch()->add($subprocesses);
                return;
            } else {
                $process->update(['text' => $text]);
            }
        }
        if (!$process->translation) {
            $process->update(['translation' => $translator->getTranslation()]);
        }

        if ($task->revert and !$process->revert_translation) {
            $process->update(['revert_translation' => $translator->getRevertTranslation($process->translation)]);
        }
        $process->update(['status' => Task::COMPLETED]);
    }

    public function failed(Throwable $exception)
    {
        Process::find($this->processId)->update(['status' => Task::FAILURE]);
    }
}
