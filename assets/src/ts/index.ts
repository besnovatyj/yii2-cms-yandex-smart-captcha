/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

import { manager } from './manager';

/**
 * Точка входа бандла.
 *
 * Экспортирует в window:
 *   YandexSmartCaptcha        — менеджер виджетов (register/reset/execute);
 *   __yandexSmartCaptchaOnload — колбэк для ?onload= в captcha.js.
 */
if (!window.YandexSmartCaptcha) {
  window.YandexSmartCaptcha = manager;
  window.__yandexSmartCaptchaOnload = (): void => manager.onload();
}
