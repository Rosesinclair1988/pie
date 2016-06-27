<?php

class MDN_GlobalPDF_Model_Template extends Mage_Core_Model_Abstract
{

	public function drawTemplate($pdf, &$page, $xml, $data = null)
	{
	
		//load xml
        $xmlDoc = new DomDocument();
		$xmlDoc->loadXML($xml);		

		//parse xml
		$documentElement = $xmlDoc->documentElement;
		
		//todo : use another method than getElementsByTagName to get first level child
		foreach ($documentElement->childNodes as $item)
		{
			//set item model
			$model = null;
			if($item->nodeType == XML_ELEMENT_NODE){
			
				switch($item->tagName)
				{
						case 'img':
								$model = mage::getModel('GlobalPDF/Template_Img');
								break;
						case 'rectangle':
								$model = mage::getModel('GlobalPDF/Template_Rectangle');
								break;
						case 'text':
								$model = mage::getModel('GlobalPDF/Template_Text');
								break;
						case 'multitext':
								$model = mage::getModel('GlobalPDF/Template_Multitext');
								break;
						case 'method':
								$model = mage::getModel('GlobalPDF/Template_Method');
								break;
						case 'if':
								$model = mage::getModel('GlobalPDF/Template_If');
								break;
						case 'repeater':
								$model = mage::getModel('GlobalPDF/Template_Repeater');
								break;
						case 'tablecolumn':
								$model = mage::getSingleton('GlobalPDF/Template_TableColumn');
								break;
				}
			}
			
			//Draw item
			if ($model != null)
			{
				try
				{
					$model->template = $this;
					$model->draw($pdf, $page, $item, $data);
				}
				catch(Exception $ex)
				{
					die('Error in template : '.$ex->getMessage()."<br><pre>".$ex->getTraceAsString().'</pre>');
				}
			}
		}
	}
	

}
