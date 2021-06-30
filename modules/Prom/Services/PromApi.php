<?php


namespace App\Prom\Services;

use App\Engine\Models\Order;
use App\Engine\Models\Product;
use App\Engine\ServiceProvider\LiqpayProvider;

class PromApi
{
    const AUTH_TOKEN ='';
    const HOST = 'my.prom.ua';

    const URL_ORDERS_LIST = '/api/v1/orders/list';
    const URL_GET_PRODUCT_BY_EXTERNAL_ID = '/api/v1/products/by_external_id/';
    const URL_EDIT_PRODUCT_BY_PROM_ID = '/api/v1/products/edit';
    const URL_EDIT_PRODUCT_BY_EXTERNAL_ID = '/api/v1/products/edit_by_external_id';
    const URL_SAVE_DECLARATION_ID = '/api/v1/delivery/save_declaration_id';
    const URL_SET_STATUS= '/api/v1/orders/set_status';
    const URL_GET_ORDER= '/api/v1/orders/';




    private $ordersForUpdate = [];

    private $PaymentStatuses = [
        'waiting'=>'waiting',
        'paid'=>'accepted',
        'unknown'=>'',
    ];


    private $PromStatus =[
        'pending', 'received', 'delivered', 'canceled', 'draft', 'paid'];


    private $DeliveryTypes =[
        'np'=>'nova_poshta'
    ];

    public function getOrders()
    {

        return $this->getOrderList();

    }

