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
  'sendReceipt'      => array(
    'value'        => '1',
    'title'        => 'Отправлять фискальный чек по 54-ФЗ',
    'description'  => '',
    'control_type' => waHtmlControl::CHECKBOX,
  ),
  'debugMode'      => array(
    'value'        => '0',
    'title'        => 'Режим отладки плагина',
    'description'  => 'В режиме отладки плагин выдаст в броузер дамп данных, необходимых для проверки его работы и исправления ошибок.',
    'control_type' => waHtmlControl::CHECKBOX,
  ),
);