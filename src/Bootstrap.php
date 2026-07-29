<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha;

use Besnovatyj\Helpers\SecretReader;
use Besnovatyj\YandexSmartCaptcha\config\CaptchaConfig;
use Besnovatyj\YandexSmartCaptcha\contracts\CaptchaVerifierInterface;
use Besnovatyj\YandexSmartCaptcha\services\YandexSmartCaptchaVerifier;
use Yii;
use yii\base\BootstrapInterface;
use yii\di\Container;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;

/**
 * Bootstrap модуля: регистрация DI.
 *
 * Все синглтоны — ленивые (резолвятся при первом обращении, т.е. при первой проверке/рендере),
 * поэтому params модуля к этому моменту уже наполнены config-модулем.
 */
final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $container = Yii::$container;

        // Настройки собираем из params модуля (их наполняет config-модуль); serverKey с fallback на секреты.
        $container->setSingleton(
            CaptchaConfig::class,
            static function (): CaptchaConfig {
                $params = Yii::$app->getModule('YandexSmartCaptcha')->params ?? [];

                $serverKey = (string)($params['serverKey'] ?? '');
                if ($serverKey === '') {
                    // Приватный ключ разумно хранить в секретах; params — приоритетный, секрет — резерв.
                    $serverKey = SecretReader::get('YANDEX_SMARTCAPTCHA_SERVER_KEY');
                }

                return new CaptchaConfig(
                    clientKey: (string)($params['clientKey'] ?? ''),
                    serverKey: $serverKey,
                    verifyUrl: (string)($params['verifyUrl'] ?? 'https://smartcaptcha.yandexcloud.net/validate'),
                    jsUrl: (string)($params['jsUrl'] ?? 'https://smartcaptcha.cloud.yandex.ru/captcha.js'),
                    defaultLanguage: (string)($params['defaultLanguage'] ?? 'ru'),
                    timeout: (float)($params['timeout'] ?? 2.0),
                    failOpen: (bool)($params['failOpen'] ?? true),
                    sendIp: (bool)($params['sendIp'] ?? true),
                    testMode: (bool)($params['testMode'] ?? false),
                    expectedHost: (string)($params['expectedHost'] ?? ''),
                );
            },
        );

        $container->setSingleton(
            CaptchaVerifierInterface::class,
            static function (Container $container): YandexSmartCaptchaVerifier {
                // Собственный экземпляр httpclient, чтобы не завязываться на глобальную настройку клиента.
                $httpClient = new Client(['transport' => CurlTransport::class]);

                return new YandexSmartCaptchaVerifier(
                    config: $container->get(CaptchaConfig::class),
                    httpClient: $httpClient,
                );
            },
        );
    }
}
