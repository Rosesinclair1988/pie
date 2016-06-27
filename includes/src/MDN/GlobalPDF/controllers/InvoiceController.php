<?php

class MDN_GlobalPDF_InvoiceController extends Mage_Core_Controller_Front_Action {

    /**
     * Print order PDF
     */
    public function PrintAction() {
        try {
            $invoiceId = $this->getRequest()->getParam('invoice_id');
            $invoice = mage::getModel('sales/order_invoice')->load($invoiceId);

            //check that invoice belong to current customer
            if (mage::getDesign()->getArea() == 'frontend')
            {
                $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
                $order = Mage::getModel('sales/order')->load($invoice->getorder_id());
                if ($customerId != $order->getcustomer_id())
                    die('Access refused');
            }

            $model = mage::getModel('GlobalPDF/Pdf_Order_Invoice');
            $pdf = $model->getPdf(array($invoice));
            $name = $this->__('Invoice #%s', $invoice->getincrement_id()) . '.pdf';
            $this->_customPrepareDownloadResponse($name, $pdf->render(), 'application/pdf');
        } catch (Exception $ex) {
            if (mage::getStoreConfig('globalpdf/general/debug_mode'))
                die($ex->getMessage());
            Mage::getSingleton('customer/session')->addError($this->__('An error occured, please contact administrator'));
            mage::logException($ex);
            $this->_redirect('sales/invoice/view', array('invoice_id' => $invoiceId));
        }
    }

    protected function _customPrepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream') {
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

?>
