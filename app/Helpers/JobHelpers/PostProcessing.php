<?php

namespace App\Helpers\JobHelpers;

use App\Models\Task;
use App\Models\Process;

class PostProcessing
{
    public const failureStatuses = [Task::FAILURE, Task::IN_PROGRESS];

    /**
     * Синхронизация статусов задачи, обработка переводов.
     *
     * @return void
     */
    public static function run(int $taskId)
    {
        $task = Task::find($taskId);
        foreach ($task->processes as $process) {
            if (!$process->translation) {
                static::joinText($process);
                $process->refresh();
            }
            if (in_array($process->status, static::failureStatuses)) {
                $taskStatus = Task::FAILURE;
            }
        }
        $task->update(['status' => $taskStatus ?? Task::COMPLETED]);
    }

    /**
     * Приведение статуса процесса и склеивание переводов.
     *
     * @return void
     */
    protected static function joinText(Process $process)
    {
        if ($process->subprocesses()->doesntExist()) {
            return;
        }
        $translation = '';
        $revert_translation = '';
        foreach ($process->subprocesses as $subprocess) {
            if (in_array($subprocess->status, static::failureStatuses)) {
                $processStatus = Task::FAILURE;
                $translation = '';
                $revert_translation = '';
                break;
            }
            $translation .= " $subprocess->translation";
            $revert_translation .= " $subprocess->revert_translation";
        }
        $process->update([
            'translation' => trim($translation),
            'revert_translation' => trim($revert_translation),
            'status' => $processStatus ?? Task::COMPLETED,
        ]);
    }
}
