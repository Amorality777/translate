<?php

namespace App\Dto;

abstract class BaseDto
{
    public array $params;

    /**
     * Инициализация сессии.
     *
     * @param mixed ...$dynamicParams
     * @return static
     */
    public static function init(...$dynamicParams): static
    {
        $class = new static();
        $class->params = $class->getParams($dynamicParams);

        return $class;
    }

    /**
     * Получение параметров запроса.
     *
     * @param array|null $dynamicParams
     * @return array
     */
    abstract protected function getParams(array $dynamicParams = null): array;

    /**
     * Добавлене параметров запроса.
     *
     * @param string $key
     * @param string|array $value
     * @return void
     */
    public function addParams(string $key, string|array $value)
    {
        $this->params[$key] = $value;
    }
}
