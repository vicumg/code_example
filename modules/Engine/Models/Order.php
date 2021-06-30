<?php
namespace App\Engine\Models;


use App\Prom\Services\PromApi;

class Order extends BaseModel
{

    const ORDER_CLOSED_STATUS =1;

    public function __construct()
    {

        parent::__construct();

        $this->table = 'orders';

    }

    public function getOrder($orderId){

        $sql = "SELECT * FROM " . $this->table . " WHERE order_id ='" .(int)$orderId ."'" ;

        return $this->db->query($sql)->row;

    }

    public function createOrder($orderData)
    {

       $orderData = $this->validateOrderData($orderData);

       $orderId = $this->addOrder($orderData);

       $this->addOrderProducts($orderId,$orderData);

       $this->productSubtraction(  $orderId);

    }


    private function addOrder($orderData)
    {

        $sql = "INSERT INTO ". $this->table ." 
        (       client_id,
                order_datetime,
                order_confirmed,
                order_dostavka,
                order_pay,
                order_type_pay,
                order_surname,
                order_name,
                order_patronymic,
                order_phone,
                region,
                city,
                otdilenie_np,
                city_ref,
                otdilenie_np_ref,
                street_ukrp,
                index_ukrp,
                order_fio,
                order_address,
                order_email,
                order_note,
                order_cost_price,
                order_discount,
                order_price,
                order_profit,
                predoplata,
                ttn,
                ves,
                dlina,
                insta_name,
                sobran,
                oplata_dostavki,
                zakaz_s_site,
                prom_order_id                
            )
            VALUES(  
                '" . $orderData['client_id'] . "',                           
                '" . $orderData['order_datetime'] . "',                           
                '" . $this->db->escape($orderData['order_confirmed']) . "',                           
                '" . $this->db->escape($orderData['order_dostavka']) . "',                           
                '" . $this->db->escape($orderData['order_pay']) . "',                           
                '" . $this->db->escape($orderData['order_type_pay']) . "',                           
                '" . $this->db->escape($orderData['order_surname']) . "',                           
                '" . $this->db->escape($orderData['order_name']) . "',                           
                '" . $this->db->escape($orderData['order_patronymic']) . "',                           
                '" . $this->db->escape($orderData['order_phone']) . "',                           
                '" . $this->db->escape($orderData['region']) . "',                           
                '" . $this->db->escape($orderData['city']) . "',                           
                '" . $this->db->escape($orderData['otdilenie_np']) . "',                           
                '" . $this->db->escape($orderData['city_ref']) . "',                           
                '" . $this->db->escape($orderData['otdilenie_np_ref']) . "',                           
                '" . $this->db->escape($orderData['street_ukrp']) . "',                           
                '" . (int)$orderData['index_ukrp'] . "',                           
                '" . $this->db->escape($orderData['order_fio']) . "',                           
                '" . $this->db->escape($orderData['order_address']) . "',                           
                '" . $this->db->escape($orderData['order_email']) . "',                           
                '" . $this->db->escape($orderData['order_note']) . "',                           
                '" . (float)$orderData['order_cost_price'] . "',                           
                '" . (float)$orderData['order_discount'] . "',                           
                '" . (float)$orderData['order_price'] . "',                           
                '" . (float)$orderData['order_profit'] . "',                           
                '" . (float)$orderData['predoplata'] . "',                           
                '" . $this->db->escape($orderData['ttn']) . "',                           
                '" . $this->db->escape($orderData['ves']) . "',                           
                '" . $this->db->escape($orderData['dlina']) . "',                           
                '" . $this->db->escape($orderData['insta_name']) . "',                           
                '" . (int)$orderData['sobran'] . "',                           
                '" . (int)$orderData['oplata_dostavki'] . "',                           
                '" . $orderData['zakaz_s_site'] . "',                           
                '" . (int)$orderData['prom_order_id'] . "'                         
                                       
                )";

        $this->db->query($sql);

        return  $this->db->lastId;

    }

    private function validateOrderData($orderData)
    {


        if (!isset($orderData['order_cost_price']) || $orderData['order_cost_price'] == 0){

            $orderData = $this->getOrderCostPrice($orderData);

        }

        return $orderData;
    }

