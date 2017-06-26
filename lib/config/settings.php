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

return [
  'publicId'       => [
    'value'        => '',
    'title'        => 'Public ID',
    'description'  => 'Идентификатор сайта, находится в ЛК CloudPayments.',
    'control_type' => waHtmlControl::INPUT,
  ],
  'apiSecret'      => [
    'value'        => '',
    'title'        => 'API secret',
    'description'  => 'Пароль для API, находится в ЛК CloudPayments.',
    'control_type' => waHtmlControl::INPUT,
  ],
  'taxationSystem' => [
    'value'        => 0,
    'title'        => 'Система налогообложения',
    'description'  => 'Указанная система налогообложения должна совпадать с одним из вариантов, зарегистрированных в ККТ.',
    'control_type' => waHtmlControl::SELECT,
    'options'      => [
      [
        'value' => 0,
        'title' => 'Общая система налогообложения',
      ],
      [
        'value' => 1,
        'title' => 'Упрощенная система налогообложения (Доход)',
      ],
      [
        'value' => 2,
        'title' => 'Упрощенная система налогообложения (Доход минус Расход)',
      ],
      [
        'value' => 3,
        'title' => 'Единый налог на вмененный доход',
      ],
      [
        'value' => 4,
        'title' => 'Единый сельскохозяйственный налог',
      ],
      [
        'value' => 5,
        'title' => 'Патентная система налогообложения',
      ],
    ],
  ],
  'vat'            => [
    'value'        => null,
    'title'        => 'Значение ставки НДС',
    'description'  => 'При указании ставки НДС будьте внимательны: "НДС 0%" и "НДС не облагается" — это не равнозначные варианты.',
    'control_type' => waHtmlControl::SELECT,
    'options'      => [
      [
        'value' => null,
        'title' => 'НДС не облагается',
      ],
      [
        'value' => '18',
        'title' => 'НДС 18%',
      ],
      [
        'value' => '10',
        'title' => 'НДС 10%',
      ],
      [
        'value' => '0',
        'title' => 'НДС 0%',
      ],
      [
        'value' => '118',
        'title' => 'Расчетный НДС 18/118',
      ],
      [
        'value' => '110',
        'title' => 'Расчетный НДС 10/110',
      ],
    ],
  ],
];