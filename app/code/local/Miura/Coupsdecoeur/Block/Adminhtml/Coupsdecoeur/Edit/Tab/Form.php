<?php

class Miura_Coupsdecoeur_Block_Adminhtml_Coupsdecoeur_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('coupsdecoeur_form', array('legend'=>Mage::helper('coupsdecoeur')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('coupsdecoeur')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('coupsdecoeur')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('coupsdecoeur')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('coupsdecoeur')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('coupsdecoeur')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('coupsdecoeur')->__('Content'),
          'title'     => Mage::helper('coupsdecoeur')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getCoupsdecoeurData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getCoupsdecoeurData());
          Mage::getSingleton('adminhtml/session')->setCoupsdecoeurData(null);
      } elseif ( Mage::registry('coupsdecoeur_data') ) {
          $form->setValues(Mage::registry('coupsdecoeur_data')->getData());
      }
      return parent::_prepareForm();
  }
}
