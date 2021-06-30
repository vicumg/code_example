<?php

namespace App\Engine\ServiceProvider;


use App\Engine\Models\Order;
use App\Engine\Validator\OrderValidator;
use App\Prom\Services\NovaPoshtaProvider;
use App\Prom\Services\UkrPoshtaProvider;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Request;

class TtnMaker
{

    private $orderId;

    const ORDER_NOT_FOUND_MSG = "Заказ не найден";
    const BASE_ORDER_COST_VALUE = "200"; //base order cost value for NP
    const DEFAULT_ORDER_DESCRIPTION = "Материалы для наращивания ногтей"; //base order cost value for NP
    const DEFAULT_CARGO_NP_TYPE = "Parcel"; //base order cost value for NP
    const DEFAULT_SEATS_AMOUNT = "1"; //base order cost value for NP
    const DEFAULT_SHIPMENT_NP_TYPE = "WarehouseWarehouse"; //base order cost value for NP
    const DEFAULT_SHIPMENT_NP_PAY_TYPE = "Cash"; //base order cost value for NP
    const ERROR_TTN_CREATE = "Ошибка при созданиии ТТН"; //base order cost value for NP
    const DEFAULT_BACKWARD_MONEY_SHIP_PAYER = "Recipient"; //base order cost value for NP

    private $postProvider;

