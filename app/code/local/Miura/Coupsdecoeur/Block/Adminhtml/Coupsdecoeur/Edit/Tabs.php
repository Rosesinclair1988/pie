<?php

class Miura_Coupsdecoeur_Block_Adminhtml_Coupsdecoeur_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('coupsdecoeur_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('coupsdecoeur')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('coupsdecoeur')->__('Item Information'),
          'title'     => Mage::helper('coupsdecoeur')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('coupsdecoeur/adminhtml_coupsdecoeur_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}
