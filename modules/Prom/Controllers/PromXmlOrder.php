<?php

namespace App\Prom\Controllers;


class PromXmlOrder
{
    const PROM_ORDER_LINK = '';



    public function getOrders()
    {
        $orders = [];

        $ordersData = $this->getOrdersFromXml();
        foreach ( $ordersData as $orderData ){

            $orders [$orderData['order_id']] = [
                'client_id'=>0,
                'order_datetime'=>$orderData['order_date'],
                'order_confirmed'=>'no',
                'order_dostavka'=>$orderData['delivery_type'],
                'order_pay'=>$orderData['payment_type'],
                'order_type_pay'=>'',
                'order_surname'=>$orderData['customer']['customer_last_name'],
                'order_name'=>$orderData['customer']['customer_first_name'],
                'order_patronymic'=>$orderData['customer']['customer_patronymic'],
                'order_phone'=>$orderData['customer']['customer_phone'],
                'region'=>'',
                'city'=>'',
                'otdilenie_np'=>'',
                'city_ref'=>'',
                'otdilenie_np_ref'=>'',
                'street_ukrp'=>$orderData['delivery_address']['full_address'],
                'index_ukrp'=>$orderData['delivery_index'],
                'order_fio'=>'',
                'order_address'=>'',
                'order_email'=>$orderData['customer']['order_email'],
                'order_note'=>'',
                'order_cost_price'=>0,
                'order_discount'=>0,
                'order_price'=>$orderData['order_sum'],
                'order_profit'=>0,
                'predoplata'=>0,
                'ttn'=>'',
                'ves'=>'',
                'dlina'=>'',
                'insta_name'=>'',
                'sobran'=>0,
                'oplata_dostavki'=>0,
                'zakaz_s_site'=>2,
                'prom_order_id'=>$orderData['order_id'],
                'order_products'=>$orderData['products'],
            ];

        }

        return  $orders;
    }


    private function getOrdersFromXml()
    {
        $orders = [];

        $xml = simplexml_load_file (self::PROM_ORDER_LINK);

        foreach ($xml->order as $order ){


            $products = [];
            foreach ($order->items->item as $product){



                $products[]=[
                    'product_name'=>(string)$product->name,
                    'sku'=>(string)$product->sku,
                    'product_id'=>intval($product->external_id),
                    'product_price'=>intval($product->price),
                    'quantity'=>intval($product->quantity),
                ];
            }

            $delivery = $this->getDeliveryCode((string)$order->deliveryType);
            $address = $this->getAddress((string) $order->address);

            $customerName = explode (' ',(string)$order->name);

            $orders[]=[
                'order_id'=>intval($order['id']),
                'order_status'=>(string) $order['state'],
                'customer'=>[
                    'customer_first_name'=>isset($customerName[0]) ? $customerName[1] : '',
                    'customer_last_name'=>isset($customerName[1]) ? $customerName[0] : '',
                    'customer_patronymic'=>isset($customerName[2]) ? $customerName[2] : '',
                    'customer_phone'=>trim((string) $order->phone,'+'),
                    'customer_email'=>(string) $order->email,
                ],
                'order_sum'=>(float)($order->priceUAH),
                'order_date'=>$this->getDate((string)$order->date),
                'payment_type' =>$this->getPaymentType((string)$order->paymentType),
                'delivery_type' =>$delivery,
                'delivery_address'=>$address,
                'delivery_index'=>(string)$order->index,
                'products' =>$products,

            ];
        }
        die();
        $orders = array_reverse($orders);
        return $orders;
    }


    private function getDeliveryCode($delivery){
        switch ($delivery){
            case 'Доставка "Justin"': return 'JS';
            case 'Нова Пошта' : return 'NP';
            default : return 'address';

        }
    }

    private function getAddress($adressString)
    {
        $address=[];
        $city = mb_substr($adressString,0,mb_stripos($adressString,','));

        $warehouseNumberStart = mb_stripos($adressString,'№')+1;

        $warehouseNumberLength = mb_stripos($adressString,' ',$warehouseNumberStart)-$warehouseNumberStart;

        $warehouseNumber = trim(mb_substr($adressString,$warehouseNumberStart,$warehouseNumberLength),':');

        $warehouseNumber = intval($warehouseNumber);

        $addressWithOutCity = mb_substr($adressString,mb_stripos($adressString,',')+2);

        if ($warehouseNumber !=0 ){
            $warehouse_address = $addressWithOutCity;
        }else{
            $warehouse_address='';
        }

        if (!is_numeric($warehouseNumber)){
            $warehouseNumber = '';
        }
        $address = [
            'city'=>$city,
            'w_number'=>$warehouseNumber,
            'full_address'=>$adressString,
            'without_city'=>$addressWithOutCity,
            'warehouse_address'=>$warehouse_address,
        ];

        return $address;
    }

    private function getDate($PromDate)
    {

        $date = date_create_from_format('d.m.y H:i',$PromDate);

        return $date->format('Y-m-d H:i:s');

    }

    private function getPaymentType($promPaymentType){
        switch ($promPaymentType){
            case 'Безналичный расчет"': return 'waiting';
            default : return '';
        }
    }

    public function getLastOrders($lastOrderId){

        $orderList = $this->getOrders();

        ksort($orderList);

        $orderIds = array_keys($orderList);

        $offset = array_search($lastOrderId, $orderIds);

        $orders = array_slice($orderList,$offset+1);

        return  $orders;
    }
}