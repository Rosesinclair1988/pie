<?php

class MDN_GlobalPDF_Model_Template_Repeater extends MDN_GlobalPDF_Model_Template_Abstract
{
	public function draw(&$pdf, &$page, $item, $data)
	{
		parent::draw($pdf, $page, $item, $data);
		
		//set collection
		$source = $item->getAttribute('source');
		$max = $item->getAttribute('max_occurences');
		$collection = $data[$source];
		
		//set template
		$xmlTemplate = $item->ownerDocument->saveXML($item);
		
		//draw collection
		$i = 0;
		foreach($collection as $repeaterItem)
		{
			$repeaterData = $this->custom_array_replace($data, $repeaterItem);
			
			mage::getModel('GlobalPDF/Template')->drawTemplate($pdf, $page, $xmlTemplate, $repeaterData);
			
			$i++;
			if ($max && $max <= $i)
				break;
		}
	}
	
}
