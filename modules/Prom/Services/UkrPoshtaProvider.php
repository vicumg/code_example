<?php


namespace App\Prom\Services;

use App\Engine\Models\UpParcel;
use App\Engine\Models\UpRecipient;
use App\Engine\ServiceProvider\Logger;
use Symfony\Component\HttpFoundation\Request;
use App\Engine\Validator\OrderValidator;
use Dotenv\Validator;
use Ukrpochta\Pochta;


class UkrPoshtaProvider
{
    private $key = '';

    private $provider;

    const IBAN='';

    const TOKEN_COUNTERPARTY='';

    const UUID_COUNTERPARTY = ''; // for client make

    const SENDER_ADDRESS_ID = ''; /*
    postcode'=> '85302',
    'region'=> 'Донецька',
    'district'=> 'Покровський',
    'city'            => 'Покровськ',
    'street'          => 'Богдана Хмельницького',
    'houseNumber'     => '14',
    'apartmentNumber' => '1',
 */

    const SENDER_CLIENT_UUID = '';// '000001'

    const DEFAULT_PARCEL_TYPE ='';

    const DEFAULT_DELIVERY_TYPE ='W2W';

    const BASE_ORDER_COST_VALUE =200;

    private $logger;


    public function __construct()
    {
        $this->provider = new Pochta($this->key);
        $this->logger = new Logger();
    }

    public function getPostOffice( $postOfficeNumber )
    {
        $postOfficeData = \GuzzleHttp\json_decode($this->provider->getPostOffice($postOfficeNumber));

        return $postOfficeData->Entries->Entry[0];
    }

    public function getCityByPostOffice($postOfficeNumber)
    {
        $postOfficeData = $this->getPostOffice($postOfficeNumber);
        $test='';
    }

    /**
     * @param $orderData
     * @return array
     * @throws \Exception
     */
    public function makeTTN($orderData){

        $address = $this->makeAddress($orderData);

        $reciever = $this->makeReceiver($orderData, $address);

        if (empty($reciever)){
            throw new \Exception('Ошибка при создании посылки');
        }


        $parcel =  $this->makeParcel($orderData,$reciever);


        $this->provider->createForm($parcel['uuid'], self::TOKEN_COUNTERPARTY, STORAGE_TTN_FOLDER . $parcel['barcode'].'.pdf' );

        $link = $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'].'/admin/ttn/' . $parcel['barcode'] . '.pdf';
        return [
            'ttn_number'=>$parcel['barcode'],
            'ttn_link'=>$link
        ];

    }

    public function makeAddress($orderData){

        $index = $orderData['index_ukrp'];
        if (strlen($index) < 5){

            $index ='0'.$index;
        }

         $address = $this->provider->createAddress( [
            'postcode'        => $index,
            'city'            => $orderData['city'],
        ]);

         if (!empty($address['fieldErrors'])){
             throw new \Exception($address['fieldErrors'][0]['message']);
         }

         return $address;
    }

    public function makeReceiver($orderData,$address){



        $name = $orderData['order_surname'] . ' ' . $orderData['order_name'] . ' ' . $orderData['order_patronymic'];

        $receiverData =  [
            'name'                     => $name,
            'firstName'               => $orderData['order_name'],
            'lastName'               => $orderData['order_surname'],
            'addressId'                => $address['id'],
            'phoneNumber'              => $orderData['order_phone'],
            'type'                     => 'INDIVIDUAL',
        ];

        if ($orderData['client_id']){
            $receiverData['externalId'] = $orderData['client_id'];
        }

        $receiver = $this->provider->createClient(self::TOKEN_COUNTERPARTY,$receiverData );

        if (!empty($receiver['fieldErrors'])){

            throw new \Exception($receiver['fieldErrors'][0]['message']);

        }

        return json_decode($receiver,true);
    }

    private function makeParcel($orderData,$receiver, $senderId = self::SENDER_CLIENT_UUID){


        $request = Request::createFromGlobals();

        $length = (int)$request->query->get('length');
        $width = (int)$request->query->get('width');
        $height = (int)$request->query->get('height');
        $weight = (int)floatval($request->query->get('weight')*1000);

        $maxSide = max([$length,$width,$height]);


        $sender = [
            'uuid'=>$senderId,
        ];
        $recipient=[
            'uuid'=>$receiver['uuid']
        ];

        $PayerType = ($orderData['oplata_dostavki'] == 0)? 'Recipient' : 'Sender';


        // sum for backward money and parcel value cost for parcels with backward money
        $Cost = $orderData['order_price'] -  $orderData['predoplata'];

        $isOrderPaid = ($orderData["order_pay"] == "accepted");

        $orderCostValue = ($isOrderPaid)? self::BASE_ORDER_COST_VALUE : $Cost;




        $parcels = [
            [
                'weight' =>$weight,
                'length' => $maxSide,
               'declaredPrice' => $orderCostValue
            ]
        ];

        $parcelData = [
            'sender'    =>$sender,
            'recipient'=>$recipient,
            'deliveryType'=>self::DEFAULT_DELIVERY_TYPE,
            'parcels' =>$parcels,

        ];

        $parcelData['paidByRecipient'] = ( $PayerType == 'Recipient' );

        if (!$isOrderPaid){

            $parcelData['postPay'] =$Cost;
            $parcelData['checkOnDelivery'] = true;
            $parcelData['postPayPaymentType']= "POSTPAY_PAYMENT_CASH_AND_CASHLESS";
            $parcelData['transferPostPayToBankAccount']=true;
            $parcelData['onFailReceiveType']="RETURN_AFTER_7_DAYS";

        }


            $parcelResult = $this->createParcelByApi($parcelData);

            $parcel = json_decode($parcelResult,true);

            if (isset($parcel['code']) || !isset($parcel['uuid']) ){

                throw new \Exception($parcel['message']);

            }

            try{
              $this->saveParcelData($parcel);
            }catch (\Exception $e){
                $this->logger->logError($e->getMessage());
            }


        return $parcel;
    }

    private function createParcelByApi($parcelData){

        $bonusParcels = $this->isBonusParcels();

        if ($bonusParcels){

            return $this->provider->createBonusParcel(self::TOKEN_COUNTERPARTY,$parcelData);

        }else{

            return $this->provider->createParcel(self::TOKEN_COUNTERPARTY,$parcelData);

        }
    }

    private function isBonusParcels(){



        $result = $this->provider->getBonusParcels(self::SENDER_CLIENT_UUID, self::TOKEN_COUNTERPARTY);

        return $result;
    }


    /**
     * @param $parcel
     * @return string
     */
    private function saveParcelData($parcel){

        $recipientId = $this->saveRecipient($parcel['recipient']);



        return $this->saveParcel($parcel, $recipientId);
    }

    /**
     * @param $recipientData
     * @return string
     */
    private function saveRecipient($recipientData){

        $Recipient = new UpRecipient();

        return $Recipient->saveRecipient($recipientData);
    }

    /**
     * @param $parcelData
     * @param $recipientId
     */
    private function saveParcel($parcelData, $recipientId){

        $Parcel = new UpParcel();

        $Parcel->saveParcel($parcelData,$recipientId);

    }
}