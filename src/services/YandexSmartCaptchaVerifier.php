<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\services;

use Besnovatyj\YandexSmartCaptcha\config\CaptchaConfig;
use Besnovatyj\YandexSmartCaptcha\contracts\CaptchaVerifierInterface;
use Besnovatyj\YandexSmartCaptcha\dto\VerificationResult;
use Throwable;
use Yii;
use yii\httpclient\Client;

/**
 * Серверный верификатор Yandex SmartCaptcha.
 *
 * Отправляет POST на {@see CaptchaConfig::$verifyUrl} в формате x-www-form-urlencoded
 * (secret + token [+ ip]) и разбирает JSON-ответ вида {status, message, host?}.
 *
 * Политика ошибок (по рекомендации Yandex):
 *  - чистый ответ "failed" от сервиса → робот (fail-closed, всегда);
 *  - транспортная/HTTP-ошибка (код != 2xx, таймаут, недоступность) → решение по {@see CaptchaConfig::$failOpen},
 *    чтобы сбой сервиса не блокировал форму задержкой/отказом.
 *
 * Единственная ответственность (SRP) — проверка токена; сетевой клиент внедряется извне (DI).
 */
final class YandexSmartCaptchaVerifier implements CaptchaVerifierInterface
{
    public function __construct(
        private readonly CaptchaConfig $config,
        private readonly Client $httpClient,
    ) {
    }

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $token = trim($token);
        if ($token === '') {
            // Пустой токен — форма отправлена без прохождения капчи. Это не сбой сервиса, а робот.
            return VerificationResult::robot('Empty token');
        }

        if ($this->config->serverKey === '') {
            // Ключ сервера не настроен — это ошибка конфигурации. Fail-closed: не пропускаем,
            // иначе капча превратится в заглушку, пропускающую всех.
            Yii::error(
                'YandexSmartCaptcha: serverKey не задан (params.serverKey / SecretReader). Проверка невозможна.',
                __METHOD__,
            );

            return VerificationResult::robot('Server key is not configured');
        }

        $data = [
            'secret' => $this->config->serverKey,
            'token'  => $token,
        ];
        if ($this->config->sendIp && $ip !== null && $ip !== '') {
            $data['ip'] = $ip;
        }

        try {
            $response = $this->httpClient->createRequest()
                ->setMethod('POST')
                ->setUrl($this->config->verifyUrl)
                ->setFormat(Client::FORMAT_URLENCODED)
                ->setData($data)
                ->setOptions([
                    'timeout'        => $this->config->timeout,
                    'connectTimeout' => $this->config->timeout,
                ])
                ->send();
        } catch (Throwable $e) {
            // Недоступность сервиса/таймаут — решение по fail-open.
            Yii::warning(
                'YandexSmartCaptcha: сбой запроса к /validate: ' . $e->getMessage(),
                __METHOD__,
            );

            return VerificationResult::networkFailure($this->config->failOpen, $e->getMessage());
        }

        if (!$response->getIsOk()) {
            // HTTP-код не 2xx. Yandex рекомендует трактовать как «ok», чтобы не задерживать пользователя.
            Yii::warning(
                'YandexSmartCaptcha: /validate вернул HTTP ' . $response->getStatusCode(),
                __METHOD__,
            );

            return VerificationResult::networkFailure(
                $this->config->failOpen,
                'HTTP ' . $response->getStatusCode(),
            );
        }

        $body = $response->getContent() ?? '';
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            // Невалидный ответ — трактуем как сбой сервиса.
            Yii::warning('YandexSmartCaptcha: невалидный JSON от /validate: ' . $body, __METHOD__);

            return VerificationResult::networkFailure($this->config->failOpen, 'Malformed response');
        }

        $status  = (string)($decoded['status'] ?? 'failed');
        $message = (string)($decoded['message'] ?? '');
        $host    = isset($decoded['host']) ? (string)$decoded['host'] : null;
        /** @var array<string, string> $metadata */
        $metadata = is_array($decoded['metadata'] ?? null) ? $decoded['metadata'] : [];

        if ($status !== 'ok') {
            // Штатный ответ «робот» либо ошибка в запросе (например, невалидный токен).
            return new VerificationResult(
                human: false,
                status: $status,
                message: $message,
                host: $host,
                metadata: $metadata,
            );
        }

        // Доп. защита: если задан ожидаемый host — сверяем (порт в host возможен, сравниваем префикс до ':').
        if ($this->config->expectedHost !== '' && $host !== null && $host !== '') {
            $actualHost = explode(':', $host, 2)[0];
            if (!hash_equals($this->config->expectedHost, $actualHost)) {
                Yii::warning(
                    "YandexSmartCaptcha: host не совпал (ожидался {$this->config->expectedHost}, получен {$host}).",
                    __METHOD__,
                );

                return new VerificationResult(
                    human: false,
                    status: 'failed',
                    message: 'Host mismatch',
                    host: $host,
                    metadata: $metadata,
                );
            }
        }

        return new VerificationResult(
            human: true,
            status: 'ok',
            message: $message,
            host: $host,
            metadata: $metadata,
        );
    }
}
