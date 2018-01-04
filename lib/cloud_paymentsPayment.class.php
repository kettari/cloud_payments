<?php

/**
 * @name CloudPayments
 * @description CloudPayments payment plugin
 * @author Andrey Kornienko <ant@kaula.ru>
 *
 * Plugin settings parameters must be specified in file lib/config/settings.php
 * @property string $publicId
 * @property string $apiSecret
 * @property string $taxationSystem
 * @property string $vat
 * @property boolean $sendReceipt
 * @property boolean $debugMode
 */
class cloud_paymentsPayment extends waPayment implements waIPayment
{
  // Taxation system types
  const TS_GENERAL = 0;
  const TS_SIMPLIFIED_INCOME_ONLY = 1;
  const TS_SIMPLIFIED_INCOME_MINUS_EXPENSE = 2;
  const TS_IMPUTED_INCOME = 3;
  const TS_AGRICULTURE = 4;
  const TS_LICENSE = 5;

  private $order_id;
  private $pattern = '/^(\w[\w\d]+)_([\w\d]+)_(.+)$/';
  private $template = '%s_%s_%s';

  /**
   * Returns array of ISO3 codes of enabled currencies (from settings)
   * supported by payment gateway.
   *
   * Note: Russian legal entities should contact CloudPayments if they want
   * to receive payments in currencies other then RUB.
   *
   * @see https://cloudpayments.ru/Docs/Directory#currencies
   *
   * @return string[]
   */
  public function allowedCurrency()
  {
    return array(
      'RUB',
      'EUR',
      'USD',
      'GBP',
      'UAH',
      'BYN',
      'KZT',
      'AZN',
      'CHF',
      'CZK',
      'CAD',
      'PLN',
      'SEK',
      'TRY',
      'CNY',
      'INR',
      'BRL',
    );
  }

  /**
   * Generates payment form HTML code.
   *
   * Payment form can be displayed during checkout or on order-viewing page.
   * Form "action" URL can be that of the payment gateway or of the current
   * page (empty URL). In the latter case, submitted data are passed again to
   * this method for processing, if needed; e.g., verification, saving,
   * forwarding to payment gateway, etc.
   *
   * @param array $payment_form_data Array of POST request data received from
   *   payment form
   * (if no "action" URL is specified for the form)
   * @param waOrder $order_data Object containing all available order-related
   *   information
   * @param bool $auto_submit Whether payment form data must be automatically
   *   submitted (useful during checkout)
   * @return string Payment form HTML
   * @throws waException
   */
  public function payment($payment_form_data, $order_data, $auto_submit = false)
  {
    $order = waOrder::factory($order_data);

    // Change comma to dot. Some merchants reported issue with it
    $total = (float)str_replace(',', '.', $order->total);
    $shipping = (float)str_replace(',', '.', $order->shipping);

    /**
     * Fields required to be sent to CloudPayments
     */
    $hidden_fields = array();
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
      $total,
      2,
      '.',
      ''
    );
    $hidden_fields['currency'] = $order->currency;
    $hidden_fields['name'] = $order->getContact()
      ->getName();
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

    // Set VAT according to configuration and common sense
    switch ($this->taxationSystem) {
      case (self::TS_GENERAL):
        $vat = $this->vat;
        break;
      default:
        $vat = null;
        break;
    }

    // Enumerate items for the sake of 54-fz
    $hidden_fields['items'] = array();
    foreach ($order->items as $single_item) {

      // Change comma to dot. Some merchants reported issue with it
      $price = (float)str_replace(
        ',',
        '.',
        ifset($single_item['price'], 0)
      );
      $quantity = (float)str_replace(
        ',',
        '.',
        ifset($single_item['quantity'], 0)
      );
      $total_amount = (float)str_replace(
        ',',
        '.',
        ifset($single_item['total'], 0)
      );
      $total_discount = (float)str_replace(
        ',',
        '.',
        ifset($single_item['total_discount'], 0)
      );

      $cp_item['label'] = ifset($single_item['name']);
      $cp_item['price'] = number_format(
        $price,
        2,
        '.',
        ''
      );
      $cp_item['quantity'] = $quantity;
      $cp_item['amount'] = number_format(
        $total_amount - $total_discount,
        2,
        '.',
        ''
      );
      $cp_item['vat'] = $vat;
      $cp_item['ean13'] = ifset($single_item['sku']);
      $hidden_fields['items'][] = $cp_item;
    }

    // Shipping
    if ($shipping > 0) {
      $cp_item['label'] = $order_data->shipping_name;
      $cp_item['price'] = number_format(
        $shipping,
        2,
        '.',
        ''
      );
      $cp_item['quantity'] = 1;
      $cp_item['amount'] = $cp_item['price'];
      $cp_item['vat'] = $vat;
      $cp_item['ean13'] = '';
      $hidden_fields['items'][] = $cp_item;
    }

