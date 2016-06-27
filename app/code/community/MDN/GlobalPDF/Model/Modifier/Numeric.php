<?php

class MDN_GlobalPDF_Model_Modifier_Numeric extends MDN_GlobalPDF_Model_Modifier_Abstract
{
	public function apply($modifierParam, $value)
	{
		switch($modifierParam)
		{
			case 'int':
				return (int)$value;
				break;
		}
	}
}
