<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\dto;

/**
 * Результат серверной проверки токена SmartCaptcha (ответ /validate + производные флаги).
 *
 * Инкапсулирует решение «человек / робот» вместе с диагностикой (status/message/host/metadata),
 * чтобы вызывающий код не разбирал сырой JSON и не сравнивал строки message (Yandex это запрещает).
 */
final readonly class VerificationResult
{
    /**
     * @param bool                  $human        Итоговое решение: true — человек, false — робот/ошибка.
     * @param string                $status       Поле status ответа: 'ok' | 'failed' (или 'error' при сбое транспорта).
     * @param string                $message      Диагностическое сообщение (только для логов, не для условий).
     * @param string|null           $host         Домен, на котором пройдена проверка (поле host ответа при status=ok).
     * @param array<string, string> $metadata     Метаданные из справки капчи, если сервис их вернул.
     * @param bool                  $networkError Проверка не выполнена из-за сбоя транспорта/HTTP (решение принято по fail-open/closed).
     */
    public function __construct(
        public bool $human,
        public string $status,
        public string $message = '',
        public ?string $host = null,
        public array $metadata = [],
        public bool $networkError = false,
    ) {
    }

    /** Прошёл ли пользователь проверку как человек. */
    public function isHuman(): bool
    {
        return $this->human;
    }

    /**
     * Успешный ответ сервиса (человек).
     *
     * @param array<string, string> $metadata
     */
    public static function human(?string $host = null, array $metadata = []): self
    {
        return new self(human: true, status: 'ok', host: $host, metadata: $metadata);
    }

    /** Робот / неуспешный ответ сервиса. */
    public static function robot(string $message = ''): self
    {
        return new self(human: false, status: 'failed', message: $message);
    }

    /** Проверка не выполнена из-за сбоя (решение зависит от fail-open). */
    public static function networkFailure(bool $failOpen, string $message = ''): self
    {
        return new self(
            human: $failOpen,
            status: 'error',
            message: $message,
            networkError: true,
        );
    }
}
