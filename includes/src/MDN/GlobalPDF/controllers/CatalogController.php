<?php

class MDN_GlobalPDF_CatalogController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Show build catalog form
	 */
	public function FormAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	/**
	 * Create catalog
	 */
	public function CreateAction()
	{
		//build product ids array
		$productIds = explode(',', $this->getRequest()->getPost('product_ids'));
		$catalogName = $this->getRequest()->getPost('catalog_name');
		$comments = $this->getRequest()->getPost('catalog_comments');
		
		//Add category's products
		$categoryId = $this->getRequest()->getPost('category_id');
		if ($categoryId)
			$productIds = array_merge($productIds, mage::helper('GlobalPDF/Category')->getProductIds($categoryId));

		//Create catalog
		$model = mage::getModel('GlobalPDF/Pdf_Catalog');
		$model->setcatalog_name($catalogName);
		$model->setcatalog_comments($comments);
		$pdf = $model->getPdf($productIds);
		$name = $catalogName.'.pdf';
		$this->_customPrepareDownloadResponse($name, $pdf->render(), 'application/pdf');    		
		
		
	}
	
	/**
	 * Download document
	 */
    protected function _customPrepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream')
    {
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', strlen($content))
            ->setHeader('Content-Disposition', 'attachment; filename='.$fileName)
            ->setBody($content);
    }

	/**
	 * Used for product grid ajax refresh
	 */
	public function ProductsGridAction()
	{
		$this->loadLayout();
		$this->getResponse()->setBody(
				$this->getLayout()->createBlock('GlobalPDF/Catalog_Products')->toHtml()
		);	
	}
	
}
