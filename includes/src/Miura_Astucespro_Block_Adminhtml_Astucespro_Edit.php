<?php

class Miura_Astucespro_Block_Adminhtml_Astucespro_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'astucespro';
        $this->_controller = 'adminhtml_astucespro';
        
        $this->_updateButton('save', 'label', Mage::helper('astucespro')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('astucespro')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('astucespro_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'astucespro_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'astucespro_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('astucespro_data') && Mage::registry('astucespro_data')->getId() ) {
            return Mage::helper('astucespro')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('astucespro_data')->getTitle()));
        } else {
            return Mage::helper('astucespro')->__('Add Item');
        }
    }
}
