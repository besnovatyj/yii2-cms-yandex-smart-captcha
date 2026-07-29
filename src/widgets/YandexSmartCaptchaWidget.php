<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\YandexSmartCaptcha\widgets;

use Besnovatyj\YandexSmartCaptcha\assets\YandexSmartCaptchaAsset;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * Виджет Yandex SmartCaptcha, подключаемый **расширенным методом** (window.smartCaptcha.render).
 *
 * Расширенный метод выбран сознательно: он даёт полный контроль над параметрами render(),
 * позволяет использовать невидимую капчу и кладёт токен в собственное скрытое поле с нужным
 * name (в т.ч. по Yii2-конвенции model/attribute), чтобы его подхватил
 * {@see \Besnovatyj\YandexSmartCaptcha\validators\YandexSmartCaptchaValidator}.
 *
 * ## С ActiveForm (model/attribute)
 *
 * ```php
 * echo YandexSmartCaptchaWidget::widget([
 *     'model'     => $form,
 *     'attribute' => 'captchaToken',
 *     'formId'    => 'contact-form', // нужен для невидимой капчи (execute при submit)
 * ]);
 * ```
 *
 * ## Невидимая капча
 *
 * ```php
 * echo YandexSmartCaptchaWidget::widget([
 *     'model'     => $form,
 *     'attribute' => 'captchaToken',
 *     'invisible' => true,
 *     'formId'    => 'contact-form',
 * ]);
 * ```
 *
 * ## Прямое использование (без модели)
 *
 * ```php
 * echo YandexSmartCaptchaWidget::widget(['name' => 'smart-token']);
 * ```
 *
 * @see https://yandex.cloud/ru/docs/smartcaptcha/concepts/widget-methods
 */
final class YandexSmartCaptchaWidget extends Widget
{
    /** Модель формы для вычисления name/id по Yii2-конвенции (совместно с {@see $attribute}). */
    public ?Model $model = null;

    /** Атрибут модели, в который придёт токен (совместно с {@see $model}). */
    public ?string $attribute = null;

    /** Имя скрытого поля токена, если не заданы model/attribute. */
    public string $name = 'smart-token';

    /** id скрытого поля токена (по умолчанию генерируется). */
    public ?string $fieldId = null;

    /** id контейнера виджета (по умолчанию генерируется). */
    public ?string $containerId = null;

    /** Клиентский ключ (sitekey). По умолчанию — params.clientKey модуля. */
    public ?string $sitekey = null;

    /** Язык виджета (hl). По умолчанию — params.defaultLanguage модуля. */
    public ?string $language = null;

    /** Тестовый режим (test): всегда показывать задание. По умолчанию — params.testMode. Только для отладки. */
    public ?bool $test = null;

    /** Запуск в WebView (webview) — для мобильных приложений. */
    public bool $webview = false;

    /** Невидимая капча (invisible). Требует расширенного метода (используется всегда). */
    public bool $invisible = false;

    /**
     * Положение блока уведомления об обработке данных (shieldPosition):
     * top-left|center-left|bottom-left|top-right|center-right|bottom-right.
     */
    public ?string $shieldPosition = null;

    /** Скрыть блок уведомления об обработке данных (hideShield). Обязывает уведомить пользователя иначе. */
    public bool $hideShield = false;

    /**
     * Дополнительные метаданные (metadata) для правил показа заданий.
     * Суммарная длина ключей и значений — не более 512 символов.
     * @var array<string, string>
     */
    public array $metadata = [];

    /**
     * id формы. Нужен для невидимой капчи: по submit вызывается execute(), а по получению
     * токена форма отправляется. Для обычной капчи не обязателен.
     */
    public ?string $formId = null;

    /** Резервировать высоту 100px у контейнера обычной капчи, чтобы не было «скачка» вёрстки. */
    public bool $reserveHeight = true;

    /** Доп. CSS-класс контейнера. */
    public string $cssClass = '';

    /** Доп. HTML-атрибуты контейнера. */
    public array $containerOptions = [];

