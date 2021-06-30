<?php
namespace App\Engine\Models;
/**
 * Class UpRecipient
 * @package App\Engine\Models
 *
 *
 */
class UpRecipient extends BaseModel
{
    public function __construct()
    {

        parent::__construct();

        $this->table = 'table_up_recipient';

    }

    public function saveRecipient($recipientData)
    {

        $sql = "INSERT INTO " . $this->table . " SET 
            first_name ='" . $this->db->escape($recipientData['firstName']) . "',
            last_name ='" . $this->db->escape($recipientData['lastName']) . "',
            middle_name ='" . $this->db->escape($recipientData['middleName']) . "',
            uuid ='" . $this->db->escape($recipientData['uuid']) . "',
            phone_number ='" . $this->db->escape($recipientData['phoneNumber']) . "',
            date_created = NOW()";

        $result = $this->db->query($sql);

        return $this->db->lastId;

    }

}