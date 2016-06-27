<?php

class MDN_GlobalPDF_Model_Pdf_Order_Invoice extends MDN_GlobalPDF_Model_Pdfhelper {

    public function getPdf($invoices = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null) // todo : test avec if(!isset($pdf)) => verification generation des documents depuis l'erp, mettre tout à oui
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        foreach ($invoices as $invoice) {

            //Set current store id depending of invoice store id
            Mage::app()->getLocale()->emulate($invoice->getStoreId());
            Mage::app()->setCurrentStore($invoice->getStoreId());

            //add new page
            $page = $this->NewPage();

            //set data for both order & invoice
            $data = $this->setTemplateData($invoice);

            //draw invoice template
            $templatePath = mage::helper('GlobalPDF')->getInvoiceTemplatePath();
            $xml = file_get_contents($templatePath);
            $this->drawTemplate($xml, $page, $data);

            //revert current store id
            Mage::app()->getLocale()->revert();
        }

        $this->_afterGetPdf();
        return $this->pdf;
    }

    protected function setTemplateData($invoice) {
        $data = array();

        //main information
        foreach ($invoice->getData() as $key => $value) {
            $data['invoice.' . $key] = $value;
        }
        foreach ($invoice->getOrder()->getData() as $key => $value) {
            $data['order.' . $key] = $value;
        }

        //add billing addresses
        $badChar = array("\r", "\r\n", "\t", "\v");
        $data['order.billing_address'] = str_replace($badChar, "\n", $invoice->getOrder()->getBillingAddress()->format('text'));
        // add method to get the complete custom address : retunr an array
        $customerEnhancedAddress = $this->getEnhancedCustomerAddress($invoice->getOrder());
        $data['order.customer_country'] = $customerEnhancedAddress['country']; //$customerEnhancedAddress['firstname']."\n".$customerEnhancedAddess['latname']."\n".$customerEnhancedAddess['email'];
        
        // add taxvat attribute if exists
        $customerId = $invoice->getOrder()->getCustomerId();
        $customer = Mage::getModel('Customer/customer')->load($customerId);
        $taxvat = $customer->gettaxvat();

        if ($taxvat != "") {
            $data['order.billing_address'] .= "\nTax/VAT : $taxvat";
        }

        //add shipping addresses
        if ($invoice->getOrder()->getShippingAddress()) {
            $data['order.shipping_address'] = $invoice->getOrder()->getShippingAddress()->format('text');
        }
        else
            $data['order.shipping_address'] = '';

        //payment method
        if ($invoice->getOrder()->getPayment()) {
            $data['order.payment_method'] = $invoice->getOrder()->getPayment()->getMethodInstance()->gettitle();

            //add payment information
            foreach ($invoice->getOrder()->getPayment()->getData() as $key => $value) {
                if ($value == null)
                    $value = '';
                $data['payment.' . $key] = $value;
            }
        }
        else
            $data['order.payment_method'] = '';

        //invoice items
        $data['invoice.items'] = array();
        foreach ($invoice->getAllItems() as $item) {
            $itemData = $item->getData();
            $itemData['parent_item_id'] = $item->getOrderItem()->getparent_item_id();
            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            $itemData['row_total'] = $itemData['row_total'] + $itemData['tax_amount'];

            //we add product information
            $product = Mage::getModel('catalog/product')->load($item->getproduct_id());
            foreach ($product->getData() as $key => $value) {
                if ($key != 'price')
                    $itemData[$key] = $value;
            }

            //Add product description
            $itemData['description'] = $this->getItemDescription($item, $invoice->getAllItems());
            $data['invoice.items'][] = $itemData;
        }

        //history items
        //todo : add in template + create an option to show / hide them
        $data['invoice.history'] = array();
        foreach ($invoice->getCommentsCollection() as $item) {
            $itemData = $item->getData();
            $data['invoice.history'][] = $itemData;
        }

        // echo'<pre>'; print_r($data); echo'</pre>'; die('*');

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
                // nothing
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

    /**
     * get 13 BILLING details for the customer billing address
     * @param type $orderInfo
     * @return array 
     */
    public function getEnhancedCustomerAddress($orderInfo) {

        $tabAddress = array('prefix'=>'', 'middlename'=>'', 'suffix'=>'', 'telephone'=>'', 'fax'=>'', 'email'=>'', 'street'=>'', 'lastname'=>'', 'firstname'=>'', 'postcode'=>'', 'city'=>'', 'country'=>'', 'company'=>'' );

        $tabAddress['prefix'] = $orderInfo->getBillingAddress()->getprefix();
        $tabAddress['middlename'] = $orderInfo->getBillingAddress()->getmiddlename();
        $tabAddress['suffix'] = $orderInfo->getBillingAddress()->getsuffix();
        $tabAddress['telephone'] = $orderInfo->getBillingAddress()->gettelephone();
        $tabAddress['fax'] = $orderInfo->getBillingAddress()->getfax();
        $tabAddress['email'] = $orderInfo->getBillingAddress()->getemail();
        $tabAddress['street'] = $orderInfo->getBillingAddress()->getstreet(); // $street[0] + [1]
        $tabAddress['lastname'] = $orderInfo->getBillingAddress()->getlastname();
        $tabAddress['firstname'] = $orderInfo->getBillingAddress()->getfirstname();
        $tabAddress['postcode'] = $orderInfo->getBillingAddress()->getpostcode();
        $tabAddress['city'] = $orderInfo->getBillingAddress()->getcity();
        $tabAddress['country'] = $orderInfo->getBillingAddress()->getcountry();
        $tabAddress['company'] = $orderInfo->getBillingAddress()->getcompany();

        // format the country label
        $_countries = Mage::getResourceModel('directory/country_collection')
                ->loadData()
                ->toOptionArray(false);

        foreach ($_countries as $countryItem) {
            if ($countryItem["value"] == $tabAddress['country']) {
                $tabAddress['country'] = $countryItem["label"];
                break;
            }
        }

        return $tabAddress;
    }

}
