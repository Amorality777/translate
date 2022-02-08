<?php

namespace Tests\Unit\Helpers\JobHelpers;

use App\Helpers\JobHelpers\PostProcessing;
use App\Models\Process;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostProcessingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверка успешной постобработки с 1 процессом.
     */
    function testSuccessProcess()
    {
        $task = Task::factory()
            ->has(Process::factory()->state([
                'status' => Task::COMPLETED,
                'translation' => 'some translate'
            ]))
            ->create();
        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::COMPLETED]);
        $this->assertDatabaseHas('processes', [
            'translation' => 'some translate',
            'revert_translation' => '',
            'status' => Task::COMPLETED,
        ]);
    }

    /**
     * Проверка успешной постобработки с несколькими процессами.
     */
    function testSuccessProcesses()
    {
        $count = 4;
        $task = Task::factory()
            ->has(Process::factory()
                ->count($count)
                ->state(new Sequence(
                    fn($sequence) => [
                        'status' => Task::COMPLETED,
                        'translation' => "$sequence->index translation text",
                        'revert_translation' => "$sequence->index revert translation text",
                    ],
                )))
            ->create();
        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::COMPLETED]);
        for ($i = 0; $i < $count; $i++) {
            $this->assertDatabaseHas('processes', [
                'translation' => "$i translation text",
                'revert_translation' => "$i revert translation text",
                'status' => Task::COMPLETED,
            ]);
        }
    }

    /**
     * Проверка успешной постобработки c одним процессом и несколькими подпроцессами.
     */
    function testSuccessPostProcess()
    {
        $task = Task::factory()
            ->has(Process::factory()
                ->state(['status' => Task::IN_PROGRESS]))
            ->create();

        Process::factory()
            ->count(3)
            ->state(new Sequence(
                fn($sequence) => [
                    'parent_id' => 1,
                    'subprocess' => true,
                    'status' => Task::COMPLETED,
                    'translation' => "$sequence->index text.",
                    'revert_translation' => "$sequence->index revert."
                ]))
            ->create();

        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::COMPLETED]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => '0 text. 1 text. 2 text.',
            'revert_translation' => '0 revert. 1 revert. 2 revert.',
            'status' => Task::COMPLETED,
        ]);

    }

    /**
     * Проверка успешной постобработки c несколькими процессами и несколькими подпроцессами.
     */
    function testSuccessPostProcesses()
    {
        $task = Task::factory()
            ->has(Process::factory()
                ->count(2)
                ->state(new Sequence(
                    [
                        'status' => Task::IN_PROGRESS
                    ],
                    [
                        'status' => Task::COMPLETED,
                        'translation' => 'simple translate.',
                        'revert_translation' => 'simple revert.'
                    ],
                )))
            ->create();
        $parentProcessId = Process::where(['status'=>Task::IN_PROGRESS])->first()->id;
        Process::factory()
            ->count(3)
            ->state(new Sequence(
                fn($sequence) => [
                    'parent_id' => $parentProcessId,
                    'subprocess' => true,
                    'status' => Task::COMPLETED,
                    'translation' => "$sequence->index text.",
                    'revert_translation' => "$sequence->index revert."
                ]))
            ->create();

        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::COMPLETED]);
        $this->assertDatabaseHas('processes', [
            'id' => $parentProcessId,
            'subprocess' => false,
            'translation' => '0 text. 1 text. 2 text.',
            'revert_translation' => '0 revert. 1 revert. 2 revert.',
            'status' => Task::COMPLETED,
        ]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => 'simple translate.',
            'revert_translation' => 'simple revert.',
            'status' => Task::COMPLETED,
        ]);

    }


    /**
     * Проверка постановки статуса Failure основной задаче, при одном провальном процессе.
     */
    function testFailureProcess()
    {
        $task = Task::factory()
            ->has(Process::factory()->state([
                'status' => Task::FAILURE
            ]))
            ->create();
        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::FAILURE]);
        $this->assertDatabaseHas('processes', [
            'translation' => '',
            'revert_translation' => '',
            'status' => Task::FAILURE,
        ]);
    }

    /**
     * Проверка сохранения перевода у проваленного процесса без подпроцессов.
     */
    function testFailureProcessWithText()
    {
        $task = Task::factory()
            ->has(Process::factory()->state([
                'status' => Task::FAILURE,
                'translation' => 'translation',
                'revert_translation' => 'revert translation',
            ]))
            ->create();
        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::FAILURE]);
        $this->assertDatabaseHas('processes', [
            'translation' => 'translation',
            'revert_translation' => 'revert translation',
            'status' => Task::FAILURE,
        ]);
    }

    /**
     * Проверка постановки статуса Failure основной задаче, при одном провальном процессе и одном успешном.
     */
    function testFailureProcesses()
    {
        $task = Task::factory()
            ->has(Process::factory()
                ->count(2)
                ->state(new Sequence(
                    [
                        'status' => Task::COMPLETED,
                        'translation' => 'success translate',
                        'revert_translation' => 'success revert translation'
                    ],
                    [
                        'status' => Task::FAILURE,
                        'translation' => 'translation'
                    ]
                ))
            )
            ->create();

        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::FAILURE]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => 'success translate',
            'revert_translation' => 'success revert translation',
            'status' => Task::COMPLETED,
        ]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => 'translation',
            'status' => Task::FAILURE,
        ]);
    }

    /**
     * Проверка логики с провеленным подпроцессом.
     */
    function testFailureSubProcess()
    {
        $task = Task::factory()
            ->has(Process::factory()->state([
                'status' => Task::IN_PROGRESS,
            ]))
            ->create();
        $processId = $task->processes()->first()->id;
        Process::factory()
            ->count(3)
            ->state([
                'subprocess' => true,
                'parent_id' => $processId,
                'status' => Task::COMPLETED,
                'translation' => 'some translation sentence.',
                'revert_translation' => 'some revert translation sentence.'
            ])
            ->create();
        Process::factory()
            ->state([
                'subprocess' => true,
                'parent_id' => $processId,
                'status' => Task::FAILURE,
                'translation' => 'some failure translation sentence.',
                'revert_translation' => 'some failure revert translation sentence.'
            ])
            ->create();

        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::FAILURE]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => '',
            'revert_translation' => '',
            'status' => Task::FAILURE,
        ]);
    }

    /**
     * Проверка логики с успешным и провеленным подпроцессами.
     */
    function testFailureSubProcesses()
    {
        $text = 'some translation sentence.';
        $count = 3;
        $task = Task::factory()
            ->has(Process::factory()
                ->count(2)
                ->state([
                    'status' => Task::IN_PROGRESS,
                ]))
            ->create();
        $failureProcessId = $task->processes()->first()->id;
        $successProcessId = $task->processes()->latest('id')->first()->id;
        Process::factory()
            ->count(3)
            ->state([
                'subprocess' => true,
                'parent_id' => $failureProcessId,
                'status' => Task::COMPLETED,
                'translation' => $text,
                'revert_translation' => $text
            ])
            ->create();
        Process::factory()
            ->state([
                'subprocess' => true,
                'parent_id' => $failureProcessId,
                'status' => Task::FAILURE,
                'translation' => $text,
                'revert_translation' => $text
            ])
            ->create();
        Process::factory()
            ->count($count)
            ->state([
                'subprocess' => true,
                'parent_id' => $successProcessId,
                'status' => Task::COMPLETED,
                'translation' => $text,
                'revert_translation' => $text
            ])
            ->create();
        PostProcessing::run($task->id);
        $this->assertDatabaseHas('tasks', ['status' => Task::FAILURE]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'translation' => '',
            'revert_translation' => '',
            'status' => Task::FAILURE,
        ]);
        $this->assertDatabaseHas('processes', [
            'subprocess' => false,
            'status' => Task::COMPLETED,
            'translation' => trim(str_repeat(" $text", $count))
        ]);
    }
}