    public function __construct($orderId){

        $this->orderId = intval($orderId);

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function makeTtn():array{

        $orderData = $this->getOrderData();

        if (empty($orderData)){

            throw new \Exception(ORDER_NOT_FOUND_MSG );

        }

        $ttn = $this->makeDeliveryTtn($orderData);

        $Order = new Order();

        $Order->updateOrder($this->orderId,['ttn'=>$ttn['ttn_number']]);

        return $ttn;
    }

    /**
     * @return array of order data
     */
    private function getOrderData():array{

        $Order = new Order();

        return $Order->getOrder($this->orderId);
    }

    /**
     * @param $orderData
     * @return array
     * @throws \Exception
     */
    private function makeDeliveryTtn($orderData):array{

        if ($orderData['index_ukrp'] > 0 ){

            return $this->makeUPttn($orderData);

        }else{

            return $this->makeNPttn($orderData);

        }

    }


    public function validateOrder(){

        $order = $this->getOrderData();

        $orderValidator = new OrderValidator($order);

        $orderValidator->validateOrderCustomer();

        $orderValidator->validateOrderShipment();

        $name = $order['order_surname'] .' ' . $order['order_name'] .' ' . $order['order_patronymic'];

        $link = $this->getTTNLink($order);

        return ['name'=>$name,'ttn_link'=>$link,'ttn_number'=>$order['ttn']];
    }

    private function getTTNLink($orderData){

        if ($orderData['index_ukrp'] > 0 ){

            return $this->getUPTtnLink($orderData);

        }else{

            return $this->getNpTtnLink($orderData);

        }

    }

    private function getUPTtnLink($orderData){

        if ($orderData['ttn']=='' || !file_exists($_SERVER['DOCUMENT_ROOT'].'/admin/ttn/' . $orderData['ttn'] . '.pdf') ){
            return '';
        }


       return $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'].'/admin/ttn/' . $orderData['ttn'] . '.pdf';

    }

    private function getNpTtnLink ($orderData){

        if ($orderData['ttn']==''){
            return '';
        }

        $np =new NovaPoshtaProvider();
        return $np->getTtnLink(['Ref'=>$orderData['ttn']]);
    }

    /**
     * make TTN from "Нова Пошта"
     * @param $orderData
     * @return array
     * @throws \Exception
     */
    private function makeNPttn($orderData):array{

        $np = new NovaPoshtaProvider();

        $ttnData = [];
        $ttnData['sender'] = $this->getNpSender();
        $ttnData['recipient'] = $this->getNpRecipient($orderData);
        $ttnData['params'] = $this->getNpParams($orderData);


        $ttnNumber = $np->makeTtn($ttnData);

        if ($ttnNumber['success']){

             $ttnNumberString = $ttnNumber['data'][0]['IntDocNumber'];
             $link = $np->getTtnLink($ttnNumber['data'][0]);
            return  [
                'ttn_number'=>$ttnNumberString,
                'ttn_link'=> $link
            ];


        }else{

            throw new \Exception(implode(' ; ', $ttnNumber['errors']));

        }
    }

    private function getNpSender(){

        $sender = [
            'LastName' => $sender['LastName'],
            'FirstName' => $sender['FirstName'],
            'MiddleName' => $sender['MiddleName'],
            'CitySender' => 'e71a0c98-4b33-11e4-ab6d-005056801329',
            'SenderAddress' => '696fd5fe-9bf7-11e4-acce-0050568002cf',
        ];

        return $sender;
    }

    /**
     * Prepare recipientData for NP
     * @param $orderData
     * @return array
     */
    private function getNpRecipient($orderData):array{

        $recipient = [

                'FirstName' => $orderData['order_name'],
                'MiddleName' => $orderData['order_patronymic'],
                'LastName' => $orderData['order_surname'],
                'Phone' => $orderData['order_phone'],
                'City' => $orderData['city'],
                'Region' => $orderData['region'],
                'CityRecipient' => $orderData['city_ref'],
                'RecipientAddress' => $orderData['otdilenie_np_ref'],
                'Warehouse' => $orderData['otdilenie_np'],

        ];

        return $recipient;
    }

    /**
     * prepare params for
     * @param $orderData
     * @return array
     */
    private function getNpParams($orderData):array{

        $request = Request::createFromGlobals();

        $length = (int)$request->query->get('length');
        $width = (int)$request->query->get('width');
        $height = (int)$request->query->get('height');
        $weight = floatval($request->query->get('weight'));
        $VolumeGeneral = ($length /100) * ($width/100) * ($height/100);

        $PayerType = ($orderData['oplata_dostavki'] == 0)? 'Recipient' : 'Sender';

        // sum for backward money and parcel value cost for parcels with backward money
        $Cost = $orderData['order_price'] -  $orderData['predoplata'];

        $isOrderPaid = ($orderData["order_pay"] == "accepted");

        $orderCostValue = ($isOrderPaid)? self::BASE_ORDER_COST_VALUE : $Cost;

        $params = [
            'DateTime' => date('d.m.Y', time() + 0 * 84600),
            'ServiceType' => self::DEFAULT_SHIPMENT_NP_TYPE,
            'PaymentMethod' => self::DEFAULT_SHIPMENT_NP_PAY_TYPE,
            'PayerType' => $PayerType,
            'Cost' => $orderCostValue,
            'SeatsAmount' => self::DEFAULT_SEATS_AMOUNT,
            'Description' => self::DEFAULT_ORDER_DESCRIPTION,
            'CargoType' => self::DEFAULT_CARGO_NP_TYPE,
            'Weight' =>  $weight,
            'VolumeGeneral'=>$VolumeGeneral,

        ];

        $params['OptionsSeat'] = [
                        [
                            "volumetricVolume"=>$VolumeGeneral,
                            "volumetricWidth"=> (string)$width,
                            "volumetricLength"=> (string)$length,
                            "volumetricHeight"=>  (string)$height,
                            "weight"=> (string)$weight
                        ]
                    ];

        if (!$isOrderPaid){
                $params['BackwardDeliveryData'] = [
                    [
                        // Кто оплачивает обратную доставку
                        'PayerType' => self::DEFAULT_BACKWARD_MONEY_SHIP_PAYER,
                        // Тип доставки
                        'CargoType' => 'Money',
                        // Значение обратной доставки
                        'RedeliveryString' => $Cost,
                    ]
                ];
        }



        return $params;
    }

    /**
     * @param $orderData
     * @return array
     * @throws \Exception
     */
    private function makeUPttn($orderData):array{

        $this->validateUPorder($orderData);

        $this->postProvider = new UkrPoshtaProvider();

        return $this->postProvider->makeTTN($orderData);

    }


    private function validateUPorder($orderData){

        $validator = new OrderValidator($orderData);

        $validator->validateOrderCustomer();

        $validator->validateOrderShipment();

    }

}