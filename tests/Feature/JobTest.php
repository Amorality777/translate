<?php

namespace Tests\Feature;

use App\Jobs\TranslateFactoryJob;
use App\Jobs\TranslateJob;
use App\Models\Process;
use App\Models\Task;
use App\Services\TranslateManager;
use App\Services\YandexTranslate\SessionManager;
use App\Services\YandexTranslate\YandexTranslateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверка создания вложенных задач переводов, для созданной родительской задачи.
     */
    public function testGenerateProcessesFromJob()
    {
        Http::fake();
        Queue::fake();

        $task = Task::factory()
            ->has(Process::factory()->state(['service' => TranslateManager::SOME_SERVICE])->count(3))
            ->create();

        (new TranslateFactoryJob($task->id))->handle();

        $this->assertDatabaseHas('tasks', [
           'id' => $task->id,
           'status' => Task::IN_PROGRESS,
           'from' => $task->from,
        ]);

        Queue::assertPushed(TranslateJob::class, 3);
    }

    /**
     * Проверка определения языка источника, если он не был задан изначально
     */
    public function testDetectLangIfNotPresent()
    {
        $detectedLang = 'some';
        Http::fake([
            SOME_SERVICE::API_URL => Http::response(['code' => 200, 'lang' => $detectedLang]),
        ]);
        Queue::fake();

        $task = Task::factory()->create(['from' => null]);

        (new TranslateFactoryJob($task->id))->handle();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::IN_PROGRESS,
            'from' => $detectedLang,
        ]);
    }
}
