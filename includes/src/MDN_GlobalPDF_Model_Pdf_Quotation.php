<?php

class MDN_GlobalPDF_Model_Pdf_Quotation extends MDN_GlobalPDF_Model_Pdfhelper {

    /**
     * Print cart...
     */
    public function getPdf($quote = array()) {

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        //add new page
        $page = $this->NewPage();

        //set data for both order & credit memo
        $quote = $quote[0];
        $data = $this->setTemplateData($quote);

        //draw quotation template
        $templatePath = mage::helper('GlobalPDF')->getQuotationTemplatePath();
        $xml = file_get_contents($templatePath);
        $this->drawTemplate($xml, $page, $data);

        $this->_afterGetPdf();
        return $this->pdf;
    }

    /**
     * Set arrays with data for template
     */
    protected function setTemplateData($quote) {

        $data = array();

        //main quotation datas
        foreach($quote->getData() as $k => $value)
        {
            $data['quotation.'.$k] = $value;
        }
        
        // add others datas
        $data['quotation.store_id'] = $quote->getStoreId();
        $data['quotation.currency_code'] = Mage::getStoreConfig('currency/options/base', $quote->getStoreId());
        $data['quotation.tax_amount'] = $quote->GetTaxAmount();
        $data['quotation.final_price_without_tax'] = $quote->GetFinalPriceWithoutTaxes();
        $data['quotation.final_price_with_tax'] = $quote->GetFinalPriceWithTaxes();
        $data['quotation.agreement'] = Mage::getStoreConfig('quotation/pdf/agreement');

        //add products information
        $data['quotation.items'] = array();
        foreach($quote->getItems() as $item)
        {
            $itemData = $item->getData();
            array_push($itemData, 'subtotal_ht'); // append the subtotal
            $itemData['subtotal_ht'] = round( ( 1 -  ($itemData['discount_purcent'] / 100) ) * $itemData['price_ht'] , 2 );
            
            //Add product description
            if( $item->getOptionsValuesAsText() == '' ) {
                $itemData['product_description'] = '';
            } else 
            $itemData['product_description'] = $item->getOptionsValuesAsText();
            
            $data['quotation.items'][] = $itemData;
        }
        
        //add quotation general settings
        if ($quote->hasBusinessProposal()) {

            $data['quotation.proposals'] = array();
            
             //call quotation's helper to get the Json array from the xml record
            $businessProposalTab = json_decode( Mage::helper('quotation/Proposal')->asArray($quote->getid()), true);

            //add proposal
                $data['quotation.proposals'] = $businessProposalTab;


        } else {
            $data['quotation.business_proposal'] = '0';
        }

        //Add customer information
        $customer = $quote->getCustomer();
        foreach($customer->getData() as $k => $v)
                $data['customer.'.$k] = $v;
        if ($quote->GetCustomerAddress())
            $data['quotation.customer_address'] = $quote->GetCustomerAddress()->format('text');
        else
            $data['quotation.customer_address'] = '';

  //  echo'<pre>';    print_r($data);  echo'</pre>';  die();
        return $data;

    }

}
