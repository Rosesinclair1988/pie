<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Upgrade extends Mage_Adminhtml_Block_Widget_Container
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardUpgrade');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml';
        $this->_mode = 'upgrade';
        //------------------------------

        // Set header text
        //------------------------------
        $this->_headerText = Mage::helper('M2ePro')->__('Configuration Wizard');
        //------------------------------

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->_addButton('goto_about', array(
            'label'     => Mage::helper('M2ePro')->__('About'),
            'onclick'   => 'setLocation(\''.$this->getUrl('*/adminhtml_about/index').'\')',
            'class'     => 'button_link'
        ));

        $this->_addButton('goto_support', array(
            'label'     => Mage::helper('M2ePro')->__('Support'),
            'onclick'   => 'setLocation(\''.$this->getUrl('*/adminhtml_support/index').'\')',
            'class'     => 'button_link'
        ));

        $videoLink = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/video_tutorials/', 'baseurl');
        $this->_addButton('goto_video_tutorials', array(
            'label'     => Mage::helper('M2ePro')->__('Video Tutorials'),
            'onclick'   => 'window.open(\''.$videoLink.'\', \'_blank\'); return false;',
            'class'     => 'button_link'
        ));

        $docsLink = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/documentation/', 'baseurl');
        $this->_addButton('goto_docs', array(
            'label'     => Mage::helper('M2ePro')->__('Documentation'),
            'onclick'   => 'window.open(\''.$docsLink.'\', \'_blank\'); return false;',
            'class'     => 'button_link'
        ));

        $url = $this->getUrl('*/*/skip', array('mode' => Ess_M2ePro_Model_Wizard::MODE_UPGRADE));
        $this->_addButton('skip', array(
            'label'     => Mage::helper('M2ePro')->__('Skip Wizard'),
            'onclick'   => 'WizardHandlerObj.skip(\''.$url.'\')',
            'class'     => 'skip'
        ));
        //------------------------------

        $this->setTemplate('widget/form/container.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->setChild('content', $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_upgrade_content'));
        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return parent::_toHtml().$this->getChildHtml('content');
    }
}
