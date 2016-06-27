<?php

class MDN_GlobalPDF_Model_Pdf_Catalog extends MDN_GlobalPDF_Model_Pdfhelper
{
	
	/**
	 * 
	 */
	public function getPdf($productIds = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
	        $this->pdf = new Zend_Pdf();
	    else 
	    	$this->firstPageIndex = count($this->pdf->pages);
	    
		//create products array
		$products = array();
		foreach($productIds as $productId)
		{
			if ($productId)
			{
				$product = mage::getModel('catalog/product')->load($productId);
				if ($product->getvisibility() != 1)
					$products[] = $product;
			}
		}
		
		//first page
       	$page = $this->NewPage();
		$templatePath = mage::helper('GlobalPDF')->getCatalogTemplatePath();
		$xml = file_get_contents($templatePath);
		$data = $this->setTemplateData($products);
		$this->drawTemplate($xml, $page, $data);
		
		//Add products pdf
		$productsModel = mage::getModel('GlobalPDF/Pdf_Product');
		$productsModel->pdf = $this->pdf;
		$productsModel->getPdf($products);
        
        $this->_afterGetPdf();
        return $this->pdf;
    }

	protected function setTemplateData($products)
	{
		$data = array();
		
		$data['catalog_name'] = $this->getcatalog_name();
		$data['comments'] = $this->getcatalog_comments();
		
		$data['products'] = array();
		foreach($products as $product)
		{
			$data['products'][] = $product->getData();
		}
		
		return $data;
	}
}
