<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\contracts;

use Besnovatyj\YandexSmartCaptcha\dto\VerificationResult;

/**
 * Контракт серверного верификатора токена Yandex SmartCaptcha (SOLID: зависим от абстракции).
 *
 * Реализация обращается к POST /validate и инкапсулирует политику fail-open, сверку host и т.п.
 * Валидатор {@see \Besnovatyj\YandexSmartCaptcha\validators\YandexSmartCaptchaValidator} —
 * тонкая обёртка над этим контрактом.
 */
interface CaptchaVerifierInterface
{
    /**
     * Проверяет одноразовый токен, полученный из формы (поле smart-token).
     *
     * @param string      $token Значение токена из скрытого поля формы.
     * @param string|null $ip    IP-адрес пользователя (передаётся в /validate, если включено в настройках).
     */
    public function verify(string $token, ?string $ip = null): VerificationResult;
}
