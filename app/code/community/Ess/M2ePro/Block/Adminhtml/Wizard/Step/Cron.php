<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Step_Cron extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardStepCron');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/step/cron.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Confirm'),
                                'onclick' => 'WizardHandlerObj.skipStep('.Ess_M2ePro_Model_Wizard::STATUS_CRON.');',
                                'class'   => 'skip_cron_button'
                            ) );
        $this->setChild('skip_cron_button',$buttonBlock);

        $this->basePath = Mage::helper('M2ePro/Server')->getBaseDirectory();
        $this->baseUrl = Mage::helper('M2ePro/Server')->getBaseUrl();
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
