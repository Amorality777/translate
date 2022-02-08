<?php

namespace App\Support\TextSplitters;

use Generator;

class SimpleTextSplitter extends BaseSplitter
{
    private static string $pattern = '/(?<=[\.\?\!])\s/';

    /**
     * Основной метод разбиентия текста.
     */
    public function getSplitText($text): Generator
    {
        if (mb_strlen($text) <= $this->limit) {
            yield $text;

            return;
        }

        $sentences = $this->getSentences($text);
        $result = $sentences->current();
        $sentences->next();
        for (; $result; $sentences->next()) {
            $lenResult = mb_strlen($result);
            if ($lenResult > $this->limit) {
                yield from $this->getSentenceSplit($result);
                $result = $sentences->current();

                continue;
            }
            $sentence = $sentences->current();
            $lenNext = mb_strlen($sentence);

            if ($lenResult + $lenNext < $this->limit) {
                $result = implode(' ', [$result, $sentence]);
            } else {
                yield trim($result);
                $result = $sentence;
            }
        }
    }

    /**
     * Возвращает генератор разделенного текста.
     */
    private function getSentences(string $text): Generator
    {
        // Обработка китайской точки.
        $preparedText = str_replace('。', '. ', $text);
        if ($preparedText !== $text) {
            $chinese = true;
            $text = $preparedText;
        }
        foreach (preg_split(self::$pattern, $text) as $sentence) {
            if ($sentence) {
                if (isset($chinese)) {
                    $sentence = str_replace('.', '。', $sentence);
                }
                yield $sentence;
            }
        }
    }

    /**
     * Возвращает генератор разделенного предложения.
     */
    public function getSentenceSplit(string $sentence): Generator
    {
        $words = explode(' ', $sentence);
        $result = '';
        foreach ($words as $word) {
            $prepare = "$result $word";
            if (mb_strlen($prepare) > $this->limit) {
                yield trim($result);
                $prepare = $word;
            }
            $result = $prepare;
        }
        yield trim($result);
    }
}
