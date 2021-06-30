<?php

namespace App\Engine\Validator;


class OrderValidator
{

    private $order;

    const ORDER_NOT_FOUND_MSG = "Заказ не найден";
    const EMPTY_ORDER_NAME = "Не заполенны ФИО";
    const EMPTY_NP_REF = "Не заполнен идентификатор отделения НП";
    const EMPTY_CITY_REF = "Не заполнен идентификатор города НП";
    const INDEX_ERROR = "Не корректный индекс Укрпочты";
    const WEIGHT_ERROR = "Не корректный вес";

    public function __construct($order){


        $this->order = $order;

    }

    public function validateOrderCustomer(){

        if (empty($this->order['order_surname'])){
            throw new \Exception(self::EMPTY_ORDER_NAME);
        }
        if (empty($this->order['order_name'])){
            throw new \Exception(self::EMPTY_ORDER_NAME);
        }


    }

    public function validateOrderShipment(){

        if ($this->order['index_ukrp'] > 0 ){

            // just example
            if (($this->order['index_ukrp'] < 1000) || ($this->order['index_ukrp'] > 99999)){
                throw new \Exception(self::INDEX_ERROR);
            }

        }else{

            if (empty($this->order['otdilenie_np_ref'])){
                throw new \Exception(self::EMPTY_NP_REF);
            }
            if (empty($this->order['city_ref'])){
                throw new \Exception(self::EMPTY_CITY_REF);
            }

        }

    }
}