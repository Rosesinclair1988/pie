<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Upgrade_Notification extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardUpgradeNotification');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/upgrade/notification.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->version = Mage::helper('M2ePro/Module')->getVersion();
        $this->status = Mage::getSingleton('M2ePro/Wizard')->getStatus(Ess_M2ePro_Model_Wizard::MODE_UPGRADE);

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $generalBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_general', '', array(
            'mode' => Ess_M2ePro_Model_Wizard::MODE_UPGRADE
        ));

        return $generalBlock->toHtml() . parent::_toHtml();
    }
}
