<?php

class Miura_Coupsdecoeur_Block_Adminhtml_Coupsdecoeur_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'coupsdecoeur';
        $this->_controller = 'adminhtml_coupsdecoeur';
        
        $this->_updateButton('save', 'label', Mage::helper('coupsdecoeur')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('coupsdecoeur')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('coupsdecoeur_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'coupsdecoeur_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'coupsdecoeur_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('coupsdecoeur_data') && Mage::registry('coupsdecoeur_data')->getId() ) {
            return Mage::helper('coupsdecoeur')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('coupsdecoeur_data')->getTitle()));
        } else {
            return Mage::helper('coupsdecoeur')->__('Add Item');
        }
    }
}
