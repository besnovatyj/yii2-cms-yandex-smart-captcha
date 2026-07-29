/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 *
 * Менеджер виджетов Yandex SmartCaptcha (расширенный метод подключения).
 *
 * Определяет:
 *   window.YandexSmartCaptcha        — register(cfg) / reset(id) / execute(id)
 *   window.__yandexSmartCaptchaOnload — колбэк для ?onload= в captcha.js
 *
 * Готовый (уже собранный) файл. Исходники — assets/src/ts/*, сборка: cd assets && npm run build.
 */
(function () {
  'use strict';

  if (window.YandexSmartCaptcha) {
    return;
  }

  var manager = {
    _ready: false,
    _queue: [],
    _widgets: {},

    /**
     * Регистрирует конфиг рендера виджета. Если captcha.js уже загружен — рендерит сразу,
     * иначе кладёт в очередь до срабатывания onload.
     */
    register: function (cfg) {
      if (this._ready && window.smartCaptcha) {
        this._renderOne(cfg);
      } else {
        this._queue.push(cfg);
      }
    },

    /** Вызывается captcha.js после загрузки. Отрисовывает всё, что накопилось в очереди. */
    onload: function () {
      this._ready = true;
      if (!window.smartCaptcha) {
        return;
      }
      var queued = this._queue;
      this._queue = [];
      for (var i = 0; i < queued.length; i++) {
        this._renderOne(queued[i]);
      }
    },

    /** Сброс виджета по id его контейнера. */
    reset: function (containerId) {
      var widgetId = this._widgets[containerId];
      if (widgetId !== undefined && window.smartCaptcha) {
        window.smartCaptcha.reset(widgetId);
      }
    },

    /** Запуск проверки (для невидимой капчи) по id контейнера. */
    execute: function (containerId) {
      var widgetId = this._widgets[containerId];
      if (widgetId !== undefined && window.smartCaptcha) {
        window.smartCaptcha.execute(widgetId);
      }
    },

    _renderOne: function (cfg) {
      var sc = window.smartCaptcha;
      if (!sc) {
        return;
      }

      var container = document.getElementById(cfg.containerId);
      if (!container) {
        return;
      }
      if (Object.prototype.hasOwnProperty.call(this._widgets, cfg.containerId)) {
        return; // уже отрисован
      }

      var tokenField = cfg.tokenFieldId ? document.getElementById(cfg.tokenFieldId) : null;

      var params = {};
      var src = cfg.params || {};
      for (var key in src) {
        if (Object.prototype.hasOwnProperty.call(src, key)) {
          params[key] = src[key];
        }
      }

      params.callback = function (token) {
        if (tokenField) {
          tokenField.value = token || '';
        }
        container.dispatchEvent(
          new CustomEvent('yandexcaptcha:success', { detail: { token: token }, bubbles: true })
        );
        if (cfg.autoSubmit && cfg.formId) {
          var form = document.getElementById(cfg.formId);
          if (form) {
            form.submit();
          }
        }
      };

      var widgetId = sc.render(container, params);
      this._widgets[cfg.containerId] = widgetId;

      if (typeof sc.subscribe === 'function') {
        sc.subscribe(widgetId, 'token-expired', function () {
          if (tokenField) {
            tokenField.value = '';
          }
          container.dispatchEvent(new CustomEvent('yandexcaptcha:token-expired', { bubbles: true }));
        });
        sc.subscribe(widgetId, 'network-error', function () {
          container.dispatchEvent(new CustomEvent('yandexcaptcha:network-error', { bubbles: true }));
        });
        sc.subscribe(widgetId, 'javascript-error', function (err) {
          container.dispatchEvent(
            new CustomEvent('yandexcaptcha:javascript-error', { detail: err, bubbles: true })
          );
        });
      }

      // Невидимая капча: по submit формы запускаем проверку, если токена ещё нет.
      if (params.invisible && cfg.formId) {
        var invisibleForm = document.getElementById(cfg.formId);
        if (invisibleForm) {
          invisibleForm.addEventListener('submit', function (event) {
            if (!tokenField || tokenField.value === '') {
              event.preventDefault();
              sc.execute(widgetId);
            }
          });
        }
      }

      // Авто-сброс при закрытии Bootstrap-модалки — чтобы повторное открытие давало свежий виджет.
      if (cfg.resetOnModalClose) {
        var modal = document.getElementById(cfg.resetOnModalClose);
        if (modal) {
          modal.addEventListener('hidden.bs.modal', function () {
            sc.reset(widgetId);
          });
        }
      }
    }
  };

  window.YandexSmartCaptcha = manager;
  window.__yandexSmartCaptchaOnload = function () {
    manager.onload();
  };
})();
