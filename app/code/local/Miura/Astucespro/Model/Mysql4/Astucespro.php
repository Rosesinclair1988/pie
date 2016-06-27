<?php

class Miura_Astucespro_Model_Mysql4_Astucespro extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the astucespro_id refers to the key field in your database table.
        $this->_init('astucespro/astucespro', 'astucespro_id');
    }
}
