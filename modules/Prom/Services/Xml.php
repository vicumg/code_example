<?php
namespace App\Prom\Services;

class Xml
{

    /**
     * @param [] $products
     * @param [] $categories
     * @return string
     */
    public function makeYmlFeed($products,$categories)
    {

        $feed ='<?xml version="1.0" encoding="UTF-8"?>';
        $feed .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">';
        $feed .= '<yml_catalog date="'. date("Y-m-d H:i") .'">';
        $feed .= '<shop>';
        $feed .= '<currencies>';
        $feed .= '<currency id="UAH" rate="1" />';
        $feed .= '</currencies>';
        $feed .= $this->makeCategoryList($categories);
        $feed .= $this->makeProductList($products);
        $feed .= '</shop>';
        $feed .= '</yml_catalog>';
        return $feed;
    }

    private function makeCategoryList($categories)
    {
        $categoryFeed='';
        $categoryFeed .= '<categories>';

        foreach ($categories as $category){

            $categoryFeed .='<category id="'. $category['categoryId'] .'"';

            if ($category['parentId'] != 0 ){

                $categoryFeed .= ' parentId="'. $category['parentId'] .'">';

            }else{

                $categoryFeed .= '>';

            }



            $categoryFeed .= $category['name'] .'</category>';
        }

        $categoryFeed .= '</categories>';

        return $categoryFeed;
    }

    private function makeProductList($products)
    {
        $productFeed='';
        $productFeed .= '<offers>';

        foreach ($products as $product){

            $productFeed .= '<offer id="'. $product['offerId'] .'" selling_type="r">' ;

            $productFeed .='<name>'. $product['name'] .'</name>';
            $productFeed .='<categoryId>'. $product['categoryId'] .'</categoryId>';

            $productFeed .='<price>'. $product['price'] .'</price>';
            if (isset($product['oldprice'])){

                $productFeed .='<oldprice>'. $product['oldprice'] .'</oldprice>';
            }
            $productFeed .='<quantity_in_stock>'. $product['quantity_in_stock'] .'</quantity_in_stock>';
            $productFeed .='<currencyId>'. $product['currencyId'] .'</currencyId>';
            $productFeed .='<picture>'. $product['image'] .'</picture>';
            if (count($product['additional_images'])){
                foreach ($product['additional_images'] as $image){
                    $productFeed .='<picture>'. $image .'</picture>';
                }
            }

            $productFeed .='<barcode>'. $product['sku'] .'</barcode>';
            $productFeed .='<description>'. $product['description'] .'</description>';
            $productFeed .='<available>'. $product['available'] .'</available>';
            if (($product['keywords'])!=''){

                $productFeed .='<keywords>'. $product['keywords'] .'</keywords>';
            }
            $productFeed .='</offer>';
        }

        $productFeed .= '</offers>';

        return $productFeed;
    }

}