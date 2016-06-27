<?php

class Miura_Astucespro_Block_Adminhtml_Astucespro_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('astucespro_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('astucespro')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('astucespro')->__('Item Information'),
          'title'     => Mage::helper('astucespro')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('astucespro/adminhtml_astucespro_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}
