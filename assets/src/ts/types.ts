/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/** Языки виджета Yandex SmartCaptcha. */
export type CaptchaLanguage = 'ru' | 'en' | 'be' | 'kk' | 'tt' | 'uk' | 'uz' | 'tr';

/** Положение блока уведомления об обработке данных. */
export type ShieldPosition =
  | 'top-left'
  | 'center-left'
  | 'bottom-left'
  | 'top-right'
  | 'center-right'
  | 'bottom-right';

/** События, на которые можно подписаться через smartCaptcha.subscribe. */
export type SubscribeEvent =
  | 'challenge-visible'
  | 'challenge-hidden'
  | 'network-error'
  | 'javascript-error'
  | 'success'
  | 'token-expired';

/** Параметры метода render (совпадают с документацией Yandex). */
export interface RenderParams {
  sitekey: string;
  callback?: (token: string) => void;
  hl?: CaptchaLanguage;
  test?: boolean;
  webview?: boolean;
  invisible?: boolean;
  shieldPosition?: ShieldPosition;
  hideShield?: boolean;
  metadata?: Record<string, string>;

  [key: string]: unknown;
}

/** Глобальный объект window.smartCaptcha, который отдаёт captcha.js. */
export interface SmartCaptcha {
  render(container: HTMLElement | string, params: RenderParams): string;
  getResponse(widgetId?: string): string;
  execute(widgetId?: string): void;
  reset(widgetId?: string): void;
  destroy(widgetId?: string): void;
  subscribe(widgetId: string, event: SubscribeEvent, callback: (payload?: unknown) => void): () => void;
}

/**
 * Конфиг рендера, который PHP-виджет передаёт в window.YandexSmartCaptcha.register().
 */
export interface WidgetRenderConfig {
  /** id контейнера, в который встраивается виджет. */
  containerId: string;
  /** id скрытого поля, куда кладётся токен для отправки на бэкенд. */
  tokenFieldId: string;
  /** Параметры render() (без callback — его вешает менеджер). */
  params: RenderParams;
  /** id формы (для невидимой капчи — execute по submit, авто-сабмит по токену). */
  formId?: string | null;
  /** Отправлять форму автоматически по получению токена. */
  autoSubmit?: boolean;
  /** id Bootstrap-модалки, при закрытии которой виджет сбрасывается. */
  resetOnModalClose?: string | null;
}

declare global {
  interface Window {
    smartCaptcha?: SmartCaptcha;
    YandexSmartCaptcha?: YandexSmartCaptchaManager;
    __yandexSmartCaptchaOnload?: () => void;
  }
}

/** Публичный API менеджера. */
export interface YandexSmartCaptchaManager {
  register(config: WidgetRenderConfig): void;
  onload(): void;
  reset(containerId: string): void;
  execute(containerId: string): void;
}
