<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Базовая конфигурация модуля YandexSmartCaptcha.
 *
 * Значения params — дефолты, которые перекрываются config-модулем (см. config/options.php).
 * Здесь они объявлены, чтобы params всегда существовали ещё до применения настроек из БД/файла.
 */
return [
    'id' => 'YandexSmartCaptcha',
    'params' => [
        'iconClass' => 'bi bi-shield-check',

        'directories' => false,

        // Клиентский (публичный) ключ капчи, префикс ysc1_. Используется виджетом (sitekey).
        'clientKey' => '',

        // Серверный (приватный) ключ капчи, префикс ysc2_. Используется верификатором (secret).
        // По умолчанию берётся отсюда; если пусто — верификатор пробует SecretReader('YANDEX_SMARTCAPTCHA_SERVER_KEY').
        'serverKey' => '',

        // Язык виджета по умолчанию (hl): ru|en|be|kk|tt|uk|uz|tr.
        'defaultLanguage' => 'ru',

        // Endpoint серверной проверки токена.
        'verifyUrl' => 'https://smartcaptcha.yandexcloud.net/validate',

        // URL JS-скрипта капчи (подключается расширенным методом с ?render=onload).
        'jsUrl' => 'https://smartcaptcha.cloud.yandex.ru/captcha.js',

        // Таймаут запроса к /validate в секундах (Yandex рекомендует держать коротким).
        'timeout' => 2,

        // fail-open: при транспортной/HTTP-ошибке /validate пропускать пользователя как «человека»
        // (рекомендация Yandex — не создавать задержек из-за сбоя сервиса). НЕ влияет на чистый ответ "failed".
        'failOpen' => true,

        // Передавать IP пользователя в /validate (Yandex просит передавать для качества ML).
        'sendIp' => true,

        // Тестовый режим виджета (test=true): пользователю всегда показывается задание. Только для отладки.
        'testMode' => false,

        // Если задан — верификатор дополнительно сверяет поле host из ответа /validate с этим доменом.
        'expectedHost' => '',
    ],
];
