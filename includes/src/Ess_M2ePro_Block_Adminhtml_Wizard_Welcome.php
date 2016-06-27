<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Wizard_Welcome extends Mage_Adminhtml_Block_Widget_Container
{
    private $mode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('wizardWelcome');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml';
        $this->_mode = 'welcome';
        //------------------------------

        // Set header text
        //------------------------------
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Ess_M2ePro_Helper_Component_Ebay::TITLE;
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Ess_M2ePro_Helper_Component_Amazon::TITLE;
        }

        $this->_headerText = str_replace(
            '%components%',
            implode(' / ',$components),
            Mage::helper('M2ePro')->__('Welcome to M2E Pro - Magento %components% Integration!')
        );
        //------------------------------

        // Initialize wizard mode
        //------------------------------
        if (Mage::getSingleton('M2ePro/Wizard')->isUpgradeWelcome()) {
            $this->mode = Ess_M2ePro_Model_Wizard::MODE_UPGRADE;
        }
        //------------------------------

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->_addButton('goto_cmd', array(
            'label'     => 'CMD',
            'onclick'   => 'setLocation(\''.$this->getUrl('*/adminhtml_cmd/index').'\')',
            'class'     => 'button_link cmd',
            'style'     => is_null($this->getRequest()->getParam('show_cmd')) ? 'display: none;' : ''
        ));

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

        $this->_addButton('skip', array(
            'label'     => Mage::helper('M2ePro')->__('Skip Wizard'),
            'onclick'   => 'WizardHandlerObj.skip(\''.$this->getUrl('*/*/skip', array('mode' => $this->mode)).'\')',
            'class'     => 'skip'
        ));
        //------------------------------

        $this->setTemplate('widget/form/container.phtml');
    }

    protected function _beforeToHtml()
    {
        if ($this->mode == Ess_M2ePro_Model_Wizard::MODE_UPGRADE) {
            $description = $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_upgrade_welcome_description');
        } else {
            $description = $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_installation_welcome_description');
        }

        $generalBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_wizard_step_general', '', array(
            'mode' => $this->mode
        ));

        $this->setChild('general', $generalBlock);
        $this->setChild('description', $description);
        $this->setChild('requirements',$this->getLayout()->createBlock('M2ePro/adminhtml_wizard_welcome_requirements'));

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return parent::_toHtml() . $this->getChildHtml('general')
                                 . $this->getChildHtml('description')
                                 . $this->getChildHtml('requirements');
    }
}
