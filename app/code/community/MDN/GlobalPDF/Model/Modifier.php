<?php

class MDN_GlobalPDF_Model_Modifier extends Mage_Core_Model_Abstract
{

	/**
	 * Apply selected modifier
	 */
	public function apply($modifierType, $modifierParam, $value)
	{
		$modifierModel = null;
		switch($modifierType)
		{
			case 'currency':
				$modifierModel = mage::getModel('GlobalPDF/Modifier_Currency');
				break;
			case 'numeric':
				$modifierModel = mage::getModel('GlobalPDF/Modifier_Numeric');
				break;
			case 'date':
				$modifierModel = mage::getModel('GlobalPDF/Modifier_Date');
				break;
			case 'multiply':
				$modifierModel = mage::getModel('GlobalPDF/Modifier_Multiply');
				break;
			case 'apply_tax':
				$modifierModel = mage::getModel('GlobalPDF/Modifier_Tax');
				break;
			default:
				die('Unknown modifier '.$modifierType);
		}
		
		return $modifierModel->apply($modifierParam, $value);
	}
	
}
