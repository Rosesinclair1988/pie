<?php

class Miura_Astucespro_Model_Astucespro extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('astucespro/astucespro');
    }
}
