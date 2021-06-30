<?php


namespace App\Prom\Services;


use App\Engine\Models\NpWarehouse;
use Ukrpochta\Pochta;
use LisDev\Delivery\NovaPoshtaApi2;

class NovaPoshtaProvider
{
    private $key =''; //

    private $provider;

    private $WarehouseModel;

    public function __construct()
    {
        $this->provider =new NovaPoshtaApi2($this->key);

        $this->WarehouseModel = new NpWarehouse();
    }

    /**
     * @param string $postOfficeNumber
     * @return object
     */
    public function getPostOffice( $warehouse_ref )
    {

        return $this->getPostOfficeData($warehouse_ref);

    }

    /**
     * @param string $postOfficeNumber
     */
    public function getCityByPostOffice($warehouse_ref)
    {

    }

    /**
     * get post office list from NP api
     *
     * @return array|\LisDev\Delivery\json|mixed|string
     */
    private function getWareHouseList(){

        return $this->provider->getWarehouses('');
    }

    public function updateWarehouses(){

        $wareHouses = $this->getWareHouseList();

        if ($wareHouses['success'] !=true){
            return false;
        }
        $wareHouseList = [];

        foreach ( $wareHouses['data'] as $wareHouse){
            $wareHouseList[]=[
                'warehouse_ref'=>$wareHouse['Ref'],
                'warehouse_number'=>(int)$wareHouse['Number'],
                'city_name_ua'=>$wareHouse['CityDescription'],
                'city_name_ru'=>$wareHouse['CityDescriptionRu'],
                'warehouse_city_ref'=>$wareHouse['CityRef'],
                'description_ua'=>$wareHouse['Description'],
                'description_ru'=>$wareHouse['DescriptionRu'],
                'area'=>$wareHouse['SettlementAreaDescription'],
                'region'=>$wareHouse['SettlementRegionsDescription'],
            ];
        }


        return $this->updateWarehouseList($wareHouseList);
    }

    private function updateWarehouseList($wareHouseList)
    {


        try{
            $this->WarehouseModel->updateWarehouses($wareHouseList);

        }catch (\Exception $e){

            echo $e->getMessage();

        }

        return true;
    }

    /**
     * @param string $warehouse_ref
     * @return array|mixed
     */
    private function getPostOfficeData($warehouse_ref){

        $wareHouse=[];

        try{

            $wareHouse =  $this->WarehouseModel->getWarehouseByRef($warehouse_ref);

        }catch (\Exception $e){

            echo $e->getMessage();

        }
        return $wareHouse;
    }

    public function makeTtn($ttnData){

        $sender = $ttnData['sender'];
        $recipient = $ttnData['recipient'];
        $params = $ttnData['params'];

        $ttn = $this->provider->newInternetDocument($sender,$recipient,$params);

        return $ttn;

    }

    public function getTtnLink($ttn){

        $printData = $this->provider->printMarkings($ttn['Ref'],'pdf_link');

       return $printData['data'][0];

    }

}