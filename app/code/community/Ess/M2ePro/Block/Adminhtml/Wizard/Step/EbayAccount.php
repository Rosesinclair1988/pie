<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Step_EbayAccount extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardStepEbayAccount');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/step/ebay_account.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $params = array(
            '\''.$this->getUrl('*/adminhtml_ebay_account/new').'\'',
            Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_EBAY,
            'WizardHandlerObj.callBackAfterEbayAccounts'
        );
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Proceed'),
                                'onclick' => 'WizardHandlerObj.processStep('.implode(',',$params).');',
                                'class'   => 'process_ebay_accounts_button'
                            ) );
        $this->setChild('process_ebay_accounts_button',$buttonBlock);

        $params = array(
            Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_EBAY,
            'WizardHandlerObj.callBackAfterEbayAccounts'
        );
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Skip'),
                                'onclick' => 'WizardHandlerObj.skipStep('.implode(',',$params).');',
                                'class'   => 'skip_ebay_accounts_button'
                            ) );
        $this->setChild('skip_ebay_accounts_button',$buttonBlock);
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
