<?php

namespace App\Services;

use Generator;
use App\Support\TextSplitters\BaseSplitter;
use App\Support\TextSplitters\SimpleTextSplitter;

abstract class BaseTranslate
{
    public string $splitter = SimpleTextSplitter::class;

    public int $limit;

    public const LANGUAGES = [
        'af' => 'Африкаанс',
        'am' => 'Амхарский',
        'ar' => 'Арабский',
        'az' => 'Азербайджанский',
        'ba' => 'Башкирский',
        'be' => 'Белорусский',
        'bg' => 'Болгарский',
        'bn' => 'Бенгальский',
        'bs' => 'Боснийский',
        'ca' => 'Каталанский',
        'ceb' => 'Себуанский',
        'cs' => 'Чешский',
        'cv' => 'Чувашский',
        'cy' => 'Валлийский',
        'da' => 'Датский',
        'de' => 'Немецкий',
        'el' => 'Греческий',
        'en' => 'Английский',
        'eo' => 'Эсперанто',
        'es' => 'Испанский',
        'et' => 'Эстонский',
        'eu' => 'Баскский',
        'fa' => 'Персидский',
        'fi' => 'Финский',
        'fr' => 'Французский',
        'ga' => 'Ирландский',
        'gd' => 'Шотландский (гэльский)',
        'gl' => 'Галисийский',
        'gu' => 'Гуджарати',
        'he' => 'Иврит',
        'hi' => 'Хинди',
        'hr' => 'Хорватский',
        'ht' => 'Гаитянский',
        'hu' => 'Венгерский',
        'hy' => 'Армянский',
        'id' => 'Индонезийский',
        'is' => 'Исландский',
        'it' => 'Итальянский',
        'ja' => 'Японский',
        'jv' => 'Яванский',
        'ka' => 'Грузинский',
        'kk' => 'Казахский',
        'km' => 'Кхмерский',
        'kn' => 'Каннада',
        'ko' => 'Корейский',
        'ky' => 'Киргизский',
        'la' => 'Латынь',
        'lb' => 'Люксембургский',
        'lo' => 'Лаосский',
        'lt' => 'Литовский',
        'lv' => 'Латышский',
        'mg' => 'Малагасийский',
        'mhr' => 'Марийский',
        'mi' => 'Маори',
        'mk' => 'Македонский',
        'ml' => 'Малаялам',
        'mn' => 'Монгольский',
        'mr' => 'Маратхи',
        'mrj' => 'Горномарийский',
        'ms' => 'Малайский',
        'mt' => 'Мальтийский',
        'my' => 'Бирманский',
        'ne' => 'Непальский',
        'nl' => 'Нидерландский',
        'no' => 'Норвежский',
        'pa' => 'Панджаби',
        'pap' => 'Папьяменто',
        'pl' => 'Польский',
        'pt' => 'Португальский',
        'ro' => 'Румынский',
        'ru' => 'Русский',
        'sah' => 'Якутский',
        'si' => 'Сингальский',
        'sk' => 'Словацкий',
        'sl' => 'Словенский',
        'sq' => 'Албанский',
        'sr' => 'Сербский',
        'su' => 'Сунданский',
        'sv' => 'Шведский',
        'sw' => 'Суахили',
        'ta' => 'Тамильский',
        'te' => 'Телугу',
        'tg' => 'Таджикский',
        'th' => 'Тайский',
        'tl' => 'Тагальский',
        'tr' => 'Турецкий',
        'tt' => 'Татарский',
        'udm' => 'Удмуртский',
        'uk' => 'Украинский',
        'ur' => 'Урду',
        'uz' => 'Узбекский',
        'vi' => 'Вьетнамский',
        'xh' => 'Коса',
        'yi' => 'Идиш',
        'zh' => 'Китайский',
        'zu' => 'Зулу',
    ];

    private string $text;

    private string $from;

    private string $to;

    public function __construct(string $text, string $from, string $to)
    {
        $this->setLimit();
        $this->text = $text;
        $this->from = $from;
        $this->to = $to;
    }

    abstract protected function setLimit();

    abstract public function translate(string $text, string $from, string $to): string;

    /**
     * Получение перевода.
     *
     * @return string
     */
    public function getTranslation(): string
    {
        return $this::translate($this->text, $this->from, $this->to);
    }

    /**
     * Получение обратного перевода.
     *
     * @param string $text
     * @return string
     */
    public function getRevertTranslation(string $text): string
    {
        return $this::translate($text, $this->to, $this->from);
    }

    public function needSplit(): bool
    {
        return mb_strlen($this->text) > $this->limit;
    }

    /**
     * Возвращает генератор резделенного текста по заданному лимиту.
     */
    public function splitText(): Generator
    {
        /** @var $splitter_class BaseSplitter */
        $splitter_class = new $this->splitter($this->limit);
        yield from $splitter_class->getSplitText($this->text);
    }
}
