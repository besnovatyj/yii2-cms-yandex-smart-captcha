/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

import type {
  RenderParams,
  SmartCaptcha,
  WidgetRenderConfig,
  YandexSmartCaptchaManager,
} from './types';

/**
 * Менеджер виджетов Yandex SmartCaptcha (расширенный метод подключения).
 *
 * captcha.js подключается с `?render=onload&onload=__yandexSmartCaptchaOnload`, поэтому
 * автоскан контейнеров отключён — все виджеты отрисовываются здесь через smartCaptcha.render().
 * Это даёт полный контроль над параметрами, поддержку невидимой капчи и запись токена в
 * произвольное скрытое поле (в т.ч. по Yii2-конвенции name).
 */
class Manager implements YandexSmartCaptchaManager {
  private ready = false;
  private queue: WidgetRenderConfig[] = [];
  private widgets: Record<string, string> = {};

  register(config: WidgetRenderConfig): void {
    if (this.ready && window.smartCaptcha) {
      this.renderOne(config);
    } else {
      this.queue.push(config);
    }
  }

  onload(): void {
    this.ready = true;
    if (!window.smartCaptcha) {
      return;
    }
    const queued = this.queue;
    this.queue = [];
    queued.forEach((cfg) => this.renderOne(cfg));
  }

  reset(containerId: string): void {
    const widgetId = this.widgets[containerId];
    if (widgetId !== undefined) {
      window.smartCaptcha?.reset(widgetId);
    }
  }

  execute(containerId: string): void {
    const widgetId = this.widgets[containerId];
    if (widgetId !== undefined) {
      window.smartCaptcha?.execute(widgetId);
    }
  }

  private renderOne(cfg: WidgetRenderConfig): void {
    const sc: SmartCaptcha | undefined = window.smartCaptcha;
    if (!sc) {
      return;
    }

    const container = document.getElementById(cfg.containerId);
    if (!container || Object.prototype.hasOwnProperty.call(this.widgets, cfg.containerId)) {
      return;
    }

    const tokenField = cfg.tokenFieldId
      ? (document.getElementById(cfg.tokenFieldId) as HTMLInputElement | null)
      : null;

    const params: RenderParams = { ...cfg.params };

    params.callback = (token: string): void => {
      if (tokenField) {
        tokenField.value = token || '';
      }
      container.dispatchEvent(
        new CustomEvent('yandexcaptcha:success', { detail: { token }, bubbles: true }),
      );
      if (cfg.autoSubmit && cfg.formId) {
        const form = document.getElementById(cfg.formId) as HTMLFormElement | null;
        form?.submit();
      }
    };

    const widgetId = sc.render(container, params);
    this.widgets[cfg.containerId] = widgetId;

    sc.subscribe(widgetId, 'token-expired', () => {
      if (tokenField) {
        tokenField.value = '';
      }
      container.dispatchEvent(new CustomEvent('yandexcaptcha:token-expired', { bubbles: true }));
    });
    sc.subscribe(widgetId, 'network-error', () => {
      container.dispatchEvent(new CustomEvent('yandexcaptcha:network-error', { bubbles: true }));
    });
    sc.subscribe(widgetId, 'javascript-error', (err) => {
      container.dispatchEvent(
        new CustomEvent('yandexcaptcha:javascript-error', { detail: err, bubbles: true }),
      );
    });

    // Невидимая капча: перехватываем submit и запускаем execute, пока токена нет.
    if (params.invisible && cfg.formId) {
      const form = document.getElementById(cfg.formId) as HTMLFormElement | null;
      form?.addEventListener('submit', (event) => {
        if (!tokenField || tokenField.value === '') {
          event.preventDefault();
          sc.execute(widgetId);
        }
      });
    }

    // Авто-сброс при закрытии Bootstrap-модалки.
    if (cfg.resetOnModalClose) {
      const modal = document.getElementById(cfg.resetOnModalClose);
      modal?.addEventListener('hidden.bs.modal', () => sc.reset(widgetId));
    }
  }
}

export const manager = new Manager();
