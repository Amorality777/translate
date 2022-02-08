<?php

namespace App\Support\Requests;

use function config;
use App\Exceptions\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseRequest
{
    public const GET = 'GET';

    public const POST = 'POST';

    /**
     * Наименование сервиса (Для распределения прокси).
     */
    public string $serviceName;

    private ?string $proxy;

    public string $method = self::POST;

    public bool $asForm = true;

    public string $errorMessage = 'Невалидный ответ';

    /**
     * Установка наименования сервиса.
     */
    abstract public function setServiceName(): void;

    public function __construct()
    {
        $this->setServiceName();
        $this->proxy = $this->getProxy();
    }

    /**
     * @throws RequestException
     */
    public static function execute(string $url, array $params = []): Response
    {
        $self = new static();

        return $self->get($url, $params);
    }

    /**
     * Выполнение запроса и его проверка.
     *
     * @throws RequestException
     */
    public function get(string $url, array $params = []): Response
    {
        $response = $this::makeRequest($url, $params);
        if ($response->ok()) {
            return $response;
        }
        static::makeFailure($response);
    }

    /**
     * Выполнение запроса.
     * @throws RequestException
     */
    protected function makeRequest(string $url, array $params): Response
    {
        $pendingRequest = Http::withHeaders($this->getHeaders())
            ->withOptions($this->getOptions());
        if ($this->asForm) {
            $pendingRequest = $pendingRequest->asForm();
        }

        if ($this->method == self::GET) {
            return $pendingRequest->get($url, $params);
        } elseif ($this->method == self::POST) {
            return $pendingRequest->post($url, $params);
        } else {
            throw new RequestException("Неизвестный метод $this->method");
        }
    }

    /**
     * Обработка не валидного запроса.
     *
     * @throws RequestException
     */
    private function makeFailure(Response $response)
    {
        $this->makeFailureAction();
        $this->updateBlacklist($response->toException());
        $status = $response->status();

        throw new RequestException("$this->errorMessage $status");
    }

    /**
     * Метод для добавления логики при неуспешном запросе.
     */
    protected function makeFailureAction(): void
    {
    }

    /**
     * Сохранение невалидных пркоси в черный список.
     */
    private function updateBlacklist(\Illuminate\Http\Client\RequestException $exception)
    {
        print_r("invalid proxy: $this->proxy for service: $this->serviceName");
        // TODO Добавить отправку информации в сервис прокси о невалидном запросе p.s.: не при всех ошибках.
    }

    /**
     * Получение прокси.
     */
    public function getProxy(): ?string
    {
        if (config('request.directRequest')) {
            return null;
        }

        return ''; // TODO Реализовать получение реального прокси.
    }

    /**
     * Получение юзер агента.
     */
    public function getUserAgent(): string
    {
        return config('request.userAgent');
    }

    /**
     * Получение всех заголовков запроса.
     */
    protected function getHeaders(): array
    {
        $headers = $this->getCustomHeaders();
        $headers['User-Agent'] = $this->getUserAgent();

        return $headers;
    }

    /**
     * Метод для подмешивания заголовков.
     */
    protected function getCustomHeaders(): array
    {
        return [];
    }

    /**
     * Получение всех опций запроса.
     */
    protected function getOptions(): array
    {
        $options = $this->getCustomOptions();
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }

        return $options;
    }

    /**
     * Метод для подмешивания опций запроса.
     */
    protected function getCustomOptions(): array
    {
        return [];
    }
}
