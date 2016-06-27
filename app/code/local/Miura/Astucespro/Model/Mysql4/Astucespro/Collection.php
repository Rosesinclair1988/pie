<?php

class Miura_Astucespro_Model_Mysql4_Astucespro_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('astucespro/astucespro');
    }
}
