<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Step_Marketplace extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardStepMarketplace');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/step/marketplace.phtml');
    }

    protected function _beforeToHtml()
    {
        $processParams = $this->getData('process_params');
        !is_array($processParams) && $processParams = array();

        //-------------------------------
        $url = $this->getUrl('*/adminhtml_marketplace/index', $processParams);
        $step = Ess_M2ePro_Model_Wizard::STATUS_MARKETPLACES;
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Proceed'),
                                'onclick' => 'WizardHandlerObj.processStep(\''.$url.'\','.$step.');',
                                'class' => 'process_marketplaces_button'
                            ) );
        $this->setChild('process_marketplaces_button',$buttonBlock);
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
