<?php
class MDN_GlobalPDF_CartController extends Mage_Core_Controller_Front_Action
{

    public function printAction()
    {
    	try
    	{
	        $model = mage::getModel('GlobalPDF/Pdf_Cart');
	        $pdf = $model->getPdf(array());
	        $name = $this->__('My cart').'.pdf';
	        $this->_prepareDownloadResponseV2($name, $pdf->render(), 'application/pdf');
    	}
    	catch (Exception $ex)
    	{
			if (mage::getStoreConfig('globalpdf/general/debug_mode'))
				die($ex->getMessage());
    		Mage::getSingleton('customer/session')->addError($this->__('An error occured, please contact administrator'));
			mage::logException($ex);
    		$this->_redirect('catalog/product/view', array('product_id' => $Productid));
    	}
    }
    
    protected function _prepareDownloadResponseV2($fileName, $content, $contentType = 'application/octet-stream')
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
    

}
