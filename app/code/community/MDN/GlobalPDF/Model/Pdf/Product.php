<?php

class MDN_GlobalPDF_Model_Pdf_Product extends MDN_GlobalPDF_Model_Pdfhelper {

    /**
     * Take products in parameter and return PDF product sheets
     */
    public function getPdf($products = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        foreach ($products as $product) {
            //add new page
            $page = $this->NewPage();

            //set data for both order & credit memo
            $data = $this->setTemplateData($product);

            //draw invoice template
            $templatePath = mage::helper('GlobalPDF')->getProductTemplatePath();
            $xml = file_get_contents($templatePath);
            $this->drawTemplate($xml, $page, $data);
        }

        $this->_afterGetPdf();
        return $this->pdf;
    }

    /**
     * Set arrays with data for template
     */
    protected function setTemplateData($product) {
        //get product data
        $data = $product->getData();
        if (!isset($data['special_price']))
            $data['special_price'] = '';

        //if it is a bundle product, calculate default selections price
        if ($product->gettype_id() == 'bundle') {
            $data['price'] = $this->getBundlePrice($product);
        }

        //add additional images
        $data['additional_images'] = $this->getImageData($product);

        //Add custom options
        $data['custom_options'] = $this->getCustomOptionsData($product);
        $data['has_custom_options'] = (count($data['custom_options']) > 0 ? '1' : '0');

        //add additional information
        $data['additional_information'] = array();
        foreach ($this->getAdditionalData($product) as $att) {
            $data['additional_information'][] = $att;
        }
        $data['has_additional_information'] = (count($data['additional_information']) > 0 ? '1' : '0');

        //add configurable options
        $data['configurable_attributes'] = $this->getConfigurableAttributes($product);
        $data['configurations'] = $this->getConfigurations($product);
        $data['has_configurations'] = (count($data['configurations']) > 0 ? '1' : '0');

        //add bundleproduct
        $data['bundle_items'] = $this->getBundleItems($product);
        $data['has_bundle_items'] = (count($data['bundle_items']) > 0 ? '1' : '0');

        // replace <br/> by \n
        if(array_key_exists('short_description', $data)){
            $data['short_description'] = str_replace('<br/>',"\n", $data['short_description']);
        }

        //todo : add reviews


        return $data;
    }

    /**
     * Return additional information (attributes visible on frontend)
     */
    protected function getAdditionalData($product) {

        $storeId    = Mage::app()->getStore()->getId();
        $attributes = $product->setStore($storeId)->getAttributes();
        
        $data = array();
        foreach ($attributes as $attribute) { 
            if ($attribute->getIsVisibleOnFront()) { 
                
                $value = $attribute->getFrontend()->getValue($product);
                $label = $attribute->getStoreLabel();
                
                if (is_string($value)) {
                    if (strlen($value) && $product->hasData($attribute->getAttributeCode())) {
                        if ($attribute->getFrontendInput() == 'price') {
                            $value = Mage::app()->getStore()->convertPrice($value, true);
                        } elseif (!$attribute->getIsHtmlAllowedOnFront()) {
                            $value = $value;
                        }
                        
                        $data[$attribute->getAttributeCode()] = array(
                            'label' =>  $label,
                            'value' => $value,
                            'code' => $attribute->getAttributeCode()
                        );
                    }
                }
            }
        } 
        return $data;
    }

    /**
     * Return image data
     */
    protected function getImageData($product) {
        $retour = array();
        $allImage = $product->getmedia_gallery();
        foreach ($allImage['images'] as $image) {
            if ($image['disabled'] == 0) {
                //$imagePath = $imageDirectory.$image['file'];
                $imagePath = $image['file'];
                $retour[] = array('image_file' => $image['file']);
            }
        }

        return $retour;
    }

    /**
     * Return custom options data
     */
    protected function getCustomOptionsData($product) {
        $retour = array();

        foreach ($product->getOptions() as $option) {
            $optionData = $option->getData();
            $optionData['selections'] = array();
            foreach ($option->getValuesCollection() as $value) {
                //set price data
                $price = $value['price'];
                switch ($value['price_type']) {
                    case 'fixed':
                        $price = mage::helper('GlobalPDF/Tax')->getPriceInclTax($product, $price);
                        $price = Mage::app()->getStore()->convertPrice($price, true);
                        break;
                    case 'percent':
                        $price = ((int) $price) . '%';
                        break;
                    default:
                        die('Price type ' . $value['price_type'] . ' unknown !');
                        break;
                }
                $value->setoption_price($price);
                $optionData['selections'][] = $value->getData();
            }

            $retour[] = $optionData;
        }

        return $retour;
    }

