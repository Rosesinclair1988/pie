<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Welcome_Requirements extends Mage_Adminhtml_Block_Widget
{
    protected $mode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardWelcomeRequirements');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/welcome/requirements.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->setChild('requirements', $this->getLayout()->createBlock('M2ePro/adminhtml_about_requirements'));

        //------------------------------

        if (Mage::getSingleton('M2ePro/Wizard')->isInstallationWelcome()) {
            $this->mode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;
            $status = Ess_M2ePro_Model_Wizard::STATUS_CRON;
            $url = $this->getUrl('*/*/installation');
        } else {
            $this->mode = Ess_M2ePro_Model_Wizard::MODE_UPGRADE;
            $status = Ess_M2ePro_Model_Wizard::STATUS_MARKETPLACES;
            $url = $this->getUrl('*/*/upgrade');
        }

        //------------------------------
        $callback = 'function() { setLocation(\''.$url.'\'); }';
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Start Configuration'),
                                'onclick' => 'WizardHandlerObj.setStatus('.$status.', '.$callback.')',
                                'class' => 'start_installation_button'
                            ) );
        $this->setChild('start_installation_button',$buttonBlock);
        //------------------------------

        return parent::_beforeToHtml();
    }
}
