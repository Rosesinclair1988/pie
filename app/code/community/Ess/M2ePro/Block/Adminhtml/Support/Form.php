<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Support_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('supportForm');
        //------------------------------

        $this->setTemplate('M2ePro/support.phtml');

    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->documentationUrl = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/documentation/', 'baseurl'
        );
        $this->supportUrl = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/support/', 'baseurl');

        //-------------------------------
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Attach Another File'),
                                'onclick' => 'SupportHandlerObj.moreAttachments();',
                                'class' => 'more_attachments'
                            ) );
        $this->setChild('more_attachments',$buttonBlock);
        //-------------------------------

        //-------------------------------
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => Mage::helper('M2ePro')->__('Search'),
                                'onclick' => 'SupportHandlerObj.searchUserVoiceArticles();',
                                'class' => 'user_voice_search'
                            ) );
        $this->setChild('user_voice_search',$buttonBlock);
        //-------------------------------

        //-------------------------------
        $buttonBlock = $this->getLayout()
                            ->createBlock('adminhtml/widget_button')
                            ->setData( array(
                                'label'   => ' '.Mage::helper('M2ePro')->__('Send').' ',
                                'onclick' => 'SupportHandlerObj.save_click();',
                                'class' => 'send_form'
                            ) );
        $this->setChild('send_form',$buttonBlock);
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
