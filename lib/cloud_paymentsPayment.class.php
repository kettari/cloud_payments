<?php

/**
 *
 * @property string $publicId
 * @property string $apiSecret
 * @property string $preference
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
    $hidden_fields['language'] = substr(
      $order->getContact()
        ->getLocale(),
      0,
      2
    );

    $transaction_data = [
      'invoiceId' => $order->id,
    ];
    /*$hidden_fields['preference'] = isset($this->preference) ? $this->preference : '';
    if (!empty($this->holdMode)) {
      $hidden_fields['holdMode'] = 1;

      $day = intval($this->expireDate);
      if ($day < 31 && $day > 0) {
        $hidden_fields['expireDate'] = date(
          'Y-m-d H:i:s',
          strtotime('+'.$day.' day')
        );
      } else {
        $hidden_fields['expireDate'] = date('Y-m-d H:i:s', strtotime('+3 day'));
      }
    }*/

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

  private function getSign(&$data)
  {
    $fields = [
      'eshopId',
      'recipientAmount',
      'recipientCurrency',
      'user_email',
      'serviceName',
      'orderId',
      'userFields',
    ];

    $hash = [];
    foreach ($fields as $field) {
      $hash[] = !empty($data[$field]) ? $data[$field] : '';
    }
    $hash[] = $this->apiSecret;
    $hash = md5(implode('::', $hash));

    return $hash;
  }

  private function getEndpointUrl()
  {
    return 'https://merchant.intellectmoney.ru/ru/';
  }

  protected function callbackInit($request)
  {
    if (preg_match($this->pattern, ifset($request['orderId']), $matches)) {
      $this->app_id = $matches[1];
      $this->merchant_id = $matches[2];
      $this->order_id = $matches[3];
    }

    return parent::callbackInit($request);
  }

  protected function callbackHandler($request)
  {
    $request_fields = [
      'eshopId'           => '',
      'paymentId'         => 0,
      'orderId'           => '',
      'eshopAccount'      => '',
      'serviceName'       => '',
      'recipientAmount'   => 0.0,
      'recipientCurrency' => '', // USD, RUR, EUR, UAH
      'paymentStatus'     => 0, // 3|5
      'userName'          => '',
      'userEmail'         => '',
      'paymentData'       => '',
      'secretKey'         => '',
      'hash'              => '',
    ];
    $request = array_merge($request_fields, $request);

    if (empty($request['eshopId']) || ($this->publicId != $request['eshopId'])) {
      throw new waException('Invalid shop id');
    }
    $hash = $this->getRequestSign($request);
    if (empty($request['hash']) || empty($hash) ||
      ($request['hash'] != $hash)
    ) {
      throw new waException('Invalid request sign');
    }


    $transaction_data = $this->formalizeData($request);


    $callback_method = null;
    switch (ifset($transaction_data['state'])) {
      case self::STATE_CAPTURED:
        $callback_method = self::CALLBACK_PAYMENT;
        break;
    }

    if ($callback_method) {
      $transaction_data = $this->saveTransaction($transaction_data, $request);
      $this->execAppCallback($callback_method, $transaction_data);
    }
  }

  private function getRequestSign($data)
  {
    $fields = [
      'eshopId',
      'orderId',
      'serviceName',
      'eshopAccount',
      'recipientAmount',
      'recipientCurrency',
      'paymentStatus',
      'userName',
      'userEmail',
      'paymentData',
    ];
    $hash = [];
    foreach ($fields as $field) {
      $hash[] = !empty($data[$field]) ? $data[$field] : '';
    }
    if (empty($this->apiSecret)) {
      return null;
    } else {
      $hash[] = $this->apiSecret;
    }
    $hash = md5(implode('::', $hash));

    return $hash;
  }

  protected function formalizeData($transaction_raw_data)
  {
    $transaction_data = parent::formalizeData($transaction_raw_data);

    $fields = [
      'userName'  => 'Имя Пользователя в Системе IntellectMoney',
      'userEmail' => 'Email Пользователя в Системе IntellectMoney',
    ];
    foreach ($fields as $field => $description) {
      if (ifset($transaction_raw_data[$field])) {
        $view_data[] = $description.': '.$transaction_raw_data[$field];
      }
    }

    $transaction_data = array_merge(
      $transaction_data,
      [
        'type'        => null,
        'native_id'   => ifset($transaction_raw_data['paymentId']),
        'amount'      => ifset($transaction_raw_data['recipientAmount']),
        'currency_id' => ifset($transaction_raw_data['recipientCurrency']),
        'result'      => 1,
        'order_id'    => $this->order_id,
        'view_data'   => implode("\n", $view_data),
      ]
    );

    switch (ifset($transaction_raw_data['paymentStatus'])) {
      case 3:
        $transaction_data['state'] = self::STATE_AUTH;
        $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
        break;
      case 5:
        $transaction_data['state'] = self::STATE_CAPTURED;
        $transaction_data['type'] = self::OPERATION_CAPTURE;
        break;
    }

    return $transaction_data;
  }
}
