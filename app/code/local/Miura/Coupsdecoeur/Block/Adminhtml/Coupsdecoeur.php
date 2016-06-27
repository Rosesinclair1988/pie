<?php
class Miura_Coupsdecoeur_Block_Adminhtml_Coupsdecoeur extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_coupsdecoeur';
    $this->_blockGroup = 'coupsdecoeur';
    $this->_headerText = Mage::helper('coupsdecoeur')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('coupsdecoeur')->__('Add Item');
    parent::__construct();
  }
}
