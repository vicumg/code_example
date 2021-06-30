<?php
namespace App\Prom\Controllers;

use App\Engine\Models\Category;
use App\Engine\Models\Product;
use App\Prom\Services\Xml;

Class PromFeed
{
    const YML_HEADER = 'Content-type: application/xml';
    const XML_HEADER = 'Content-type: application/xml';

    public function run($feedType)
    {
        switch ($feedType){
            case 'yml':  $this->YmlFeed();
                    break;

        case 'xml':  $this->XmlFeed();
                break;

        }

    }

    private function YmlFeed()
    {
        $products =  $this->getProductsForFeed();

        $categories = $this->getCategoriesForFeed();

        $xml = new Xml();

        $feed = $xml->makeYmlFeed($products,$categories);

        $this->sendYmlFeed( $feed);

    }


    private function getProductsForFeed()
    {
        $Product = new Product();

        $products  = $Product->getProductsForFeed();

        foreach ( $products as $key=>$product ){


            $products[$key]['name'] = $this->makeXmlName($product['name']);


            if ( $product['description'] == '' ) {

                $products[$key]['description'] =$products[$key]['name'];

            }else{

                $description = $this->makeXmlDescription($product['description']);
                $products[$key]['description'] = $description;
            }

            if (   $product['image'] != '' ){

                $products[$key]['image'] = IMAGE_PATH.$product['image'];
            }
            $products[$key]['additional_images'] = [];
            if (count($product['additional_images'])){
                foreach ($product['additional_images'] as $image)
                    $products[$key]['additional_images'][] = $image;
            }

            $keywordsArray = explode(',', $product['keywords']);

            $keywords = implode(',',array_map('trim',$keywordsArray));

            $products[$key]['keywords'] = $keywords;

        }

        return $products;
    }

    private function getCategoriesForFeed()
    {
        $Category = new Category();

        $categories  = $Category->getCategoriesForFeed();

        return  $categories;

    }


    /**
     * @param string $feed
     */
    private  function sendYmlFeed( $feed)
    {
        header(self::YML_HEADER);
        echo ($feed);
        exit();
    }


    private function XmlFeed()
    {

    }

    private function makeXmlFeed()
    {


    }

    private  function sendXmlFeed( $feed)
    {


    }

    private function makeXmlName($name)
    {
        return str_replace('&','and', $name);
    }

    private function makeXmlDescription($description)
    {

        return '<![CDATA['.trim ($description ,"\t\n\r\0\x0B").']]>';

    }
}