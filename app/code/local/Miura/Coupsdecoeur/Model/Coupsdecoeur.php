<?php

class Miura_Coupsdecoeur_Model_Coupsdecoeur extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('coupsdecoeur/coupsdecoeur');
    }
}
