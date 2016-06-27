<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Installation_Content extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardInstallationContent');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/installation/content.phtml');
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

        $this->setChild('step_general', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_general'));

        // Steps
        //-------------------------------
        $this->setChild(
            'step_cron', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_cron')
        );
        $this->setChild(
            'step_settings', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_settings')
        );
        $this->setChild(
            'step_license', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_license')
        );
        $this->setChild(
            'step_marketplace', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_marketplace')
        );
        $this->setChild(
            'step_ebay_account', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_ebayAccount')
        );
        $this->setChild(
            'step_amazon_account', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_amazonAccount')
        );
        $this->setChild(
            'step_synchronization', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_synchronization')
        );
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
