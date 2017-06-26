# Плагин CloudPayments
Плагин платёжной системы CloudPayments для [Вебасист](https://www.webasyst.com/) и магазина Shop Script.

Домашняя страница: https://github.com/kettari/cloud_payments

## Установка
Плагин можно установить вручную с помощью Инсталера со [страницы плагина](https://www.webasyst.ru/store/plugin/payment/cloud_payments/) либо вручную из [репозитория git](https://github.com/kettari/cloud_payments).

### Автоматическая установка
Автоматическая установка описана в [инструкции](https://support.webasyst.ru/8620/webasyst-store-install-product/) к системе Вебасист.

### Ручная установка
* Скачайте последнюю версию плагина из [репозитория](https://github.com/kettari/cloud_payments/releases).
* Распакуйте архив на своём сервере в папку `\wa-plugins\payment`
> Например, если магазин на вашем сервере установлен в папке `\var\www\shop.yourdomain.com\public`, 
> то плагин должен быть распакован в папку `\var\www\shop.yourdomain.com\public\wa-plugins\payment`
>
> Для проверки, полный путь к основному файлу плагина в примере выше будет:
> `\var\www\shop.yourdomain.com\public\wa-plugins\payment\cloud_payments\lib\cloud_paymentsPayment.class.php`
* Добавьте плагин CloudPayments в настройках вашего магазина как способ оплаты: *Настройки* → *Оплата* → *Добавить способ оплаты* → *CloudPayments*
* Настройте плагин, нажав в списке на пункт *«Конфигурация»*.

## Настройка плагина
Для корректной работы плагина нужно указать несколько опций.

**Внимание:** автор плагина и CloudPayments не несут ответственности за неверную настройку параметров плагина.
Пожалуйста, будьте особенно внимательны при настройке системы налогообложения и тщательно протестируйте
чеки онлайн-кассы, прежде чем перевести свой сайт в «боевой» режим.

* *Public ID* — идентификатор сайта, находится в ЛК CloudPayments. Обычно выглядит как строка символов, начинается с «pk_».
* *API secret* — пароль для API, находится в ЛК CloudPayments в параметре «*Пароль для API*».
* *Система налогообложения* — система налогообложения, используемая в юридическом лице от имени которого работает магазин. Варианты:
  * Общая система налогообложения (ОСН)
  * Упрощенная система налогообложения (Доход)
  * Упрощенная система налогообложения (Доход минус Расход)
  * Единый налог на вмененный доход
  * Единый сельскохозяйственный налог
  * Патентная система налогообложения
* *Значение ставки НДС* — если используется «Основная система налогообложения», то какую ставку НДС указывать в чеках онлайн-кассы. При указании ставки НДС будьте внимательны: «НДС 0%» и «НДС не облагается» — это не равнозначные варианты.
Если в параметре «*Система налогообложения*» выбран любой вариант, кроме ОСН, то плагин всегда подставляет в чек вариант «НДС не облагается».

### Настройка уведомлений об оплате
Чтобы магазин узнавал об оплатах товара, в личном кабинете CloudPayments укажите для уведомления Pay адрес из параметра «*URL для Pay уведомлений*» плагина. 
> Например, URL для уведомлений может выглядеть примерно так: `http://www.your-domain.com/payments.php/cloud_payments/`, но это не точно.

# Контакты
Вопросы и предложения пишите в [issues на гитхабе](https://github.com/kettari/cloud_payments/issues).