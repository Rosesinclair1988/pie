<?php

class MDN_GlobalPDF_Model_Event extends Mage_Core_Model_Abstract
{
	private $_events = array();
	private $_isLoaded = false;

	/**
	 * Load event XML
	 */
	public function loadEvents()
	{
		$templatePath = mage::helper('GlobalPDF')->getEventTemplatePath();
		$xml = file_get_contents($templatePath);
	
		//if empty, return false
		if ($xml == '')
			return false;
	
		//load xml
        $xmlDoc = new DomDocument();
		$xmlDoc->loadXML($xml);		
		
		//parse xml
		$documentElement = $xmlDoc->documentElement;
		foreach ($documentElement->childNodes as $item)
		{
			if($item->nodeType != XML_ELEMENT_NODE)
				continue;

			$this->_events[$item->tagName] = $item;
		}
		
		$this->_isLoaded = true;
		
	}

	/**
	 * Return event types
	 */
	protected function getEventTypes()
	{
		$eventTypes = array('after_new_page',
							'before_new_page',
							'after_document'
							);
		return $eventTypes;
	}
	
	/**
	 * Play event
	 */
	public function playEvent($eventName, &$pdf, &$page, $data)
	{
	
		//load template if not loaded
		if (!$this->_isLoaded)
			$this->loadEvents();
	
		//process event
		if (isset($this->_events[$eventName]))
		{
			$eventNode = $this->_events[$eventName];
			$xml = $eventNode->ownerDocument->saveXML($eventNode);
			$pdf->drawTemplate($xml, $page);
			
		}
	}
	
}
