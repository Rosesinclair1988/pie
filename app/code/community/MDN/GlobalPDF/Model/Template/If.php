<?php

class MDN_GlobalPDF_Model_Template_If extends MDN_GlobalPDF_Model_Template_Abstract
{

	public function draw(&$pdf, &$page, $item, $data)
	{
		parent::draw($pdf, $page, $item, $data);

		//check condition
		$condition = $item->getAttribute('condition');
		$operator = $item->getAttribute('operator');
		if ($operator == '')
			$operator = 'eq';
		$condition = $this->replaceCodes($condition, $data);
		$value  = $item->getAttribute('value');
		
		//force null type to empty string
		if ($condition == null)
			$condition = "";
		
		//apply condition depending of operator
		$debug = '<p>"'.$condition.'" '.$operator.' "'.$value.'" is true</p>';
		switch($operator)
		{
			case 'gt':
				
				if ((int)$condition < (int)$value)
					return false;
				break;
			case 'lt':
				if ($condition > $value)
					return false;
				break;
			case 'neq':
				if ($value == $condition)
					return false;
				break;
			case 'eq':
			default:
				if ($value != $condition)
					return false;		
				break;
		}
		
		//draw child
		$xmlTemplate = $item->ownerDocument->saveXML($item);
		mage::getModel('GlobalPDF/Template')->drawTemplate($pdf, $page, $xmlTemplate, $data);

	}
	
}
