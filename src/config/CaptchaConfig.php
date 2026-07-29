<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\config;

/**
 * Иммутабельный value-object настроек интеграции Yandex SmartCaptcha.
 *
 * Собирается в {@see \Besnovatyj\YandexSmartCaptcha\Bootstrap} из params модуля
 * (которые, в свою очередь, наполняет config-модуль).
 */
final readonly class CaptchaConfig
{
    public function __construct(
        /** Клиентский ключ (sitekey, ysc1_) — публичный, для виджета. */
        public string $clientKey,
        /** Серверный ключ (secret, ysc2_) — приватный, для /validate. */
        public string $serverKey,
        /** Endpoint серверной проверки токена. */
        public string $verifyUrl = 'https://smartcaptcha.yandexcloud.net/validate',
        /** URL JS-скрипта капчи. */
        public string $jsUrl = 'https://smartcaptcha.cloud.yandex.ru/captcha.js',
        /** Язык виджета по умолчанию (hl). */
        public string $defaultLanguage = 'ru',
        /** Таймаут запроса к /validate, сек. */
        public float $timeout = 2.0,
        /** Пропускать пользователя при транспортной/HTTP-ошибке /validate. */
        public bool $failOpen = true,
        /** Передавать IP пользователя в /validate. */
        public bool $sendIp = true,
        /** Тестовый режим виджета (test=true). */
        public bool $testMode = false,
        /** Ожидаемый host для доп. сверки ответа /validate ('' — не сверять). */
        public string $expectedHost = '',
    ) {
    }
}
