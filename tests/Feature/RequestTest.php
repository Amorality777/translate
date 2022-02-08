<?php

namespace Tests\Feature;

use App\Models\Process;
use App\Models\Task;
use Tests\TestCase;
use App\Jobs\TranslateFactoryJob;
use App\Services\TranslateManager;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /**
     * Тест отправки запроса на перевод.
     * Проверяем создание задачи перевода,
     * и вложенных процессов для обоих переводчиков если не было передано указание на конкретного.
     */
    public function testSendTranslateRequest()
    {
        $data = [
            'text' => 'some text',
        ];
        $resultData = [
            'data' => ['task_id' => 1],
        ];

        $response = $this->postJson(route('request.store'), $data);
        $response
            ->assertStatus(200)
            ->assertJsonFragment($resultData);

        $this->assertDatabaseHas('tasks', [
            'id' => 1,
            'text' => 'some text',
        ]);

        $this->assertDatabaseCount('processes', 2);
        $this->assertDatabaseHas('processes', [
            'parent_id' => 1,
            'service' => TranslateManager::SOME_SERVICE,
        ]);
        $this->assertDatabaseHas('processes', [
            'parent_id' => 1,
            'service' => TranslateManager::SOME_SERVICE,
        ]);

        Queue::assertPushed(TranslateFactoryJob::class);
    }

    /**
     * Проверка работы валидации.
     * При наличии ошибки валидации ожидается возврат ошибки в стандартном формате
     * и не выполнение логики в контроллере.
     */
    public function testValidationFailure()
    {
        $data = [
            'services' => 'not valid service',
            'revert' => 'not bool value',
        ];

        $response = $this->postJson(route('request.store'), $data);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text', 'revert', 'services']);

        $this->assertDatabaseCount('tasks', 0);
        Queue::assertNothingPushed();
    }

    /**
     * Проверка создания задачи перевода с конкретными параметрами.
     * При указании сервиса дял перевода - должен создаться одна вложенная задача для конкретного переводчика.
     */
    public function testCreateOneProcessByRequest()
    {
        $text = 'test text';
        $from = 'en';
        $data = [
            'text' => $text,
            'from' => $from,
            'services' => TranslateManager::SOME_SERVICE,
        ];
        $this->postJson(route('request.store'), $data)->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => 1,
            'text' => $text,
            'from' => $from,
        ]);

        $this->assertDatabaseCount('processes', 1);
        $this->assertDatabaseHas('processes', [
            'parent_id' => 1,
            'service' => TranslateManager::SOME_SERVICE,
        ]);
    }

    /**
     * Проверка получения информации о состоянии перевода.
     * Ожидается получение информации об исходной задаче, и вложенных процессах.
     * При создании одной вложенной задачи - ожидается возврат этой одной.
     */
    public function testGetProcessInfo()
    {
        $taskData = [
            'text' => 'test text',
            'from' => 'en',
            'to' => 'ru',
            'revert' => true,
        ];

        $processData = [
            'service' => TranslateManager::SOME_SERVICE,
            'translation' => '',
            'revert_translation' => '',
        ];

        $task = Task::factory()
            ->has(Process::factory()->state($processData))
            ->create($taskData);

        $response = $this->getJson(route('request.show', $task->id));

        $response->assertOk()
            ->assertJsonFragment($taskData)
            ->assertJsonFragment($processData);
        $response->assertJsonCount(1, 'data.processes');
        $this->assertEquals(Task::CREATED, $response->json('data.status'));
    }
}
