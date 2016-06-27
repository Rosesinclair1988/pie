<?php

class MDN_GlobalPDF_OrderController extends Mage_Core_Controller_Front_Action {

    /**
     * Print order PDF
     */
    public function PrintAction() {
        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $order = mage::getModel('sales/order')->load($orderId);

            //check that invoice belong to current customer (if current scope is not adminhtml)
            if (mage::getDesign()->getArea() == 'frontend') {
                $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
                if ($customerId != $order->getcustomer_id())
                    die('Access refused');
            }

            $model = mage::getModel('GlobalPDF/Pdf_Order');
            $pdf = $model->getPdf(array($order));
            $name = $this->__('Order #%s', $order->getincrement_id()) . '.pdf';
            $this->_customPrepareDownloadResponse($name, $pdf->render(), 'application/pdf');
        } catch (Exception $ex) {
            if (mage::getStoreConfig('globalpdf/general/debug_mode'))
                die($ex->getMessage());
            Mage::getSingleton('customer/session')->addError($this->__('An error occured, please contact administrator'));
            mage::logException($ex);
            $this->_redirect('sales/order/view', array('order_id' => $orderId));
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
