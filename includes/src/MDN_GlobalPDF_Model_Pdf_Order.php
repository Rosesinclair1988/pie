<?php

class MDN_GlobalPDF_Model_Pdf_Order extends MDN_GlobalPDF_Model_Pdfhelper {

    /**
     * Print order...
     */
    public function getPdf($orders = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        $order = $orders[0];

        //add new page
        $page = $this->NewPage(array('store_id' => $order->getStoreId()));

        //set data for both order & credit memo
        $data = $this->setTemplateData($order);

        //draw invoice template
        $templatePath = mage::helper('GlobalPDF')->getOrderTemplatePath();
        $xml = file_get_contents($templatePath);
        $this->drawTemplate($xml, $page, $data);

        $this->_afterGetPdf();
        return $this->pdf;
    }

    protected function setTemplateData($order) {
        $data = $order->getData();

        //Set addresses
        $data['billing_address'] = $order->getBillingAddress()->format('text');
        if ($order->getShippingAddress())
            $data['shipping_address'] = $order->getShippingAddress()->format('text');
        else
            $data['shipping_address'] = '';

        //payment method
        if ($order->getPayment())
            $data['payment_method'] = $order->getPayment()->getMethodInstance()->gettitle();
        else
            $data['payment_method'] = '';


        //items
        $data['items'] = array();
        foreach ($order->getAllItems() as $item) {
            $itemData = $item->getData();
            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            //Add content description
            $itemData['description'] .= $this->getItemDescription($item, $order->getAllItems());

            $data['items'][] = $itemData;
        }

        //manually add tax amount
        $data['tax_amount'] = $data['grand_total'] - $data['subtotal'] - $data['shipping_amount'] - $data['discount_amount'];

        return $data;
    }

    /**
     * Return item description
     */
    protected function getItemDescription($item, $allItems) {
        $value = '';

        //add options depending of product type
        switch ($item->getproduct_type()) {
            case 'simple':
                //nothing
                break;
            case 'configurable':
                //find subitem
                $subProduct = null;
                foreach ($allItems as $subItem) {
                    if ($item->getId() == $subItem->getparent_item_id())
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
                    if ($item->getId() == $subItem->getparent_item_id())
                        $value .= ((int) $subItem->getqty_ordered()) . 'x ' . $subItem->getname() . "\n";
                }

                break;
        }

        //Add custom options
        $options = $item->getProductOptions();
        if ($options && (isset($options['options']))) {
            foreach ($options['options'] as $option) {
                $value .= $option['label'] . ' : ' . $option['print_value'] . "\n";
            }
        }

        return $value;
    }

}