    /**
     * Прочие параметры render(), которые будут смёрджены в объект params как есть.
     * Ключи — в camelCase, как их называет Yandex.
     * @var array<string, mixed>
     */
    public array $options = [];

    /** id Bootstrap-модалки, при закрытии которой виджет сбрасывается (reset). */
    public ?string $modalId = null;

    public function init(): void
    {
        parent::init();

        // Принудительная инициализация модуля — регистрирует DI (Bootstrap), даёт доступ к params.
        Yii::$app->getModule('YandexSmartCaptcha');
    }

    public function run(): string
    {
        $view = $this->view;
        YandexSmartCaptchaAsset::register($view);

        $params = Yii::$app->getModule('YandexSmartCaptcha')->params;

        $sitekey = $this->sitekey ?? (string)($params['clientKey'] ?? '');
        if ($sitekey === '') {
            throw new InvalidConfigException(
                'YandexSmartCaptcha: не задан clientKey (sitekey). Укажите его в настройках модуля или свойстве sitekey.',
            );
        }

        // Имя и id скрытого поля токена.
        if ($this->model !== null && $this->attribute !== null) {
            $name    = Html::getInputName($this->model, $this->attribute);
            $fieldId = $this->fieldId ?? Html::getInputId($this->model, $this->attribute);
        } else {
            $name    = $this->name;
            $fieldId = $this->fieldId ?? ($this->getId() . '-token');
        }
        $containerId = $this->containerId ?? ($this->getId() . '-container');

        // Параметры render(): начинаем с явных options, затем накрываем типизированными свойствами.
        $renderParams = $this->options;
        $renderParams['sitekey'] = $sitekey;
        $renderParams['hl'] = $this->language ?? (string)($params['defaultLanguage'] ?? 'ru');

        $test = $this->test ?? (bool)($params['testMode'] ?? false);
        if ($test) {
            $renderParams['test'] = true;
        }
        if ($this->webview) {
            $renderParams['webview'] = true;
        }
        if ($this->invisible) {
            $renderParams['invisible'] = true;
        }
        if ($this->shieldPosition !== null) {
            $renderParams['shieldPosition'] = $this->shieldPosition;
        }
        if ($this->hideShield) {
            $renderParams['hideShield'] = true;
        }
        if ($this->metadata !== []) {
            $renderParams['metadata'] = $this->metadata;
        }

        // Подключаем captcha.js расширенным методом. Дедуп по ключу — один скрипт на страницу.
        $jsUrl = (string)($params['jsUrl'] ?? 'https://smartcaptcha.cloud.yandex.ru/captcha.js');
        $view->registerJsFile(
            $jsUrl . '?render=onload&onload=__yandexSmartCaptchaOnload',
            ['defer' => true, 'position' => View::POS_END],
            'yandex-smart-captcha-js',
        );

        // Конфиг рендера для JS-менеджера.
        $config = [
            'containerId'      => $containerId,
            'tokenFieldId'     => $fieldId,
            'params'           => $renderParams,
            'formId'           => $this->formId,
            // Для невидимой капчи с известной формой — отправляем форму по получению токена.
            'autoSubmit'       => $this->invisible && $this->formId !== null,
            'resetOnModalClose' => $this->modalId,
        ];
        $view->registerJs(
            'window.YandexSmartCaptcha.register(' . Json::encode($config) . ');',
            View::POS_READY,
        );

        // Скрытое поле токена — его читает валидатор.
        $hidden = Html::hiddenInput($name, '', ['id' => $fieldId]);

        // Контейнер виджета. Для обычной капчи резервируем высоту, чтобы не «прыгала» вёрстка.
        $containerOptions = $this->containerOptions;
        Html::addCssClass($containerOptions, 'yandex-smart-captcha');
        if ($this->cssClass !== '') {
            Html::addCssClass($containerOptions, $this->cssClass);
        }
        $containerOptions['id'] = $containerId;
        if ($this->reserveHeight && !$this->invisible && !isset($containerOptions['style'])) {
            $containerOptions['style'] = 'height:100px';
        }

        return $hidden . Html::tag('div', '', $containerOptions);
    }
}
