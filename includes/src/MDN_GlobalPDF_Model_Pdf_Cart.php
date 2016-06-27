<?php

class MDN_GlobalPDF_Model_Pdf_Cart extends MDN_GlobalPDF_Model_Pdfhelper {

    /**
     * Print cart...
     */
    public function getPdf($nada = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        //add new page
        $page = $this->NewPage();

        //set data for both order & credit memo
        $cart = mage::helper('checkout/cart');
        $data = $this->setTemplateData($cart);

        //draw invoice template
        $templatePath = mage::helper('GlobalPDF')->getCartTemplatePath();
        $xml = file_get_contents($templatePath);
        $this->drawTemplate($xml, $page, $data);

        $this->_afterGetPdf();
        return $this->pdf;
    }

    /**
     * Set arrays with data for template
     */
    protected function setTemplateData($cart) {
        $data = $cart->getCart()->getQuote()->getData();
        $data['items'] = array();

        //Add products
        foreach ($cart->getCart()->getQuote()->getAllItems() as $item) {
            $itemData = $item->getData();
            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            //Add content description
            $itemData['description'] .= $this->getItemDescription($item, $cart->getCart()->getQuote()->getAllItems());

            //add product datas
            $productDatas = $item->getProduct()->getData();
            foreach ($productDatas as $key => $value) {
                if (!isset($itemData[$key]))
                    $itemData[$key] = $value;
            }

            $data['items'][] = $itemData;

            $data['subtotal_include_tax'] += $data['items']['row_total_incl_tax'];
        }

        //add shipping method
        if ($cart->getCart()->getQuote()->getShippingAddress()) {
            $data['shipping_amount'] = $cart->getCart()->getQuote()->getShippingAddress()->getShippingAmount();
            $data['shipping_description'] = $cart->getCart()->getQuote()->getShippingAddress()->getShippingDescription();
        }

        //manually add tax amount
        $data['tax_amount'] = $data['grand_total'] - $data['subtotal'] - $data['shipping_amount'];
        //$data['tax_amount'] = $data['base_grand_total'] - $data['base_subtotal_with_discount'] - $data['base_shipping_amount'];
        //echo'<pre>';        var_dump($data);  echo'</pre>';  die();
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
                        $subProduct = mage::getModel('catalog/product')->load($subItem->getProduct()->getId());
                }
                if ($subProduct == null)
                    return '';

                //display sub product configurable attributes values
                $configurableProduct = $item->getProduct();
                $attributes = $configurableProduct->getTypeInstance()->getConfigurableAttributesAsArray($configurableProduct);
                foreach ($attributes as $att) {
                    $value .= $att['label'] . ' : ' . $subProduct->getAttributeText($att['attribute_code']) . "\n";
                }
                break;
            case 'bundle':
                //find sub products and build description
                foreach ($allItems as $subItem) {
                    if ($item->getId() == $subItem->getparent_item_id())
                        $value .= ((int) $subItem->getqty()) . 'x ' . $subItem->getname() . "\n";
                }

                break;
        }

        //Add custom options
        $options = array();
        $optionIds = $item->getOptionByCode('option_ids');
        if ($optionIds) {
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $item->getProduct()->getOptionById($optionId)) {

                    $quoteItemOption = $item->getOptionByCode('option_' . $option->getId());

                    $group = $option->groupFactory($option->getType())
                            ->setOption($option)
                            ->setQuoteItemOption($quoteItemOption);

                    $currentOptions = array(
                        'label' => $option->getTitle(),
                        'value' => $group->getFormattedOptionValue($quoteItemOption->getValue()),
                        'print_value' => $group->getPrintableOptionValue($quoteItemOption->getValue())
                    );

                    $value .= $currentOptions['label'] . ' : ' . $currentOptions['print_value'] . "\n";
                }
            }
        }

        return $value;
    }

}
