<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\YandexSmartCaptcha;

use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesAdminMenu;
use Besnovatyj\Contracts\module\ProvidesBootstrap;
use Besnovatyj\Contracts\module\ProvidesOptions;
use Besnovatyj\Kernel\module\CmsModule;

/**
 * Модуль интеграции Yandex SmartCaptcha в Yii2 CMS.
 *
 * Даёт три компонента:
 *  - {@see \Besnovatyj\YandexSmartCaptcha\widgets\YandexSmartCaptchaWidget} — виджет капчи,
 *    подключаемый расширенным методом (window.smartCaptcha.render), со всеми опциями Yandex;
 *  - {@see \Besnovatyj\YandexSmartCaptcha\services\YandexSmartCaptchaVerifier} — серверный
 *    верификатор токена через POST /validate;
 *  - {@see \Besnovatyj\YandexSmartCaptcha\validators\YandexSmartCaptchaValidator} — Yii2-валидатор
 *    (тонкая обёртка над верификатором) для навешивания в rules() формы.
 *
 * Ключи (clientKey/serverKey) и поведение верификации настраиваются через config-модуль
 * (см. {@see moduleConfig()} и config/options.php).
 */
class Module extends CmsModule implements
    DeclaresModule, ProvidesAdminMenu, ProvidesOptions, ProvidesBootstrap
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'YandexSmartCaptcha';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array { return require __DIR__ . '/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__ . '/config/config.php'; }
    public static function options(): array { return require __DIR__ . '/config/options.php'; }
    public static function bootstrapClasses(): array { return [Bootstrap::class]; }
}
