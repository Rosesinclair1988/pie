<?php

class MDN_GlobalPDF_ProductController extends Mage_Core_Controller_Front_Action {

    public function PrintAction() {
        try {
            //main information
            $productId = $this->getRequest()->getParam('id');
            $product = mage::getModel('catalog/product')->load($productId);
            $name = preg_replace('/ /', '_', $product->getname()) . '.pdf';
            $cachePath = mage::helper('GlobalPDF/Cache')->getProductFilePath($productId);

            //if cache enabled, try to find it in cache
            if (mage::helper('GlobalPDF')->cacheEnabled() && file_exists($cachePath)) {
                $contents = mage::helper('GlobalPDF/Cache')->getFileContent($cachePath);
                $this->_prepareDownloadResponseV2($name, $contents, 'application/pdf');
                return true;
            }

            //generate
            if (!isset($pdf)) {
                $model = mage::getModel('GlobalPDF/Pdf_Product');
                $pdf = $model->getPdf(array($product));
                $this->_prepareDownloadResponseV2($name, $pdf->render(), 'application/pdf');
                if (mage::helper('GlobalPDF')->cacheEnabled())
                    $pdf->save($cachePath);
            }
        } catch (Exception $ex) {
            if (mage::getStoreConfig('globalpdf/general/debug_mode'))
                die($ex->getMessage());
            Mage::getSingleton('customer/session')->addError($this->__('An error occured, please contact administrator'));
            mage::logException($ex);
            $this->_redirect('catalog/product/view', array('product_id' => $productId));
        }
    }

    protected function _prepareDownloadResponseV2($fileName, $content, $contentType = 'application/octet-stream') {
        $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', $contentType, true)
                ->setHeader('Content-Length', strlen($content))
                ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
                ->setBody($content);
    }

}
