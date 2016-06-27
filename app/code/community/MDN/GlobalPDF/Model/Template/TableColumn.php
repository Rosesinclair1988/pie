<?php

class MDN_GlobalPDF_Model_Template_TableColumn extends MDN_GlobalPDF_Model_Template_Abstract {

	protected $_tableColumns = array();

	/**
	 * Process XML depending of the mode
	 */
    public function draw(&$pdf, &$page, $item, $data) {
        parent::draw($pdf, $page, $item, $data);

		$mode = $item->getAttribute('mode');
		switch($mode)
		{
			case 'declare':			//declare a new table
				$this->declareTable($pdf, $page, $item, $data);
				break;
			case 'draw_header':		//draw table header
				$selectedTables = $this->getTables($item->getAttribute('name'));
				foreach($selectedTables as $selectedTable)
					$this->drawHeader($selectedTable, $pdf, $page, $item, $data);
				break;
			case 'end':				//end table
				if (isset($this->_tableColumns[$item->getAttribute('name')]))
					unset($this->_tableColumns[$item->getAttribute('name')]);
				break;
			case 'draw_borders':	//Draw table borders
				$selectedTables = $this->getTables($item->getAttribute('name'));
				foreach($selectedTables as $selectedTable)
					$this->drawColumnBorders($selectedTable, $pdf, $page, $item, $data);
				break;
		}
		
    }
	
	/**
	 * Declare a new table
	 */
	protected function declareTable(&$pdf, &$page, $item, $data)
	{
		//create object
		$tableColumn = new Varien_Object();
		
        //get position & size
        $font = $this->getFont($item, $pdf, $data);
        $tableColumn->setFont($this->getFontFromName($font['face'], $font['size']) ,$font['size']);

		//get mode
		$tableColumn->setMode($item->getAttribute('mode'));				
		
        //get custom datas
        $tableColumn->setName($item->getAttribute('name'));
        $tableColumn->setDrawHeader($item->getAttribute('draw_header'));
        $tableColumn->setHeaderHeight($item->getAttribute('header_height'));
        $tableColumn->setLeft($item->getAttribute('left'));
        $tableColumn->setBgColor($item->getAttribute('bgcolor'));
        $tableColumn->setBorderColor($item->getAttribute('border_color'));
		
		//load columns
		$columns = array();
        $columnsNodes = $item->getElementsByTagName('columns');
        if ($columnsNode = $columnsNodes->item(0)) {
			$columnNodes = $item->getElementsByTagName('column');
			foreach($columnNodes as $columnNode)
			{
				$column = new Varien_Object();
				$column->setWidth($columnNode->getAttribute('width'));
				$column->setAlign($columnNode->getAttribute('align'));
				$column->setTitle($columnNode->getAttribute('title'));
				
				$columns[] = $column;
			}
			
        }
		$tableColumn->setColumns($columns);
		
		//load template
		$template = '';
		$templateNodes = $item->getElementsByTagName('template');
		if ($templateNode = $templateNodes->item(0)) 
		{
			$template = $item->ownerDocument->saveXML($templateNode);
		}
		$tableColumn->setTemplate($template);

		//add to collection
		$this->_tableColumns[$tableColumn->getName()] = $tableColumn;
		
		//draw header (if set)
		if ($tableColumn->getDrawHeader())
			$this->drawHeader($tableColumn, $pdf, $page, $item, $data);

	}
	
	/**
	 * Return an array of tables matching to pattern
	 */
	protected function getTables($pattern)
	{
		$tables = array();
		
		if ($pattern == '*')
			$tables = $this->_tableColumns;
		else
			$tables[] = $this->_tableColumns[$pattern];
		
		return $tables;
	}

	/**
	 * Draw table header
	 */
	protected function drawHeader(&$tableColumn, &$pdf, &$page, $item, $data)
	{
		$x = $tableColumn->getLeft();
		$baseTemplate = $tableColumn->getTemplate();
		foreach($tableColumn->getColumns() as $column)
		{
			//Add current column attributes in data & manage X position
			$repeaterData = $this->custom_array_replace($data, $column->getData());
			$repeaterData['left'] = $x;
			$repeaterData['debug'] = '1';
			$repeaterData['top'] = $pdf->_PAGE_HEIGHT - $pdf->y;
		
			//draw template for current column
			mage::getModel('GlobalPDF/Template')->drawTemplate($pdf, $page, $baseTemplate, $repeaterData);
			
			//increment x according to column width
			$x += $column->getWidth();
		}
		
		//increment Y according to header height
		$pdf->y -= $tableColumn->getHeaderHeight();
		
		//store current y
		$tableColumn->setY($pdf->y);
		
	}
	
	/**
	 * Draw table borders
	 */
	protected function drawColumnBorders(&$tableColumn, &$pdf, &$page, $item, $data)
	{
		//get Y
		$originY = $tableColumn->getY();
		$currentY = $pdf->y;
		
        //set border color
        $color = $this->buildZendColor($tableColumn->getBorderColor());
        $page->setLineColor($color);
		
		//draw lines
		$x = $tableColumn->getLeft();
		foreach($tableColumn->getColumns() as $column)
		{
			//Draw line
			$page->drawRectangle($x,
					$originY,
					$x,
					$currentY,
					Zend_Pdf_Page::SHAPE_DRAW_STROKE
			);			

			//increment X
			$x += $column->getWidth();
			
		}
		
		//draw last column border
		$page->drawRectangle($x,
				$originY,
				$x,
				$currentY,
				Zend_Pdf_Page::SHAPE_DRAW_STROKE
		);			
		
	}
	
}
