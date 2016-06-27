<?php

class MDN_GlobalPDF_Model_Pdf_Order_Shipment extends MDN_GlobalPDF_Model_Pdfhelper {

    public function getPdf($shipments = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        foreach ($shipments as $shipment) {

            //Set current store id depending of shipment store id
            Mage::app()->getLocale()->emulate($shipment->getStoreId());
            Mage::app()->setCurrentStore($shipment->getStoreId());

            //add new page
            $page = $this->NewPage();

            //set data for both order & invoice
            $data = $this->setTemplateData($shipment);


            //draw invoice template
            $templatePath = mage::helper('GlobalPDF')->getShipmentTemplatePath();
            $xml = file_get_contents($templatePath);
            $this->drawTemplate($xml, $page, $data);

            //revert current store id
            Mage::app()->getLocale()->revert();
        }

        $this->_afterGetPdf();
        return $this->pdf;
    }

    protected function setTemplateData($shipment) {
        $data = array();

        //main information
        foreach ($shipment->getData() as $key => $value) {
            $data['shipment.' . $key] = $value;
        }
        foreach ($shipment->getOrder()->getData() as $key => $value) {
            $data['order.' . $key] = $value;
        }

        //add billing & shipping addresses
        $data['order.billing_address'] = $shipment->getOrder()->getBillingAddress()->format('text');
        if ($shipment->getOrder()->getBillingAddress()){
            
            $data['order.shipping_address'] = $shipment->getOrder()->getShippingAddress()->format('text');
            //$adressWithoutNumberPhone =  explode('T: ',$data['order.shipping_address']);
            //$data['order.shipping_address'] = $adressWithoutNumberPhone[0];
            
/*
 * custom address, you can choose element to dsplay in address (tel, zip, company ...)
 * 
 * 
           $street = $shipment->getOrder()->getBillingAddress()->getstreet(); // $treet[0]
           $lastName = $shipment->getOrder()->getBillingAddress()->getlastname();
           $firstName = $shipment->getOrder()->getBillingAddress()->getfirstname();
           $postCode = $shipment->getOrder()->getBillingAddress()->getpostcode();
           $city = $shipment->getOrder()->getBillingAddress()->getcity();
           $country = $shipment->getOrder()->getBillingAddress()->getcountry();
           $company = $shipment->getOrder()->getBillingAddress()->getcompany();

           $_countries = Mage::getResourceModel('directory/country_collection')
                                    ->loadData()
                                    ->toOptionArray(false);
        
            $countryLabel ="";
        
            foreach( $_countries as $countryItem){
            
                if($countryItem["value"] == $country){
                    $countryLabel =  $countryItem["label"];
                    break;
                }
            
            }
           
           $data['order.shipping_address'] = $company."\n".$lastName." ".$firstName."\n".$street[0]."\n".$postCode." ".$city."\n".$countryLabel;
 */          
        
          
        }
        else
            $data['order.shipping_address'] = '';
        
        //payment method
        if ($shipment->getOrder()->getPayment())
            $data['order.payment_method'] = $shipment->getOrder()->getPayment()->getMethodInstance()->gettitle();
        else
            $data['order.payment_method'] = '';

        //shipment items
        $data['shipment.items'] = array();
        foreach ($shipment->getAllItems() as $item) {
            $itemData = $item->getData();
            $itemData['parent_item_id'] = $item->getOrderItem()->getparent_item_id();
            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            //Add content description
            $itemData['descriptions'] = $this->getItemDescription($item, $shipment->getAllItems());

            //we add product information
            $product = Mage::getModel('catalog/product')->load($itemData['product_id']);  
            
            // attributs
            foreach ($product->getData() as $key => $value) {
                if ($key != 'price')
                    $itemData[$key] = $value;
            }
            
            // categories
            $cats = $product->getCategoryIds();
            foreach ($cats as $category_id) {
                $_cat = Mage::getModel('catalog/category')->load($category_id) ;
                $itemData['category'] = $_cat->getName();
            } 
            
            $data['shipment.items'][] = $itemData;
        }
   
        //history items
        //todo : add in template + create an option to show / hide them
        $data['shipment.history'] = array();
        foreach ($shipment->getCommentsCollection() as $item) {
            $itemData = $item->getData();
            $data['shipment.history'][] = $itemData;
        }

        //add Tracking numbers
        $data['shipment.tracking'] = array();
        foreach ($shipment->getTracksCollection() as $item) {
            $itemData = $item->getData();
            $data['shipment.tracking'][] = $itemData;
        }
        
        // add shipping description
	$data['shipment.description'] = str_replace('_', ' ', $data['order.shipping_method']);
              
        // get order history comment from table 'sales_flat_order_status_history' column 'comment'
        // // app\code\core\Mage\Sales\Model\Resource\Order\Comment\Collection
        $order = Mage::getModel('sales/order')->load($shipment->getOrder()->getid());

        $comments =  $order->getVisibleStatusHistory(); // return collection with comment
        
        $data['order.history'] = array();

        // get visible comment on frontend
        foreach ($comments as $comment) {
            $data['order.history'][]["comment"] = $comment->getComment();
        }
//echo'<pre>'; print_r($data); echo'</pre>'; die();
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
