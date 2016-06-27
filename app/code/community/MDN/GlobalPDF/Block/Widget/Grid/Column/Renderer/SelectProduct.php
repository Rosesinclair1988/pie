<?php

class MDN_GlobalPDF_Block_Widget_Grid_Column_Renderer_SelectProduct extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
		$name = str_replace("'", '', $row->getName());
		$html = '<a href="javascript:selectProduct('.$row->getId().', \''.$name.'\')">'.$this->__('Select').'</a>';
		return $html;
	}
    
}
