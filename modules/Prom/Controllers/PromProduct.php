<?php


namespace App\Prom\Controllers;


use App\Engine\Models\Product;
use App\Prom\Services\PromApi;

class PromProduct
{

    /**
     * @param array $productIds
     */
    public function updateQuantityPriceProduct($productIds)
    {

        $Product = new Product();

        $products=[];

        foreach ($productIds as $productId ){

            $products[] = $this->prepareQuantityPrice($Product->getProduct($productId));

        }

        $promApi = new PromApi();

        $result = $promApi->editProductExternalId($products);

        return (count($result['errors']) === 0 );

    }

    private function prepareQuantityPrice($product){


        $quantity = ($product['count_w_mod'] > 0) ? (int)$product['count_w_mod'] : 0 ;

        if ($product['visible'] ==1 &&  $quantity ){
            $presence = 'available';
        }else{
            $presence = 'not_available';
        }


        if( (float)$product['price_sale'] !=0 ){
            $price = (float)$product['price_sale'];
        }else{
            $price = (float)$product['price'];
        }



        $promProduct = [
            'id'=>$product['products_id'],
            'price'=>$price,
            'quantity_in_stock'=> $quantity,
            'presence'=>$presence,
        ];

        return $promProduct;
    }

    /**
     * @param int $period <p> period in minutes for changed products
     */
    public function syncProduct($period)
    {

        $Product = new Product();

        $changedProducts = $Product->getChangedProducts((int)$period);

        $result = false;

        if (count($changedProducts)){

            $result = $this->updateQuantityPriceProduct($changedProducts);

        }

        return $result;

    }

}