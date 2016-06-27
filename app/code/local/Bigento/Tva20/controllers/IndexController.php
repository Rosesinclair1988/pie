<?php

class Bigento_Tva20_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function saveAction()
    {
        mage::helper('bgtva20')->applyTVA20();

        $this->_redirectReferer();
    }

    public function restoreAction()
    {
        mage::helper('bgtva20')->applyTVA20(true);

        $this->_redirectReferer();
    }

}
