<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Installation_Welcome_Description extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardInstallationWelcomeDescription');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/installation/welcome/description.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->version = Mage::helper('M2ePro/Module')->getVersion();
        $this->mode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;

        return parent::_beforeToHtml();
    }
}
