<?php

abstract class MDN_GlobalPDF_Model_Modifier_Abstract extends Mage_Core_Model_Abstract
{
	abstract function apply($modifierParam, $value);
}
