<?php

class Miura_Coupsdecoeur_Model_Mysql4_Coupsdecoeur extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the coupsdecoeur_id refers to the key field in your database table.
        $this->_init('coupsdecoeur/coupsdecoeur', 'coupsdecoeur_id');
    }
}
