<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\validators;

use Besnovatyj\YandexSmartCaptcha\contracts\CaptchaVerifierInterface;
use Besnovatyj\YandexSmartCaptcha\dto\VerificationResult;
use Yii;
use yii\validators\Validator;

/**
 * Yii2-валидатор Yandex SmartCaptcha — тонкая обёртка над {@see CaptchaVerifierInterface}.
 *
 * Вся сетевая логика и политика ошибок живут в верификаторе; валидатор лишь достаёт токен из
 * атрибута, резолвит IP и переводит результат в ошибку модели.
 *
 * ## Пример использования в форме
 *
 * ```php
 * use Besnovatyj\YandexSmartCaptcha\validators\YandexSmartCaptchaValidator;
 *
 * public function rules(): array
 * {
 *     return [
 *         ['captchaToken', YandexSmartCaptchaValidator::class],
 *     ];
 * }
 * ```
 *
 * Имя атрибута должно совпадать с полем виджета (по умолчанию виджет вычисляет name из model/attribute).
 */
final class YandexSmartCaptchaValidator extends Validator
{
    /**
     * Сообщение об ошибке. Если null — подставляется дефолт в {@see init()}.
     * @var string|null
     */
    public $message;

    /**
     * Резолвить и передавать IP пользователя в проверку.
     * Финальное решение «слать ли IP в /validate» принимает верификатор по своим настройкам.
     */
    public bool $sendIp = true;

    /**
     * Явный IP пользователя. Если null — берётся из Yii::$app->request->userIP
     * (учитывает доверенные прокси согласно настройкам request).
     */
    public ?string $ip = null;

    /**
     * Капча обязательна: пустое значение — это ошибка (робот/обход), а не «пропустить».
     * @var bool
     */
    public $skipOnEmpty = false;

    public function init(): void
    {
        parent::init();

        // Принудительная инициализация модуля — регистрирует DI-контейнер (Bootstrap).
        Yii::$app->getModule('YandexSmartCaptcha');

        $this->message ??= 'Проверка «я не робот» не пройдена. Попробуйте ещё раз.';
    }

    public function validateAttribute($model, $attribute): void
    {
        $result = $this->check((string)($model->$attribute ?? ''));

        if (!$result->isHuman()) {
            $this->addError($model, $attribute, $this->message);
        }
    }

    /**
     * Поддержка standalone-валидации (Validator::validate($value)).
     *
     * @param mixed $value
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    protected function validateValue($value): ?array
    {
        $result = $this->check((string)$value);

        return $result->isHuman() ? null : [$this->message, []];
    }

    /** Единая точка проверки токена через верификатор из DI. */
    private function check(string $token): VerificationResult
    {
        /** @var CaptchaVerifierInterface $verifier */
        $verifier = Yii::$container->get(CaptchaVerifierInterface::class);

        $ip = null;
        if ($this->sendIp) {
            $ip = $this->ip ?? Yii::$app->request->getUserIP();
        }

        return $verifier->verify($token, $ip);
    }
}
