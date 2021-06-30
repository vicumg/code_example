<?php


namespace App\Prom\Services;


class AddressProvider
{
    const NP_PROVIDER_PROM_NAME = 'nova_poshta';

    const UP_PROVIDER_PROM_NAME = 'ukrposhta';

    private static $existProviders = ['nova_poshta','ukrposhta'];


    private function getUkrPoshtaAddress( $order ){

        $provider = new UkrPoshtaProvider();

        $orderIndex = $order['delivery_provider_data']['recipient_warehouse_id'];

        if  ($orderIndex) {

            $deliveryData = $provider->getPostOffice($orderIndex);

            if ( $orderIndex == $deliveryData->POSTINDEX){
                $order['index_ukrp'] = $deliveryData->POSTINDEX;
                $order['region'] = $deliveryData->DISTRICT_RU;
                $order['city'] = $deliveryData->CITY_RU;

            }else{
                $order['index_ukrp'] = '';
                $order['index_ukrp'] = '';
                $order['region'] = '';
                $order['city'] = '';
            }

        }else{
            $order['index_ukrp'] = '';
            $order['region'] = '';
            $order['city'] = '';
        }
        $order['otdilenie_np_ref'] = '';
        $order['otdilenie_np'] = '';
        $order['city_ref'] = '';

        return $order;
    }

    private function getNovaPoshtaAddress( $order ){
        $provider = new NovaPoshtaProvider();

        $deliveryData = $provider->getPostOffice($order['delivery_provider_data']['recipient_warehouse_id']);

        $order['index_ukrp'] = '';
        $order['region'] = $deliveryData['area_ua'];
        $order['city'] = $deliveryData['city_name_ua'];
        $order['otdilenie_np_ref'] = $deliveryData['warehouse_ref'];
        $order['otdilenie_np'] = $deliveryData['description_ua'];
        $order['city_ref'] = $deliveryData['warehouse_city_ref'];

        return $order;
    }

    public function getAddressData( $order )
    {
        if (self::NP_PROVIDER_PROM_NAME === $order['delivery_provider_data']['provider']) {

            $order =  $this->getNovaPoshtaAddress($order);

        } else if (self::UP_PROVIDER_PROM_NAME === $order['delivery_provider_data']['provider']) {

            $order =  $this->getUkrPoshtaAddress($order);

        }

        return $order;
    }

    public static function isExistProver($deliveryData)
    {

        return (in_array($deliveryData['provider'],static::$existProviders));

    }
}