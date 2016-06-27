<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Step_Settings extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardStepLicense');
        //------------------------------

        $this->setTemplate('M2ePro/wizard/step/settings.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $params = array(
            '\''.$this->getUrl('*/adminhtml_settings/index').'\'',
            Ess_M2ePro_Model_Wizard::STATUS_SETTINGS,
            'WizardHandlerObj.callBackAfterSettings'
        );
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Proceed'),
                                'onclick' => 'WizardHandlerObj.processStep('.implode(',',$params).');',
                                'class' => 'process_settings_button'
                            ) );
        $this->setChild('process_settings_button',$buttonBlock);
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
