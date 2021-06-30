<?php
namespace App\Engine\Models;
/**
 * Class Product
 * @package App\Engine\Models
 *
 *
 */

class NpWarehouse extends BaseModel
{
    public function __construct()
    {

        parent::__construct();

        $this->table = 'table_np_warehouse';

    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getWarehouses()
    {

        $sql = "SELECT * FROM `". $this->table ."` ORDER BY warehouse_id ASC";

        return $this->db->query($sql)->rows;
    }

    /**
     * @param string $warehouse_ref
     * @return mixed
     * @throws \Exception
     */
    public function getWarehouseByRef($warehouse_ref)
    {

        $sql = "SELECT * FROM `". $this->table ."` WHERE warehouse_ref = '".$warehouse_ref ."'";


        return $this->db->query($sql)->row;

    }

    /**
     * @param $wareHouseList
     * @return bool
     * @throws \Exception
     */
    public function updateWarehouses($wareHouseList){

        foreach ( $wareHouseList as $wareHouse ){

            $values[]="('".$wareHouse['warehouse_ref'] ."','"
                .$wareHouse['warehouse_number'] ."','"
                .$this->db->escape($wareHouse['city_name_ua']) ."','"
                .$this->db->escape($wareHouse['city_name_ru']) ."','"
                .$this->db->escape($wareHouse['warehouse_city_ref']) ."','"
                .$this->db->escape($wareHouse['description_ua']) ."','"
                .$this->db->escape($wareHouse['description_ru']) ."','"
                .$this->db->escape($wareHouse['area']) ."','"
                .$this->db->escape($wareHouse['region']) ."')";


        }

        $sql = "INSERT INTO ".$this->table. " (
                warehouse_ref,
                warehouse_number,
                city_name_ua,
                city_name_ru,
                warehouse_city_ref,
                description_ua,
                description_ru,
                area_ua,
                region_ua)              
                  VALUES " . implode(',',$values) . "     
                    ON DUPLICATE KEY UPDATE warehouse_ref = VALUES(warehouse_ref)";

            $this->db->query($sql);

    return true;

    }

    /**
     * migration due to prom api
     */
    public function migrationSetupTable()
    {

        try{

            $result = $this->db->query("SELECT warehouse_ref FROM `". $this->table ."` limit 1");

        } catch (\Exception $e){

            $sql = "CREATE TABLE `" .$this->table ."` (
                    `warehouse_id` INT NOT NULL AUTO_INCREMENT,
                    `warehouse_ref` VARCHAR(36) NOT NULL,
                    `warehouse_number` INT NULL,
                    `city_name_ua` VARCHAR(50) NULL,
                    `city_name_ru` VARCHAR(50) NULL,
                    `warehouse_city_ref` VARCHAR(36) NULL,
                    `description_ua` VARCHAR(99) NULL,
                    `description_ru` VARCHAR(99) NULL,
                    `area_ua` VARCHAR(99) NULL,
                    `region_ua` VARCHAR(99) NULL,
                    PRIMARY KEY (`warehouse_id`),
                    UNIQUE INDEX `warehouse_id_UNIQUE` (`warehouse_id` ASC),
                    UNIQUE INDEX `warehouse_ref_UNIQUE` (`warehouse_ref` ASC))
                    ENGINE=InnoDB DEFAULT CHARSET=UTF8";

           $this->db->query($sql);

        }
    }


}