# Yandex SmartCaptcha для Yii2 CMS

Интеграция [Yandex SmartCaptcha](https://yandex.cloud/ru/docs/smartcaptcha/) в Yii2 CMS
**расширенным методом** подключения (`window.smartCaptcha.render`). Пакет даёт:

- **Виджет** `YandexSmartCaptchaWidget` — рендерит капчу через `render()` со всеми опциями
  Yandex (обычная и невидимая, язык, `test`, `webview`, `shieldPosition`, `hideShield`,
  `metadata`), кладёт токен в скрытое поле формы.
- **Верификатор** `YandexSmartCaptchaVerifier` (сервис) — серверная проверка токена через
  `POST /validate` с политикой fail-open и опциональной сверкой `host`.
- **Валидатор** `YandexSmartCaptchaValidator` — тонкая Yii2-обёртка над верификатором для
  навешивания в `rules()` формы.

Ключи и поведение настраиваются через модуль `config` (см. `src/config/options.php`).

## Установка

Модуль ставится штатным установщиком (modman). После установки задайте в настройках модуля
(раздел **YandexSmartCaptcha**):

- **Клиентский ключ** (`clientKey`, `ysc1_…`) — публичный, для виджета;
- **Серверный ключ** (`serverKey`, `ysc2_…`) — приватный, для проверки. Как альтернатива —
  секрет `YANDEX_SMARTCAPTCHA_SERVER_KEY` (см. ниже).

## Ключи и секреты

**Клиентский ключ** (`ysc1_…`) публичен по своей природе — он попадает в HTML страницы, и
хранить его в настройках модуля (`config`) безопасно.

**Серверный ключ** (`ysc2_…`) — приватный. Yandex прямо предупреждает: его нельзя пересылать,
хранить в открытом виде или допускать в публичный доступ; при утечке нужно пересоздать капчу.
Поэтому у верификатора два источника ключа, в порядке приоритета:

1. **Опция модуля** `serverKey` (config-модуль). Значение лежит в хранилище настроек
   (`PhpFileStorage` — PHP-файл, либо БД). Удобно, но ключ оказывается в этом хранилище —
   убедитесь, что файл/таблица настроек не утекают в VCS, бэкапы и логи в открытом виде.
2. **Секрет** `YANDEX_SMARTCAPTCHA_SERVER_KEY` через `SecretReader` — используется, **только если
   опция `serverKey` пуста**. Это предпочтительный способ для production: в проекте секреты
   монтируются как Docker secrets (`/run/secrets/*`, доступны www-data) и не попадают в БД/файлы
   конфигурации.

Рекомендация: на проде оставляйте опцию `serverKey` пустой и кладите ключ в секрет
`YANDEX_SMARTCAPTCHA_SERVER_KEY`; опцию используйте для быстрого локального теста.

> Смена ключа: после ротации секрета сбросьте кэш `SecretReader` (пересоздание процесса
> php-fpm/CLI) — значение кэшируется в рамках запроса.

## Использование

### 1. Виджет в форме (ActiveForm)

```php
use Besnovatyj\YandexSmartCaptcha\widgets\YandexSmartCaptchaWidget;

echo YandexSmartCaptchaWidget::widget([
    'model'     => $form,
    'attribute' => 'captchaToken',
]);
```

Виджет сам подключит `captcha.js` (один раз на страницу), вычислит `name`/`id` поля токена
по Yii2-конвенции и запишет туда токен после прохождения проверки.

### 2. Валидатор в форме

```php
use Besnovatyj\YandexSmartCaptcha\validators\YandexSmartCaptchaValidator;

class ContactForm extends \Besnovatyj\Forms\BaseForm
{
    public ?string $captchaToken = null;

    public function rules(): array
    {
        return [
            ['captchaToken', YandexSmartCaptchaValidator::class],
        ];
    }
}
```

Имя атрибута должно совпадать с полем виджета. `skipOnEmpty` по умолчанию `false` — пустой
токен считается непройденной проверкой.

### 3. Невидимая капча

Требует `formId` — по `submit` формы вызывается `execute()`, а по получению токена форма
отправляется автоматически:

```php
echo YandexSmartCaptchaWidget::widget([
    'model'     => $form,
    'attribute' => 'captchaToken',
    'invisible' => true,
    'formId'    => 'contact-form',
]);
```

### 4. Прямой вызов верификатора (без формы)

```php
use Besnovatyj\YandexSmartCaptcha\contracts\CaptchaVerifierInterface;

/** @var CaptchaVerifierInterface $verifier */
$verifier = Yii::$container->get(CaptchaVerifierInterface::class);

$result = $verifier->verify(Yii::$app->request->post('smart-token', ''), Yii::$app->request->userIP);
if ($result->isHuman()) {
    // ок
}
```

## Опции виджета

| Свойство          | Тип       | Назначение (параметр `render`)                          |
|-------------------|-----------|---------------------------------------------------------|
| `model`/`attribute` | Model/string | Вычисление `name`/`id` поля токена по Yii2-конвенции |
| `name`            | string    | Имя поля токена без модели (по умолч. `smart-token`)    |
| `sitekey`         | ?string   | Клиентский ключ (по умолч. `params.clientKey`)          |
| `language`        | ?string   | `hl` — язык (по умолч. `params.defaultLanguage`)        |
| `test`            | ?bool     | `test` — всегда показывать задание (отладка)            |
| `webview`         | bool      | `webview` — запуск в WebView                            |
| `invisible`       | bool      | `invisible` — невидимая капча                           |
| `shieldPosition`  | ?string   | `shieldPosition` — положение блока уведомления          |
| `hideShield`      | bool      | `hideShield` — скрыть блок уведомления                  |
| `metadata`        | array     | `metadata` — доп. данные для правил показа              |
| `formId`          | ?string   | id формы (нужен для невидимой капчи)                    |
| `reserveHeight`   | bool      | Резерв высоты 100px против «скачка» вёрстки             |
| `options`         | array     | Прочие параметры `render()` как есть                    |
| `modalId`         | ?string   | Авто-сброс при закрытии Bootstrap-модалки               |

## Настройки модуля (config)

`clientKey`, `serverKey`, `defaultLanguage`, `verifyUrl`, `jsUrl`, `timeout`, `failOpen`,
`sendIp`, `testMode`, `expectedHost` — см. `src/config/options.php`.

**fail-open** (`failOpen=true`, по умолчанию): при транспортной/HTTP-ошибке `/validate`
пользователь пропускается как человек (рекомендация Yandex — не блокировать форму из-за сбоя
сервиса). На штатный ответ `"failed"` это не влияет — там всегда «робот».

URL серверной проверки (/validate): https://smartcaptcha.yandexcloud.net/validate
URL JS-скрипта капчи: https://smartcaptcha.cloud.yandex.ru/captcha.js

## Сборка фронтенда

В репозитории уже лежит собранный `assets/dist/js/yandex-smart-captcha.js`. При правке
исходников (`assets/src/ts/*`):

```bash
cd assets && npm install && npm run build
```

## JS-события

Контейнер виджета генерирует bubbling-события: `yandexcaptcha:success` (detail.token),
`yandexcaptcha:token-expired`, `yandexcaptcha:network-error`, `yandexcaptcha:javascript-error`.
