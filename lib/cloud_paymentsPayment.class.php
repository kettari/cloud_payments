<?php

/**
 *
 * @property string $publicId
 * @property string $apiSecret
 * @property string $taxationSystem
 * @property string $vat
 */
class cloud_paymentsPayment extends waPayment implements waIPayment
{
  private $order_id;
  private $pattern = '/^(\w[\w\d]+)_([\w\d]+)_(.+)$/';
  private $template = '%s_%s_%s';

  /**
   * Возвращает массив с разрешёнными валютами для платежа.
   *
   * @return string|string[] ISO3 currency code
   */
  public function allowedCurrency()
  {
    return ['RUB'];
  }

  /**
   * Этот метод формирует основное содержимое страницы, с помощью которой
   * покупатель может ввести данные, необходимые для совершения оплаты
   * (например, реквизиты для выставления банковского счета), либо перейти на
   * защищенную страницу платежной системы.
   *
   * @param array $payment_form_data POST form data
   * @param waOrder $order_data formalized order data
   * @param bool $auto_submit
   * @return string HTML payment form
   */
  public function payment($payment_form_data, $order_data, $auto_submit = false)
  {
    $order = waOrder::factory($order_data);

    /**
     * Fields required to be sent to CloudPayments
     */
    $hidden_fields = [];
    $hidden_fields['publicId'] = $this->publicId;
    $hidden_fields['invoiceId'] = sprintf(
      $this->template,
      $this->app_id,
      $this->merchant_id,
      $order->id
    );
    $hidden_fields['description'] = mb_substr(
      $order->description,
      0,
      255,
      "UTF-8"
    );
    $hidden_fields['amount'] = number_format(
      (float)$order->total,
      2,
      '.',
      ''
    );
    $hidden_fields['currency'] = $order->currency;
    $hidden_fields['email'] = $order->getContact()
      ->get('email', 'default');
    $hidden_fields['accountId'] = $hidden_fields['email'];
    $hidden_fields['phone'] = $order->getContact()
      ->get('phone', 'default');
    $hidden_fields['language'] = substr(
      $order->getContact()
        ->getLocale(),
      0,
      2
    );

    // Enumerate items for the sake of 54-fz
    $hidden_fields['items'] = [];
    foreach ($order->items as $good) {
      $cp_item['label'] = ifset($good['name']);
      $cp_item['price'] = ifset($good['price']);
      $cp_item['quantity'] = ifset($good['quantity']);
      $cp_item['amount'] = ifset($good['total']) - (ifset($good['total_discount']));
      $cp_item['vat'] = $this->vat;
      $cp_item['ean13'] = ifset($good['sku']);
      $hidden_fields['items'] = $cp_item;
    }
    $hidden_fields['taxationSystem'] = $this->taxationSystem;

    print_r($hidden_fields);
    die;

    /**
     * $transaction_data['order_id'] is mandatory field,
     *
     * @see /wa-apps/shop/lib/classes/shopPayment.class.php
     */
    $transaction_data = [
      'order_id' => $order->id,
    ];
    $hidden_fields['successUrl'] = $this->getAdapter()
      ->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
    $hidden_fields['failUrl'] = $this->getAdapter()
      ->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

    //$hidden_fields['hash'] = $this->getSign($hidden_fields);

    $view = wa()->getView();
    //$view->assign('form_url', $this->getEndpointUrl());
    $view->assign('hidden_fields', $hidden_fields);
    $view->assign('auto_submit', $auto_submit);

    return $view->fetch($this->path.'/templates/payment.html');
  }

