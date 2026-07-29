<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\YandexSmartCaptcha\Module;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общая для всех приложений).
 *
 * Регистрирует модуль и его bootstrap-класс (L2 — выполняется только у активного модуля, гейт modman).
 * Меню и опции остаются вкладами modman через статические методы {@see Module}.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
            ['version' => Module::moduleVersion()],
        ),
    ],
    'bootstrap' => array_values(Module::bootstrapClasses()),
];
