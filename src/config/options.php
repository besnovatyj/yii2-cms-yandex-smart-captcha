<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

// Все опции определяются при установке (подключении) модуля в приложение.
// Настраиваются через модуль config; путь path определяет, куда значение ложится в params модуля.
return [

    'ysc_client_key' => [
        'path'         => 'modules.YandexSmartCaptcha.params.clientKey',
        'label'        => 'Клиентский ключ (sitekey, ysc1_)',
        'description'  => "Публичный ключ для виджета. Yii::\$app->getModule('YandexSmartCaptcha')->params['clientKey']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['string']],
        'inputOptions' => ['type' => 'input'],
    ],

    'ysc_server_key' => [
        'path'         => 'modules.YandexSmartCaptcha.params.serverKey',
        'label'        => 'Серверный ключ (secret, ysc2_)',
        'description'  => "Приватный ключ для проверки токена. Yii::\$app->getModule('YandexSmartCaptcha')->params['serverKey']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['string']],
        'inputOptions' => ['type' => 'input'],
    ],

    'ysc_default_language' => [
        'path'         => 'modules.YandexSmartCaptcha.params.defaultLanguage',
        'label'        => 'Язык виджета по умолчанию',
        'description'  => "hl виджета. Yii::\$app->getModule('YandexSmartCaptcha')->params['defaultLanguage']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['in', 'range' => ['ru', 'en', 'be', 'kk', 'tt', 'uk', 'uz', 'tr']]],
        'inputOptions' => [
            'type'  => 'dropdown',
            'items' => [
                'ru' => 'Русский',
                'en' => 'English',
                'be' => 'Беларуская',
                'kk' => 'Қазақша',
                'tt' => 'Татарча',
                'uk' => 'Українська',
                'uz' => 'Oʻzbekcha',
                'tr' => 'Türkçe',
            ],
        ],
    ],

    'ysc_verify_url' => [
        'path'         => 'modules.YandexSmartCaptcha.params.verifyUrl',
        'label'        => 'URL серверной проверки (/validate)',
        'description'  => "Endpoint проверки токена. Yii::\$app->getModule('YandexSmartCaptcha')->params['verifyUrl']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['url']],
        'inputOptions' => ['type' => 'input'],
    ],

    'ysc_js_url' => [
        'path'         => 'modules.YandexSmartCaptcha.params.jsUrl',
        'label'        => 'URL JS-скрипта капчи',
        'description'  => "captcha.js виджета. Yii::\$app->getModule('YandexSmartCaptcha')->params['jsUrl']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['url']],
        'inputOptions' => ['type' => 'input'],
    ],

    'ysc_timeout' => [
        'path'         => 'modules.YandexSmartCaptcha.params.timeout',
        'label'        => 'Таймаут запроса к /validate, сек',
        'description'  => "Yii::\$app->getModule('YandexSmartCaptcha')->params['timeout']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['number', 'min' => 0]],
        'inputOptions' => ['type' => 'input'],
    ],

    'ysc_fail_open' => [
        'path'         => 'modules.YandexSmartCaptcha.params.failOpen',
        'label'        => 'Fail-open при сбое сервиса',
        'description'  => "Пропускать пользователя при транспортной/HTTP-ошибке /validate. Yii::\$app->getModule('YandexSmartCaptcha')->params['failOpen']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['boolean']],
        'inputOptions' => ['type' => 'checkbox'],
    ],

    'ysc_send_ip' => [
        'path'         => 'modules.YandexSmartCaptcha.params.sendIp',
        'label'        => 'Передавать IP пользователя в /validate',
        'description'  => "Yii::\$app->getModule('YandexSmartCaptcha')->params['sendIp']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['boolean']],
        'inputOptions' => ['type' => 'checkbox'],
    ],

    'ysc_test_mode' => [
        'path'         => 'modules.YandexSmartCaptcha.params.testMode',
        'label'        => 'Тестовый режим виджета (test)',
        'description'  => "Всегда показывать задание — только для отладки. Yii::\$app->getModule('YandexSmartCaptcha')->params['testMode']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['boolean']],
        'inputOptions' => ['type' => 'checkbox'],
    ],

    'ysc_expected_host' => [
        'path'         => 'modules.YandexSmartCaptcha.params.expectedHost',
        'label'        => 'Ожидаемый host (доп. сверка ответа)',
        'description'  => "Если задан — верификатор сверяет host из ответа /validate. Yii::\$app->getModule('YandexSmartCaptcha')->params['expectedHost']",
        'category'     => 'YandexSmartCaptcha',
        'rules'        => [['string']],
        'inputOptions' => ['type' => 'input'],
    ],
];
