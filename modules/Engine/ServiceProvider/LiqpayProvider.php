<?php

namespace App\Engine\ServiceProvider;


use App\Engine\ApiServices\Payment\LiqPay;

class LiqpayProvider
{
    private $api;


    public function __construct(){
        $keys['LIQPAY_PUBLIC'] = '';
        $keys['LIQPAY_PRIVAT'] = '';


        try{
            $this->api = new LiqPay($keys['LIQPAY_PUBLIC'],$keys['LIQPAY_PRIVAT']);
        }catch (\Exception $e){
            echo $e->getMessage();exit();
        }


    }

    public function getOrderStatus($order_id){

        $orderStatus = $this->api->getPaymentStatus($order_id);

        return $orderStatus->status === 'success' ;
    }
}