    $hidden_fields['taxationSystem'] = $this->taxationSystem;
    $hidden_fields['sendReceipt'] = $this->sendReceipt ? 'yes' : 'no';

    if ($this->debugMode) {
      print('<h1>Debug info</h1>');

      print('<h2>Order data</h2>');
      print('<pre>');
      print_r($order_data);
      print('</pre>');

      print('<h2>CloudPayments form data</h2>');
      print('<pre>');
      print_r($hidden_fields);
      print('</pre>');

      die;
    }

    /**
     * $transaction_data['order_id'] is mandatory field,
     *
     * @see /wa-apps/shop/lib/classes/shopPayment.class.php
     */
    $transaction_data = array(
      'order_id' => $order->id,
    );
    $hidden_fields['successUrl'] = $this->getAdapter()
      ->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
    $hidden_fields['failUrl'] = $this->getAdapter()
      ->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

    $view = wa()->getView();
    $view->assign('hidden_fields', $hidden_fields);
    $view->assign('auto_submit', $auto_submit);

    return $view->fetch($this->path.'/templates/payment.html');
  }

  /**
   * Plugin initialization for processing callbacks received from payment
   * gateway.
   *
   * @param array $request Request data array ($_REQUEST)
   * @return waPayment
   * @throws waPaymentException
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
   * Actual processing of callbacks from payment gateway.
   *
   * Plugin settings are already initialized and available (see
   * callbackInit()).
   * Request parameters are checked and app's callback handler is called, if
   * necessary:
   *
   * $this->execAppCallback($state, $transaction_data)
   *
   * $state should be one of the following constants defined in the waPayment:
   * CALLBACK_PAYMENT, CALLBACK_REFUND, CALLBACK_CONFIRMATION,
   * CALLBACK_CAPTURE, CALLBACK_DECLINE, CALLBACK_CANCEL, CALLBACK_CHARGEBACK
   *
   * @see https://developers.webasyst.ru/plugins/payment-plugins/
   *
   * @throws waPaymentException
   * @param array $request Request data array ($_REQUEST) received from gateway
   * @return array Associative array of optional callback processing result
   *   parameters:
   *     'redirect' => URL to redirect user upon callback processing
   *     'template' => path to template to be used for generation of HTML page
   *   displaying callback processing results; false if direct output is used
   *   if not specified, default template displaying message 'OK' is used
   *     'header'   => associative array of HTTP headers ('header name' =>
   *   'header value') to be sent to user's browser upon callback processing,
   *   useful for cases when charset and/or content type are different from
   *   UTF-8 and text/html
   *
   *     If a template is used, returned result is accessible in template
   *   source code via $result variable, and method's parameters via $params
   *   variable
   */
  protected function callbackHandler($request)
  {
    $request_fields = array(
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
    );
    $request = array_merge($request_fields, $request);

    // Check signature to avoid any fraud and mistakes
    if (!$this->checkSignature()) {
      throw new waPaymentException(
        'Invalid request signature (possible fraud)'
      );
    }

    // Convert request data into acceptable format and save transaction
    $transaction_data = $this->formalizeData($request);
    $transaction_data = $this->saveTransaction($transaction_data, $request);
    $result = $this->execAppCallback(self::CALLBACK_PAYMENT, $transaction_data);
    if (!empty($result['error'])) {
      throw new waPaymentException(
        'Forbidden (validate error): '.$result['error']
      );
    }

    // Send correct response to the CloudPayments server
    wa()
      ->getResponse()
      ->addHeader('Content-Type', 'application/json', true);
    echo json_encode(array('code' => 0));

    // This plugin generates response without using a template
    return array('template' => false);
  }

  /**
   *  Checks HMAC-encoded signature.
   *
   * @return bool
   * @throws \waPaymentException
   */
  private function checkSignature()
  {
    // Check if API secret is configured properly
    if (empty($this->apiSecret)) {
      throw new waPaymentException('API secret is not configured');
    }

    // Get received Content-Hmac value and compare with calculated
    $headers = $this->getAllHeaders();
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
   * Returns all HTTP headers.
   *
   * @see http://php.net/manual/ru/function.getallheaders.php#84262
   *
   * @return array
   */
  private function getAllHeaders()
  {
    $headers = array();
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(
          ' ',
          '-',
          ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
        )] = $value;
      }
    }

    return $headers;
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

    // Payment details to be showed in the UI of the order
    $fields = array(
      'Name'  => 'Имя держателя карты',
      'Email' => 'E-mail адрес плательщика',
    );
    $view_data = array();
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
      array(
        'type'        => self::OPERATION_AUTH_ONLY,
        // transaction id assigned by payment gateway
        'native_id'   => ifset($transaction_raw_data['TransactionId']),
        'amount'      => ifset($transaction_raw_data['Amount']),
        'currency_id' => ifset($transaction_raw_data['Currency']),
        'result'      => 1,
        'order_id'    => $this->order_id,
        'view_data'   => implode("\n", $view_data),
      )
    );

    return $transaction_data;
  }
}
