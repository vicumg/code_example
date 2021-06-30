<?php
namespace App\Engine\Models;
/**
 * Class UpParcel
 * @package App\Engine\Models
 *
 *
 */

class UpParcel extends BaseModel
{
    public function __construct()
    {

        parent::__construct();

        $this->table = 'table_up_parcel';

    }

    public function saveParcel($parcelData, $recipientId){

        $sql = "INSERT INTO " .$this->table ." SET           
            uuid ='" .$this->db->escape($parcelData['uuid']) ."',
            recipient_uuid ='" .$this->db->escape($parcelData['recipient']['uuid']) ."',
            barcode ='" .$this->db->escape($parcelData['barcode']) ."',
            recipient_id ='" . (int)$recipientId ."',
            date_created = NOW()";



       $result =  $this->db->query($sql);

        return $this->db->lastId;

    }

}
