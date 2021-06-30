<?php

namespace App\Engine\ServiceProvider;


use App\Engine\Models\Order;
use App\Engine\Validator\OrderValidator;
use JetBrains\PhpStorm\Pure;
use SoapClient;
use Symfony\Component\HttpFoundation\Request;

class SmsSender
{

    private $smsProvider;

    private $logger;

    public function __construct($smsService =''){

        $this->logger = new Logger();

        if ($smsService == ''){
            $smsService = 'turbosms';
        }

          $this->smsProvider = $this->makeSmsService($smsService);

    }

    public function SendSms($order){


            $message = "Благодарим за заказ! Ваш ТТН:" . $order['ttn'];
            $phone_turbosms = "+" .$order['order_phone'];
            // Текст сообщения ОБЯЗАТЕЛЬНО отправлять в кодировке UTF-8
            $text = iconv('utf-8', 'utf-8', $message);

            // Отправляем сообщение на один номер.
            // Подпись отправителя может содержать английские буквы и цифры. Максимальная длина - 11 символов.
            // Номер указывается в полном формате, включая плюс и код страны
            $sms = [
                'sender' => 'ForGirl',
                'destination' => $phone_turbosms,
                'text' => $text
            ];

            return $this->smsProvider->SendSMS($sms);

    }

    private function makeSmsService($smsService){

        switch ($smsService){
            case 'turbosms': return $this->turboSms();
        }
        return null;
    }

    private function turboSms(){
        $client = new SoapClient('http://turbosms.in.ua/api/wsdl.html');

        $auth = [
            'login' => '',
            'password' => ''
        ];
        $client->Auth($auth);

        return $client;
    }

}