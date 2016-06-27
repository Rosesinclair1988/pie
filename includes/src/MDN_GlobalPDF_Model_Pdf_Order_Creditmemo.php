<?php

class MDN_GlobalPDF_Model_Pdf_Order_Creditmemo extends MDN_GlobalPDF_Model_Pdfhelper {

    public function getPdf($creditMemos = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        foreach ($creditMemos as $creditMemo) {

            //Set current store id depending of shipment store id
            Mage::app()->getLocale()->emulate($creditMemo->getStoreId());
            Mage::app()->setCurrentStore($creditMemo->getStoreId());

            //add new page
            $page = $this->NewPage();

            //set data for both order & credit memo
            $data = $this->setTemplateData($creditMemo);

            //draw invoice template
            $templatePath = mage::helper('GlobalPDF')->getCreditmemoTemplatePath();
            $xml = file_get_contents($templatePath);
            $this->drawTemplate($xml, $page, $data);

            //revert current store id
            Mage::app()->getLocale()->revert();
        }

        $this->_afterGetPdf();
        return $this->pdf;
    }

    protected function setTemplateData($creditMemo) {
        $data = array();

        //main information
        foreach ($creditMemo->getData() as $key => $value) {
            $data['creditmemo.' . $key] = $value;
        }
        foreach ($creditMemo->getOrder()->getData() as $key => $value) {
            $data['order.' . $key] = $value;
        }

        //add billing & shipping addresses
        $data['order.shipping_address'] = $creditMemo->getOrder()->getShippingAddress()->format('text');
        $data['order.billing_address'] = $creditMemo->getOrder()->getBillingAddress()->format('text');

        // add taxvat attribute if exists
        $customerId = $creditMemo->getOrder()->getCustomerId();
        $customer = Mage::getModel('Customer/customer')->load($customerId);

        $taxvat = $customer->gettaxvat();

        if ($taxvat != "") {
            $data['order.billing_address'] .= "\nTax/VAT : $taxvat";
        }

        if ($creditMemo->getOrder()->getShippingAddress())
            $data['order.shipping_address'] = $creditMemo->getOrder()->getShippingAddress()->format('text');
        else
            $data['order.shipping_address'] = '';

        //payment method
        if ($creditMemo->getOrder()->getPayment())
            $data['order.payment_method'] = $creditMemo->getOrder()->getPayment()->getMethodInstance()->gettitle();
        else
            $data['order.payment_method'] = '';

        //invoice items
        $data['creditmemo.items'] = array();
        foreach ($creditMemo->getAllItems() as $item) {
            $itemData = $item->getData();
            $itemData['parent_item_id'] = $item->getOrderItem()->getparent_item_id();
            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            //Add content description
            $itemData['description'] = $this->getItemDescription($item, $creditMemo->getAllItems());
            $data['creditmemo.items'][] = $itemData;
        }

        //history items
        //todo : add in template + create an option to show / hide them
        $data['creditmemo.history'] = array();
        foreach ($creditMemo->getCommentsCollection() as $item) {
            $itemData = $item->getData();
            $data['creditmemo.history'][] = $itemData;
        }
    //echo'<pre>'; print_r($data); echo'</pre>'; die('*');
        return $data;
    }

    /**
     * Return item description
     */
    protected function getItemDescription($item, $allItems) {
        $value = '';

        //add options depending of product type
        switch ($item->getOrderItem()->getproduct_type()) {
            case 'simple':
                //nothing
                break;
            case 'configurable':
                //find subitem
                $subProduct = null;
                foreach ($allItems as $subItem) {
                    if ($item->getOrderItem()->getId() == $subItem->getOrderItem()->getparent_item_id())
                        $subProduct = mage::getModel('catalog/product')->load($subItem->getproduct_id());
                }
                if ($subProduct == null)
                    return '';

                //display sub product configurable attributes values
                $configurableProduct = mage::getModel('catalog/product')->load($item->getproduct_id());
                $attributes = $configurableProduct->getTypeInstance()->getConfigurableAttributesAsArray($configurableProduct);
                foreach ($attributes as $att) {
                    $value .= $att['label'] . ' : ' . $subProduct->getAttributeText($att['attribute_code']) . "\n";
                }
                break;
            case 'bundle':
                //find sub products and build description
                foreach ($allItems as $subItem) {
                    if ($item->getOrderItem()->getId() == $subItem->getOrderItem()->getparent_item_id())
                        $value .= ( (int) $subItem->getqty()) . 'x ' . $subItem->getname() . "\n";
                }

                break;
        }

        //Add custom options
        $options = $item->getOrderItem()->getProductOptions();
        if ($options && (isset($options['options']))) {
            foreach ($options['options'] as $option) {
                $value .= $option['label'] . ' : ' . $option['print_value'] . "\n";
            }
        }

        return $value;
    }

}
