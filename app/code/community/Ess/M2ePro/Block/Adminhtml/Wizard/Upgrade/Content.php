<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Upgrade_Content extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardUpgradeContent');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/upgrade/content.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Complete Configuration'),
                                'onclick' => 'setLocation(\''.$this->getUrl('*/*/complete').'\');',
                                'class' => 'end_installation_button'
                            ) );
        $this->setChild('end_installation_button',$buttonBlock);
        //-------------------------------

        $this->setChild(
            'step_general',
            $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_general', '', array(
                'mode' => Ess_M2ePro_Model_Wizard::MODE_UPGRADE
            ))
        );

        // Steps
        //-------------------------------
        $this->setChild(
            'step_marketplace',
            $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_marketplace', '', array(
                'process_params' => array('hide_upgrade_notification' => 'yes',
                                          'tab' => Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON)
            ))
        );
        $this->setChild(
            'step_amazon_account',
            $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_amazonAccount', '', array(
                'process_params' => array('hide_upgrade_notification' => 'yes')
            ))
        );
        $this->setChild(
            'step_synchronization',
            $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_synchronization', '', array(
                'process_params' => array('hide_upgrade_notification' => 'yes',
                                          'tab' => Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON)
            ))
        );
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