    private function getOrderCostPrice($orderData)
    {

            $Product = new Product();



        foreach ($orderData['order_products'] as $orderProduct)
        {
            $productId = $orderProduct['product_id'];

            $productCostPrice = $Product->getProductCostPrice($productId);

            $orderData['order_cost_price'] += $productCostPrice*$orderProduct['quantity'];

        }

        $orderData ['order_profit'] =$orderData ['order_price'] -  $orderData['order_cost_price'];

        return $orderData;
    }

    private function addOrderProducts($orderId,$orderData)
    {

        foreach ($orderData['order_products'] as $orderProduct){

            $sql = "INSERT INTO `buy_products` 
                (buy_id_order,buy_id_product,buy_price_product,buy_count_product,buy_title_product)
                VALUES (". (int)$orderId .",
                        ". (int)$orderProduct['product_id'] .",
                        ". (float)$orderProduct['product_price'] .",
                        ". (int)$orderProduct['quantity'] .",
                        '". $this->db->escape($orderProduct['product_name'] )."')";

            $this->db->query($sql);
        }

    }

    private function productSubtraction($orderId)
    {

        $Product = new Product();

        $Product->productSubtractionByOrder($orderId);

    }

    public function getLastPromOrder()
    {

        $sql = "SELECT prom_order_id FROM `" . $this->table . "` order by prom_order_id desc limit 1";

        return (int)$this->db->query($sql)->row['prom_order_id'];
    }

    public function getLastPromOrderDate()
    {

        $sql = "SELECT order_datetime FROM `" . $this->table . "` order by prom_order_id desc limit 1";

        return $this->db->query($sql)->row['order_datetime'];
    }


    /**
     * @param $orderId
     * @param $data
     * @throws \Exception
     *
     *  $data array where key equal tp table field
     *  example : ['ttn'=>$ttn['ttn_number']
     */
    public function updateOrder($orderId,$data){

        $sqlData = "";

        foreach ($data as $key => $value){

            $sqlData .= $key ."='" .$value ."', ";

        }
        $sqlData = trim($sqlData);
        $sqlData = trim($sqlData,',');

        $slq = "UPDATE ". $this->table . " SET " . $sqlData . " WHERE order_id ='" . $orderId . "'";

        $this->db->query($slq);
    }



    public function migration2(){

        try{

            $result = $this->db->query("SELECT prom_order_id FROM `orders` limit 1");

        } catch (\Exception $e){

                    $this->db->query("ALTER TABLE `orders`
                                       ADD COLUMN `prom_order_id` INT NULL AFTER `zakaz_s_site`");

        }

    }

    /**
     * @param array $orderIds
     */
    public function closeOrders($orderIds){
        foreach ($orderIds as $orderId){

            $this->closeOrder((int)$orderId);
            $this->closePromOrder((int)$orderId);
        }

    }

    private function closeOrder($order_id){

        $sql = "UPDATE " .$this->table." SET order_closed='" . self::ORDER_CLOSED_STATUS . "' WHERE order_id='" . $order_id . "'";

        $this->db->query($sql);

    }

    public function getOrdersReadyForSms($orderList){

        $orderIds = [];

        foreach ($orderList as $orderId){

            $orderIds[]=(int)$orderId;
        }

        $sql = "SELECT order_id,order_phone,ttn FROM " . $this->table ."  
            WHERE order_phone <> '' AND ttn <> '' AND order_id in (". implode(',',$orderIds) .")";

        return $this->db->query($sql)->rows;
    }


    public function updateOrderPaymentStatus($order){

        $sql = "UPDATE " . $this->table . " SET 
        order_pay = '" . $this->db->escape($order['order_pay']) . "'
        WHERE prom_order_id ='" . $order['id'] ."'";

        $this->db->query($sql);

    }

    private function closePromOrder($orderId){

        $order = $this->getOrder($orderId);

        $promApi = new PromApi();

        $promApi->closePromOrder($order);

    }

    public function closePromOrders($orderIds){
        foreach ($orderIds as $orderId){

            $this->closePromOrder((int)$orderId);
        }

    }
}