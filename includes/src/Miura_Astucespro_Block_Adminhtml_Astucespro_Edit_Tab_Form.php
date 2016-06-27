<?php

class Miura_Astucespro_Block_Adminhtml_Astucespro_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('astucespro_form', array('legend'=>Mage::helper('astucespro')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('astucespro')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('astucespro')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('astucespro')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('astucespro')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('astucespro')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('astucespro')->__('Content'),
          'title'     => Mage::helper('astucespro')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getAstucesproData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getAstucesproData());
          Mage::getSingleton('adminhtml/session')->setAstucesproData(null);
      } elseif ( Mage::registry('astucespro_data') ) {
          $form->setValues(Mage::registry('astucespro_data')->getData());
      }
      return parent::_prepareForm();
  }
}
