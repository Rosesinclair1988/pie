<?php

class MDN_GlobalPDF_Model_Modifier_Multiply extends MDN_GlobalPDF_Model_Modifier_Abstract
{
	public function apply($modifierParam, $value)
	{
		return $value * $modifierParam;
	}
}
