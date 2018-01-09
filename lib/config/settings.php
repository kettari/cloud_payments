<?php

/*
  Taxation system constants

  See cloud_paymentsPayment class constants:

  const TS_GENERAL = 0;
  const TS_SIMPLIFIED_INCOME_ONLY = 1;
  const TS_SIMPLIFIED_INCOME_MINUS_EXPENSE = 2;
  const TS_IMPUTED_INCOME = 3;
  const TS_AGRICULTURE = 4;
  const TS_LICENSE = 5;
 */

return array(
  'publicId'       => array(
    'value'        => '',
    'title'        => 'Public ID',
    'description'  => 'Идентификатор сайта, находится в ЛК CloudPayments.',
    'control_type' => waHtmlControl::INPUT,
  ),
  'apiSecret'      => array(
    'value'        => '',
    'title'        => 'API secret',
    'description'  => 'Пароль для API, находится в ЛК CloudPayments.',
    'control_type' => waHtmlControl::INPUT,
  ),
  'messageScheme' => array(
    'value'        => 'sms',
    'title'        => 'Схема проведения платежа',
    'description'  => 'Одностадийная оплата выполняется сразу, двухстадийная требует подтверждения в личном кабинете мерчанта CloudPayments. Подробнее см. <a href="https://cloudpayments.ru/Docs/Integration#schemes" target="_blank">Схемы проведения платежа</a> <i class="icon10 new-window"></i>',
    'control_type' => waHtmlControl::RADIOGROUP,
    'options'      => array(
      array(
        'value' => 'sms',
        'title' => 'Одностадийная',
      ),
      array(
        'value' => 'dms',
        'title' => 'Двухстадийная',
      ),
    ),
  ),
  'taxationSystem' => array(
    'value'        => 0,
    'title'        => 'Система налогообложения',
    'description'  => 'Указанная система налогообложения должна совпадать с одним из вариантов, зарегистрированных в ККТ.',
    'control_type' => waHtmlControl::SELECT,
    'options'      => array(
      array(
        'value' => 0,
        'title' => 'Общая система налогообложения',
      ),
      array(
        'value' => 1,
        'title' => 'Упрощенная система налогообложения (Доход)',
      ),
      array(
        'value' => 2,
        'title' => 'Упрощенная система налогообложения (Доход минус Расход)',
      ),
      array(
        'value' => 3,
        'title' => 'Единый налог на вмененный доход',
      ),
      array(
        'value' => 4,
        'title' => 'Единый сельскохозяйственный налог',
      ),
      array(
        'value' => 5,
        'title' => 'Патентная система налогообложения',
      ),
    ),
  ),
  'vat'            => array(
    'value'        => null,
    'title'        => 'Значение ставки НДС',
    'description'  => 'При указании ставки НДС будьте внимательны: "НДС 0%" и "НДС не облагается" — это не равнозначные варианты.',
    'control_type' => waHtmlControl::SELECT,
    'options'      => array(
      array(
        'value' => null,
        'title' => 'НДС не облагается',
      ),
      array(
        'value' => '18',
        'title' => 'НДС 18%',
      ),
      array(
        'value' => '10',
        'title' => 'НДС 10%',
      ),
      array(
        'value' => '0',
        'title' => 'НДС 0%',
      ),
      array(
        'value' => '118',
        'title' => 'Расчетный НДС 18/118',
      ),
      array(
        'value' => '110',
        'title' => 'Расчетный НДС 10/110',
      ),
    ),
  ),
  'sendReceipt'    => array(
    'value'        => '1',
    'title'        => 'Отправлять фискальный чек по 54-ФЗ',
    'description'  => '',
    'control_type' => waHtmlControl::CHECKBOX,
  ),
  'widgetLanguage' => array(
    'value'        => 'ru-RU',
    'title'        => 'Локализация виджета',
    'description'  => 'Укажите язык виджета. От выбора языка так же зависит часовой пояс, используемый для отметок времени.',
    'control_type' => waHtmlControl::SELECT,
    'options'      => array(
      array(
        'value' => 'ru-RU',
        'title' => 'Русский (MSK)',
      ),
      array(
        'value' => 'en-US',
        'title' => 'Английский (CET)',
      ),
      array(
        'value' => 'lv',
        'title' => 'Латышский (CET)',
      ),
      array(
        'value' => 'az',
        'title' => 'Азербайджанский (AZT)',
      ),
      array(
        'value' => 'kk',
        'title' => 'Русский (ALMT)',
      ),
      array(
        'value' => 'kk-KZ',
        'title' => 'Казахский (ALMT)',
      ),
      array(
        'value' => 'uk',
        'title' => 'Украинский (EET)',
      ),
      array(
        'value' => 'pl',
        'title' => 'Польский (CET)',
      ),
      array(
        'value' => 'pt',
        'title' => 'Португальский (CET)',
      ),
    ),
  ),
  'debugMode'      => array(
    'value'        => '0',
    'title'        => 'Режим отладки плагина',
    'description'  => 'В режиме отладки плагин выдаст в броузер дамп данных, необходимых для проверки его работы и исправления ошибок.',
    'control_type' => waHtmlControl::CHECKBOX,
  ),
);