    /**
     * Return configurable attributes
     */
    protected function getConfigurableAttributes($product) {
        $retour = array();

        //if product is not configurable, return
        if ($product->gettype_id() != 'configurable')
            return $retour;

        $attributes = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        foreach ($attributes as $att) {
            if($att['attribute_id'] != ''){
                $retour[] = array('label' => $att['label'], 'code' => $att['attribute_code'], 'attribute_id' => $att['attribute_id']);
            }
        }

        return $retour;
    }

    /**
     * Return configurable options datas
     */
    protected function getConfigurations($product) {
        $retour = array();

        //if product is not configurable, return
        if ($product->gettype_id() != 'configurable')
            return $retour;

        //create an array with options prices
        $optionPrices = array();
        $attributes = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        foreach ($attributes as $att) {
            $values = '';
            foreach ($att['values'] as $value) {
                $key = $att['attribute_id'] . '_' . $value['value_index'];
                $optionPrices[$key] = array('percent' => $value['is_percent'], 'value' => $value['pricing_value']);
            }
        }

        //get sub products
        $originalProductPrice = $product->getPrice();
        $subProducts = $product->getTypeInstance()->getUsedProducts(null, $product);
        $attributes = $this->getConfigurableAttributes($product);
        foreach ($subProducts as $subProduct) {
            //do not add out of stock product
            if ($subProduct->getStockItem()->getis_in_stock() == 0)
                continue;

            $subProductDescription = '';
            $subProductPrice = 0;
            $item = array();
            $item['fields'] = array();

            $price = $product->getprice();
            if ($product->getspecial_price())
                $price = $product->getspecial_price();

            //add attributes values
            foreach ($attributes as $att) {
                $item['fields'][] = array('label' => $subProduct->getAttributeText($att['code']));
                $key = $att['attribute_id'] . '_' . $subProduct->getData($att['code']);

                //apply price
                $optionPrice = $optionPrices[$key];
                if ($optionPrice['percent'] == 0) {
                    $price += $optionPrice['value'];
                } else {
                    $optionPrice = $originalProductPrice * $optionPrice['value'] / 100;
                    $price += $optionPrice;
                }
            }

            //format price & apply taxes
            $price = mage::helper('GlobalPDF/Tax')->getPriceInclTax($product, $price);
            $price = Mage::app()->getStore()->convertPrice($price, true);

            //add price
            $item['fields'][] = array('label' => $price);

            $retour[] = $item;
        }

        return $retour;
    }

    /**
     * Return bundle items
     */
    protected function getBundleItems($product) {
        $retour = array();

        //if product is not bundle, return
        if ($product->gettype_id() != 'bundle')
            return $retour;

        //get options & selections
        $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
                        $product->getTypeInstance(true)->getOptionsIds($product),
                        $product
        );
        $options = $optionCollection->appendSelections($selectionCollection, false, false);
        foreach ($options as $option) {

            $optionData = array();
            $optionData['title'] = $option->getdefault_title();
            $optionData['option_type'] = $option->gettype();
            $optionData['selections'] = array();

            //add selections
            foreach ($option->getSelections() as $selection) {

                $selectionData = array();
                $selectionData['title'] = $selection->getName();
                $selectionData['selection_default'] = ($selection->getis_default() ? '1' : '');

                $selectionPrice = mage::helper('GlobalPDF/Tax')->getPriceInclTax($selection, $selection->getPrice());
                $selectionPrice = Mage::app()->getStore()->convertPrice($selectionPrice, true);
                $selectionData['selection_price'] = $selectionPrice;

                $optionData['selections'][] = $selectionData;
            }


            $retour[] = $optionData;
        }

        return $retour;
    }

    /**
     * Calculate bundle default price
     */
    private function getBundlePrice($product) {
        $value = 0;

        //get options & selections
        $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
                        $product->getTypeInstance(true)->getOptionsIds($product),
                        $product
        );
        $options = $optionCollection->appendSelections($selectionCollection, false, false);
        foreach ($options as $option) {
            foreach ($option->getSelections() as $selection) {
                if ($selection->getis_default())
                    $value += $selection->getPrice();
            }
        }

        return $value;
    }

}
