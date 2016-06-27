<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Upgrade_Welcome_Description extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardUpgradeWelcomeDescription');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/upgrade/welcome/description.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->version = Mage::helper('M2ePro/Module')->getVersion();
        $this->mode = Ess_M2ePro_Model_Wizard::MODE_UPGRADE;

        return parent::_beforeToHtml();
    }
}
