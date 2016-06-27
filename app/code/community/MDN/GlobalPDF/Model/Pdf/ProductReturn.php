<?php

/**
 * 
 */
class MDN_GlobalPDF_Model_Pdf_ProductReturn extends MDN_GlobalPDF_Model_Pdfhelper {

    /**
     *
     * @param type $rmas 
     */
    public function getPdf($rmas = array()) {

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        $rma = $rmas[0];

        //set data for both order & credit memo
        $data = $this->setTemplateData($rma);
        
        //add new page
        $settings = array('store_id' => $rma->getSalesOrder()->getstore_id());
        $page = $this->NewPage($settings, $data);


        //draw invoice template
        $templatePath = mage::helper('GlobalPDF')->getProductReturnTemplatePath();

        $xml = file_get_contents($templatePath);

        $this->drawTemplate($xml, $page, $data);

        $this->_afterGetPdf();

        return $this->pdf;
    }

    protected function setTemplateData($rma) {

        $data = $rma->getData();
        $data['store_id'] = $rma->getSalesOrder()->getstore_id();
        $data['rma.order_id'] = $rma->getSalesOrder()->getincrement_id();
        $data['rma.valid_until'] = $rma->getrma_expire_date();
        $data['rma.comments'] = Mage::getStoreConfig('productreturn/pdf/pdf_comment');
        $data['store_address'] = Mage::getStoreConfig('productreturn/pdf/address'); 
        $data['customer_billing_address'] = $rma->getShippingAddress()->getFormated(); // unused in xml file
        
        //items
        $data['items'] = array();
        foreach ($rma->getProducts() as $item) {
            if ($item->getrp_qty() > 0) {

                $itemData = $item->getData();

                if (!isset($itemData['parent_item_id']))
                    $itemData['parent_item_id'] = '';

                $data['items'][] = $itemData;
            }
        }
//echo'<pre>'; var_dump($data); echo'</pre>'; die();
        return $data;
    }

}
