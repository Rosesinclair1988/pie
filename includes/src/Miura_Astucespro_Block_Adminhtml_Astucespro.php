<?php
class Miura_Astucespro_Block_Adminhtml_Astucespro extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_astucespro';
    $this->_blockGroup = 'astucespro';
    $this->_headerText = Mage::helper('astucespro')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('astucespro')->__('Add Item');
    parent::__construct();
  }
}
