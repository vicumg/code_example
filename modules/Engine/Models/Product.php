<?php
namespace App\Engine\Models;
/**
 * Class Product
 * @package App\Engine\Models
 *
 *
 */

class Product extends BaseModel
{
    public function __construct()
    {

        parent::__construct();

        $this->table = 'table_products';

    }


    public function getProducts()
    {

        $sql = "SELECT * FROM `". $this->table ."` ORDER BY products_id ASC";

        return $this->db->query($sql)->rows;

    }

    public function getProduct($productId)
    {

        $sql = "SELECT * FROM `". $this->table ."` WHERE products_id = ".(int)$productId;


        return $this->db->query($sql)->row;

    }

    public function getProductsForFeed(){

         $results = $this->getProducts();

         $products = [];

                 foreach ($results as $result){

                     $product=[];

                     if ($result['price'] ==0 ){

                         continue;

                     }

                     $product=[
                         'offerId'=>$result['products_id'],
                         'available'=>($result['visible'] == 1)? 'true':'',
                         'name'=>$result['title'],
                         'categoryId'=>$result['brand_id'] ? $result['brand_id'] : $result['type_tovara'],
                         'price'=> $result['price'],
                         'currencyId'=> 'UAH',
                         'quantity_in_stock'=> $result['count_w_mod'],
                         'image'=> $result['image'],
                         'sku'=> '4GIRL-'.$result['products_id'],
                         'keywords'=> $result['seo_words'],
                         'description'=> $result['description'],
                         'additional_images'=>$this->getAdditionalImages($result['products_id'])
                     ];

                        if ($result['price_sale'] !=0 ){

                            $product['oldprice'] = $result['price_sale'];

                        }


                     $products []=$product;

                 }


         return $products;

    }

    public function getChangedProducts($period)
    {

        $sql="SELECT products_id, TIMESTAMPDIFF(MINUTE,date_modified,NOW()) as timedif 
              FROM `". $this->table ."` having timedif < ". $period;

        $results = $this->db->query($sql)->rows;

        $products =[];

        if (count($results)){

            foreach ($results as $result){

                $products[]=$result['products_id'];

            }
        }

        return $products;
    }

    public function getProductCostPrice($productId)
    {

        $sql = "SELECT cost_price FROM `" . $this->table. "` where products_id=".(int)$productId;

        return (float)$this->db->query($sql)->row['cost_price'];

    }

    public function productSubtractionByOrder($orderId)
    {
        $productsInOrders = $this->db->query("SELECT buy_id_product as product_id, buy_count_product as count_w_mod
            FROM `buy_products` WHERE buy_id_order = '" . (int)$orderId . "'")->rows;

        if (count( $productsInOrders)){

            foreach ($productsInOrders as $product){

                $this->db->query("UPDATE `table_products` 
                SET count_w_mod = (count_w_mod - " . (int)$product['count_w_mod'] . ") 
                WHERE products_id = '" . (int)$product['product_id'] ."'");

            }

        }

    }

    public function UpdateProductTypeTovara()
    {

        $sql = "SELECT products_id, c.id as new_type_tovara FROM table_products p
                left join category c on p.type_tovara = c.main_category_id";


        $products = $this->db->query($sql)->rows;

        foreach ($products as $product){

            $this->db->query("UPDATE `table_products` 
                SET type_tovara = ". $product['new_type_tovara'] . "
                WHERE products_id = " . (int)$product['products_id'] );

        }

    }




    /**
     * migration due to prom api
     */
    public function migration1()
    {

        try{

            $result = $this->db->query("SELECT date_modified FROM `table_products` limit 1");

        } catch (\Exception $e){

           $this->db->query("ALTER TABLE `table_products` ADD `date_modified` datetime  DEFAULT NOW() ON UPDATE NOW()");

        }

    }

    public function getAdditionalImages($products_id){

        $sql = "SELECT image FROM `uploads_images` where products_id=$products_id";

        $images = [];
        foreach ($this->db->query($sql)->rows as $image){
            if (is_file(IMAGE_FILE_PATH.$image['image']))
            $images [] = IMAGE_PATH.$image['image'];
        }

        return $images;
        
        
    }

}