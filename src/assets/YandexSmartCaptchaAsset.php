<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Asset-бандл менеджера SmartCaptcha (window.YandexSmartCaptcha).
 *
 * Бандл собирается командой:
 *   cd assets && npm install && npm run build
 * (в репозитории уже лежит готовый dist/js/yandex-smart-captcha.js, работающий без сборки).
 *
 * Менеджер определяет глобальные:
 *   - window.YandexSmartCaptcha      — register/reset/execute для виджетов;
 *   - window.__yandexSmartCaptchaOnload — колбэк, на который ссылается ?onload= в captcha.js.
 *
 * Грузится в <head> (POS_HEAD), чтобы колбэк был определён до того, как отложенный (defer)
 * скрипт captcha.js вызовет его после загрузки.
 */
final class YandexSmartCaptchaAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../../assets/dist/js';

    /** @var string[] */
    public $js = ['yandex-smart-captcha.js'];

    /** @var array<string, mixed> */
    public $jsOptions = ['position' => View::POS_HEAD];
}