  /**
   * Этот метод позволяет извлечь из содержимого либо параметров адреса ответа
   * платежной системы информацию о приложении, которому необходимо передать
   * обработку, а также параметры, указанные в настройках плагина оплаты в этом
   * приложении.
   *
   * @param array $request
   * @return \waPayment
   * @throws \waPaymentException
   */
  protected function callbackInit($request)
  {
    if (preg_match($this->pattern, ifset($request['InvoiceId']), $matches)) {
      $this->app_id = $matches[1];
      $this->merchant_id = $matches[2];
      $this->order_id = $matches[3];
    } else {
      throw new waPaymentException('Invalid invoice number');
    }

    return parent::callbackInit($request);
  }

  /**
   * В этом методе описывается алгоритм обработки обратных запросов, полученных
   * от платежной системы. Основное назначение метода — вызвать обработчик
   * приложения для обработки транзакции:
   *
   * $this->execAppCallback($state, $transaction_data)
   *
   * В качестве $state нужно указать статус транзакции с помощью одной из
   * констант системного класса waPayment: CALLBACK_PAYMENT, CALLBACK_REFUND,
   * CALLBACK_CONFIRMATION, CALLBACK_CAPTURE, CALLBACK_DECLINE,
   * CALLBACK_CANCEL, CALLBACK_CHARGEBACK
   *
   * @see https://developers.webasyst.ru/plugins/payment-plugins/
   *
   * @param array $request
   * @return mixed|void
   * @throws \waPaymentException
   */
  protected function callbackHandler($request)
  {
    $request_fields = [
      'TransactionId' => 0,
      'InvoiceId'     => '',
      'Description'   => '',
      'Amount'        => 0.0,
      'Currency'      => '', // RUB,...
      'Name'          => '',
      'Email'         => '',
      'Data'          => '',
      'CardFirstSix'  => '',
      'CardLastFour'  => '',
    ];
    $request = array_merge($request_fields, $request);

    if (!$this->checkSignature()) {
      throw new waPaymentException(
        'Invalid request signature (possible fraud)'
      );
    }

    $transaction_data = $this->formalizeData($request);
    $transaction_data = $this->saveTransaction($transaction_data, $request);
    $this->execAppCallback(self::CALLBACK_PAYMENT, $transaction_data);
  }

  /**
   *  Check signature
   *
   * @return bool
   * @throws \waPaymentException
   */
  private function checkSignature()
  {
    // Check API secret is configured properly
    if (empty($this->apiSecret)) {
      throw new waPaymentException('API secret is not configured');
    }

    // Get received Content-Hmac value and compare with calculated
    $headers = getallheaders();
    $hmac = ifset($headers['Content-Hmac']);
    $signature = base64_encode(
      hash_hmac(
        'sha256',
        file_get_contents('php://input'),
        $this->apiSecret,
        true
      )
    );

    return $hmac == $signature;
  }

  /**
   * Converts transaction raw data to formatted data.
   *
   * @param array $transaction_raw_data
   * @return array
   */
  protected function formalizeData($transaction_raw_data)
  {
    $transaction_data = parent::formalizeData($transaction_raw_data);

    // Payment details
    $fields = [
      'Name'  => 'Имя держателя карты',
      'Email' => 'E-mail адрес плательщика',
    ];
    $view_data = [];
    foreach ($fields as $field => $description) {
      if (ifset($transaction_raw_data[$field])) {
        $view_data[] = $description.': '.$transaction_raw_data[$field];
      }
    }
    $view_data[] = sprintf(
      'Номер карты: %s****%s',
      $transaction_raw_data['CardFirstSix'],
      $transaction_raw_data['CardLastFour']
    );

    $transaction_data = array_merge(
      $transaction_data,
      [
        'type'        => self::OPERATION_AUTH_ONLY,
        // transaction id assigned by payment gateway
        'native_id'   => ifset($transaction_raw_data['TransactionId']),
        'amount'      => ifset($transaction_raw_data['Amount']),
        'currency_id' => ifset($transaction_raw_data['Currency']),
        'result'      => 1,
        'order_id'    => $this->order_id,
        'view_data'   => implode("\n", $view_data),
      ]
    );

    return $transaction_data;
  }
}
