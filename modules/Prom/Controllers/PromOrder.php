<?php

namespace App\Prom\Controllers;


use App\Engine\Models\Order;
use App\Prom\Services\PromApi;

class PromOrder
{

    public function run($method)
    {
        switch($method){
            case 'xml': $this->updateByXml();break;
            case 'api': $this->updateByApi();break;
        }

    }

    private function updateByXml(){

        $promXMLOrder = new PromXmlOrder();

        $lastOrderId = $this->getLastPromOrder();

        $orders =  $promXMLOrder->getLastOrders($lastOrderId);

        $this->addOrders($orders);
    }

    private function updateByApi()
    {
        $PromApi = new PromApi();

        $lastOrderId = $this->getLastPromOrder();

        $orders = $PromApi->getLastOrders( $lastOrderId);

        $this->addOrders($orders);

        $this->updateOrders($PromApi->getOrdersForUpdate());

    }


    private function addOrders($promOrders){

        $Order = new Order();

        foreach ($promOrders as $promOrder){

            $Order->createOrder($promOrder);

        }

    }

    private function updateOrders($promOrders){

        $Order = new Order();

        foreach ($promOrders as $promOrder){

            $Order->updateOrderPaymentStatus($promOrder);

        }
    }


    private function getLastPromOrder()
    {

        $Order = new Order();

        $lastPromOrder = $Order->getLastPromOrder();

        return  $lastPromOrder;
    }

    private function getLastPromOrderDate()
    {

        $Order = new Order();

        $lastPromOrderDate = $Order->getLastPromOrderDate();

        return  $lastPromOrderDate;
    }

}