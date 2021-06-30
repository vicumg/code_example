<?php
namespace App\Engine\Models;


class Category extends BaseModel
{
    public function __construct()
    {

        parent::__construct();

        $this->table = 'category';

    }


    public function getCategories()
    {

        $sql = "SELECT * FROM `". $this->table ."` ORDER BY id ASC";

        return $this->db->query($sql)->rows;

    }


    public function getCategoriesForFeed(){

         $results = $this->getCategories();

         $categories = [];

                 foreach ($results as $result){
                     $categories []=[
                         'categoryId'=>$result['id'],
                         'parentId'=>$result['type'],
                         'name'=>$result['brand'],
                     ];
                 }


         return $categories;

    }


    /**
     * @return mixed
     * @throws \Exception
     * <p> techical service only for migrations
     */
    public function getMainCategoriesTable()
    {
        $sql = "SELECT * FROM main_category";

        return $this->db->query($sql)->rows;

    }

    public function UpdateCategoriesAfterReplace()
    {

        $sql = "SELECT bc.id, bc.type, bc.brand, bc.position, nc.main_category_id, nc.id as new_type 
                FROM category bc left join category nc on bc.type = nc.main_category_id ";

        $categories = $this->db->query($sql)->rows;

        foreach ($categories as $category ){

            if ( $category['new_type'] != null ){
                $sql = "UPDATE category SET 
                        type=".$category['new_type']."
                        where id=".$category['id'];

                $this->db->query($sql);
            }

        }

    }






    public function addOldMainCategory( $maincategory ){

        $sql = "INSERT INTO `category` (type,brand,position, main_category_id)
                VALUES (" . 0 . ",'". $maincategory['main_cat'] ."',". $maincategory['position'] . ",". $maincategory['id'] .")";

        $this->db->query($sql);

    }

    public function migration3()
    {

        try{

            $result = $this->db->query("SELECT main_category_id FROM `category` limit 1");

        } catch (\Exception $e){

            $this->db->query("ALTER TABLE `category` 
                                    ADD COLUMN `main_category_id` INT NULL AFTER `position`");

        }

    }

}