<?php

class MDN_GlobalPDF_Model_Template_Method extends MDN_GlobalPDF_Model_Template_Abstract
{

	public function draw(&$pdf, &$page, $item, $data)
	{
		parent::draw($pdf, $page, $item, $data);
		
		$methodName = $item->getAttribute('name');
		$value = $item->getAttribute('value');
		$value = $this->replaceCodes($value, $data);
		
		switch($methodName)
		{
			case 'increment_y':
				$pdf->y -= $value;
				$pdf->checkForNewPage($page);
				break;
			case 'increment_x':
				$pdf->x += $value;
				break;
			case 'set_x':
				$pdf->x = $value;
				break;
			case 'set_y':
				$pdf->y = $pdf->_PAGE_HEIGHT - $value;
				break;
			case 'set_store':
				Mage::app()->setCurrentStore($value);
				break;
			case 'assign_variable':
				$t = explode('=', $value);
				if (count($t) == 2)
				{
					$varName = $t[0];
					$varValue = $t[1];
					$varValue = $this->replaceCodes($varValue, $data);
					mage::helper('GlobalPDF')->addCustomData($varName, $varValue);
				}
				break;
			case 'set_footer_height':
				$pdf->_footerHeight = $value;
				break;
                        case 'new_page':
                                // add new page
                                $pdf->jumpToNewPage($page);
                                break;
			default:
				die('Method '.$methodName.' does not exist !');
				break;
		}
	}
	
}
