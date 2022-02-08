<?php

namespace App\Support\TextSplitters;

use Generator;

abstract class BaseSplitter
{
    public int $limit;

    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * Основной метод разбиентия текста.
     */
    abstract public function getSplitText($text): Generator;
}
