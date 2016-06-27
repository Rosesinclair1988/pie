<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Step_AmazonAccount extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardStepAmazonAccount');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/step/amazon_account.phtml');
    }

    protected function _beforeToHtml()
    {
        $processParams = $this->getData('process_params');
        !is_array($processParams) && $processParams = array();

        //-------------------------------
        $url = $this->getUrl('*/adminhtml_amazon_account/new', $processParams);
        $step = Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_AMAZON;
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Proceed'),
                                'onclick' => 'WizardHandlerObj.processStep(\''.$url.'\','.$step.');',
                                'class' => 'process_amazon_accounts_button'
                            ) );
        $this->setChild('process_amazon_accounts_button',$buttonBlock);

        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Skip'),
                                'onclick' => 'WizardHandlerObj.skipStep('.$step.');',
                                'class' => 'skip_amazon_accounts_button'
                            ) );
        $this->setChild('skip_amazon_accounts_button',$buttonBlock);
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
