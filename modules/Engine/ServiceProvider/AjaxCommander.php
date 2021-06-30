<?php

namespace App\Engine\ServiceProvider;


use App\Engine\Models\Order;
use App\Engine\Validator\OrderValidator;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Request;

class AjaxCommander
{
    private $method;

    private $request;

    const SCIPEDNAME = 'action';

    private $logger;

    const ERROR_SMS_CREATE = "ошибка подключения к СМС сервису";

    const NO_SMS_ORDER = "нет заказов для СМС сервиса";

    public function __construct($method){

        $methodData = explode('_',$method);
        $methodData = array_map(function ($namePart){
            if ($namePart != self::SCIPEDNAME ){
                $namePart = ucfirst($namePart);
            }
        return $namePart;
        },$methodData);

        $this->method = implode($methodData);

        $this->request = Request::createFromGlobals();

        $this->logger = new Logger();

    }

    public function run(){

        $this->{$this->method}();

    }

    private function actionCloseOrders (){


        $requestOrders = $this->request->toArray();



        $Order = new Order();

        $message = '';
        $actionStatus = true;

        try {
            $Order->closeOrders($requestOrders);
            $Order->closePromOrders($requestOrders);

        }catch (\Exception $e){

            $message = $e->getMessage();
            $actionStatus =false;
        }


        $smsSendMessage = "";
        try{

            $smsSendMessage =  $this->notifySms($requestOrders);

        }catch (\Exception $e){

            $smsSendMessage = " ". $e->getMessage();
        }



        $response=[
            'success'=>$actionStatus,
            'message'=>$message.$smsSendMessage
        ];

        $this->responseJson($response);

    }
    private function responseJson($responseJsonData){

        header('content-type: application/json');
        echo json_encode($responseJsonData);
    }

    /**
     * @param $orderList
     * @return string
     * @throws \Exception
     */
    private function notifySms($orderList){

        try {

            $SmsSender = new SmsSender();

        }catch (\Exception $e){

            $this->logger->logError($e->getMessage());

           throw new \Exception(self::ERROR_SMS_CREATE);
        }


        $Order = new Order();

        $ordersForSms = $Order->getOrdersReadyForSms($orderList);

        if (empty($ordersForSms)){

            return self::NO_SMS_ORDER;

        }

        $messagesErrors = "";

        foreach ($ordersForSms as $orderData){

            try {

                $result = $SmsSender->SendSms($orderData) ;
                if (is_array($result->SendSMSResult->ResultArray)){
                    $text = $result->SendSMSResult->ResultArray[0];
                }else{
                    $text = $result->SendSMSResult->ResultArray;
                }
                $messagesErrors .= $text . "; ";

            }catch (\Exception $e){

                $messagesErrors .= $orderData['order_id'] . ": " . $e->getMessage()."; ";
            }

        }

        return $messagesErrors;
    }
}