    /**
     * @param $method
     * @param $url
     * @param array $body
     * @return mixed
     */
    private function makeRequest($method, $url, $body = []) {
        $headers = array (
            'Authorization: Bearer ' . self::AUTH_TOKEN,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . self::HOST . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        $tmpRes = json_decode($result, true);
        return $tmpRes;
    }

    private function getOrderList($lastOrderDate = NULL) {

        $url = self::URL_ORDERS_LIST;

        if ( !is_null($lastOrderDate) )
        {
            $url .= '?'.http_build_query(array('date_from'=>$lastOrderDate));
        }
        $method = 'GET';
      //  $url .= '?'.http_build_query(array('limit'=>70));
        return  $this->makeRequest($method, $url, NULL);
    }

    /**
     * @param $products <p>format array  due to prom api
     * @return bool|mixed
     *
     * return false if not found products by external id
     */
    public function editProductExternalId($products){

        $promProducts =[];

        foreach ($products as $product ){

            $promId = $this->getPromProductId( $product['id']);

            if ( $promId === false ) continue;

            $promProducts[]= $product;

        }

        $result = false;

        if (count($promProducts)){

            $result = $this->editPromProductExternalID( $promProducts);
        }

        return $result;
    }

    private function editPromProductExternalID($product)
    {

        $url = self::URL_EDIT_PRODUCT_BY_EXTERNAL_ID;

        $method = 'POST';

        return  $this->makeRequest($method, $url, $product);

    }

    public function saveShipmentDeclaration($promOrderId, $declarationNumber ){

        $url = self::URL_SAVE_DECLARATION_ID;

        $method = 'POST';

        if (strlen($declarationNumber) == 14){
            $deliveryMethod = $this->DeliveryTypes['np'];
        }else{
            return ['status'=>'success'];
        }

        $data=[
            'order_id'=>$promOrderId,
            'declaration_id'=>$declarationNumber,
            'delivery_type'=>$deliveryMethod
        ];

        return  $this->makeRequest($method, $url, $data);

    }

    private function setPromOrderStatus($data){

        $url = self::URL_SET_STATUS;

        $method = 'POST';

        return $this->makeRequest($method, $url, $data);
    }


    public function showOrder($orderId){

        $url = self::URL_GET_ORDER . $orderId;

        $method = 'GET';

        return  $this->makeRequest($method, $url, NULL);
    }

    public function closePromOrder($order){

        $promOrderId = (int)$order['prom_order_id'];

        if (!$promOrderId) return;

        $setTtn = $this->saveShipmentDeclaration($promOrderId, $order['ttn']);

        $result =false;

        if ($setTtn['status'] =='success'){
            $result = $this->setPromOrderStatus(
                ['status'=>'delivered',
                    "ids"=> [$promOrderId]

                ]
            );
        }

        return $result;

    }

    private function getPromProductId($promExternalId)
    {
        $url = self::URL_GET_PRODUCT_BY_EXTERNAL_ID.$promExternalId;

        $method = 'GET';

        $request = $this->makeRequest($method, $url);

        if (isset($request['product']['id']) && ($request['product']['external_id'] == $promExternalId) ){

          return  $request['product']['id'];

        }

        return false;
    }

    public function getLastOrders($lastOrderId)
    {
        $lastOrderDate= date('Y-m-d\TH:i:sO',strtotime("-1 days"));

        $ordersData = $this->getOrderList($lastOrderDate);

        $orderList = [];

        foreach ( $ordersData['orders'] as $order){


            if ( $order['id'] > $lastOrderId){

                $orderList[] =  $this->prepareOrder($order);

             }elseif ( '155722341' == $order['id']){

               $this->ordersForUpdate[] = $this->prepareOrderUpdate($order);
            }
        }

        $orders = $this->formatOrders($orderList);

        ksort($orders);

        return  $orders;
    }

    private function formatOrders($orderList)
    {

        $orders = [];

        foreach ( $orderList as $orderData ){

            $strPrice = str_replace([' ',"\xC2\xA0",'грн.'],'',$orderData['price']);

            $price = (float)$strPrice;

            $orders [$orderData['id']] = [
                'client_id'=>0,
                'order_datetime'=>date('Y-m-d H:i:s',strtotime($orderData['date_created'])),
                'order_confirmed'=>'no',
                'order_dostavka'=>$orderData['delivery_option']['name'],
                'order_pay'=>$orderData['order_pay'],
                'order_type_pay'=>'',
                'order_surname'=>$orderData['client_last_name'],
                'order_name'=>$orderData['client_first_name'],
                'order_patronymic'=>$orderData['client_second_name'],
                'order_phone'=>trim($orderData['phone'],'+'),
                'region'=>$orderData['region'],
                'city'=>$orderData['city'],
                'otdilenie_np'=>$orderData['otdilenie_np'],
                'city_ref'=>$orderData['city_ref'],
                'otdilenie_np_ref'=>$orderData['otdilenie_np_ref'],
                'street_ukrp'=>$orderData['delivery_address'],
                'index_ukrp'=>$orderData['index_ukrp'],
                'order_fio'=>'',
                'order_address'=>'',
                'order_email'=>$orderData['email'],
                'order_note'=>'',
                'order_cost_price'=>0,
                'order_discount'=>0,
                'order_price'=>$price,
                'order_profit'=>0,
                'predoplata'=>0,
                'ttn'=>'',
                'ves'=>'',
                'dlina'=>'',
                'insta_name'=>'',
                'sobran'=>0,
                'oplata_dostavki'=>0,
                'zakaz_s_site'=>2,
                'prom_order_id'=>$orderData['id'],
                'order_products'=>$this->getProducts($orderData['products']),
            ];
        }
        return  $orders;
    }

    private function getProducts($orderProducts)
    {

        $products=[];

        foreach ($orderProducts as $product){

            $products[]=[
                'product_name'=>$product['name'],
                'sku'=>$product['sku'],
                'product_id'=>(int)$product['external_id'],
                'product_price'=>(float)($product['price']),
                'quantity'=>$product['quantity'],
            ];
        }

        return $products;
    }

    private function prepareOrderUpdate($order){


        return $this->fillPayment($order);
    }


    private function prepareOrder($order){
        $deliveryData = $order['delivery_provider_data'];

        if (AddressProvider::isExistProver($deliveryData)){

            $addressFill = new AddressProvider();

            $order =  $addressFill->getAddressData($order);

        }

        $order = $this->fillPayment($order);

        return $order;
    }

    private function fillPayment($order){

        $promPayment = trim($order['payment_option']['name']);
        switch ($promPayment){
            case 'Пром-оплата': $order['order_pay'] = $this->getPromPaymentStatus($order);break;
            case 'Оплата картой Visa, Mastercard - LiqPay': $order['order_pay'] = $this->getLiqPayPaymentSatus($order);break;
            case 'Оплата на карту': $order['order_pay'] = $this->getCardPaymentStatus($order);break;
            default: $order['order_pay'] = '';
        }

        return $order;
    }

    private function getPromPaymentStatus($order){

        if ($order['payment_data']['status'] == 'paid'){
            return $this->PaymentStatuses['paid'];
        }else{
            return $this->PaymentStatuses['waiting'];
        }

    }
    private function getLiqPayPaymentSatus($order){

        $paymentProvider  = new LiqpayProvider();
        $orderStatus = $paymentProvider->getOrderStatus($order['id']);

        if ($orderStatus){
            return $this->PaymentStatuses['paid'];
        }else{
            return $this->PaymentStatuses['waiting'];
        }

    }

    public function getCardPaymentStatus($order){

        return $this->PaymentStatuses['waiting'];

    }

    public function getOrdersForUpdate(){

        return $this->ordersForUpdate;

    }

    public function cancelOrder($orderId){

        $Order = new Order();
        $promOrder = $Order->getOrder($orderId);
        if ($promOrder['prom_order_id'] !=''){
           return $this->cancelPromOrder($promOrder['prom_order_id']);
        }

        return false;
    }

    private function cancelPromOrder($promOrderId){

        return $this->setPromOrderStatus(
            ['status'=>'canceled',
                "ids"=> [$promOrderId],
                "cancellation_reason"=>"buyers_request"
            ]
        );
    }

}