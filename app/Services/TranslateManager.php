<?php

namespace App\Services;

use App\Services\GoogleTranslate\GoogleTranslateService;
use App\Services\YandexTranslate\YandexTranslateService;

class TranslateManager
{
    public const SOME_SERVICE = 'someService';


    public const SERVICES = [self::SOME_SERVICE];

    private const MATCH = [
        self::SOME_SERVICE => SOME_SERVICE::class,
    ];

    public static function getTranslateService(string $service): string
    {
        return self::MATCH[$service];
    }
}
