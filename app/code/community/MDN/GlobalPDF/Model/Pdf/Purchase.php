<?php

/**
 * 
 */
class MDN_GlobalPDF_Model_Pdf_Purchase extends MDN_GlobalPDF_Model_Pdfhelper {

    private $_showPictures = false;
    private $_pictureSize = 70;
    //------------------------------------------------------
    public $_totalHt = 0;
    public $_totalhTTC = 0;

    //------------------------------------------    

    /**
     *
     * @param type $orders 
     */
    public function getPdf($orders = array()) {

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        if ($this->pdf == null)
            $this->pdf = new Zend_Pdf();
        else
            $this->firstPageIndex = count($this->pdf->pages);

        $order = $orders[0];

        //set data for both order & credit memo
        $data = $this->setTemplateData($order);

        //add new page
        $settings = array('store_id' => 0);
        $page = $this->NewPage($settings, $data);


        //draw invoice template
        $templatePath = mage::helper('GlobalPDF')->getPurchaseTemplatePath();

        $xml = file_get_contents($templatePath);

        $this->drawTemplate($xml, $page, $data);

        $this->_afterGetPdf();

        return $this->pdf;
    }

    protected function setTemplateData($order) {

        $data = array();
        $data['store_id'] = 0;
        $data['current_date'] = $order->getpo_date(); // 2012-12-11

        // get supplier datas
        $supplier = $order->getSupplier();
        foreach ($supplier->getData() as $k => $val) {
            $data['supplier.' . $k] = $val;
        }

        //main purchase datas
        foreach ($order->getData() as $k => $value) {
            $data['purchase.' . $k] = $value;
        }

        $data['purchase.total_tax_amount'] = $order->getTaxAmount();
        $data['purchase.po_zoll_cost_ttc'] = round( ( 1 +  ($data['purchase.po_tax_rate'] / 100) ) * $data['purchase.po_zoll_cost'] , 2 );
        $data['purchase.po_shipping_cost_ttc'] = round( ( 1 +  ($data['purchase.po_tax_rate'] / 100) ) * $data['purchase.po_shipping_cost'] , 2 );
        
        //items datas
        $data['purchase.items'] = array();
        $cpt = 0;
        foreach ($order->getProducts() as $item) {

            $itemData = $item->getData();

            if (!isset($itemData['parent_item_id']))
                $itemData['parent_item_id'] = '';

            $itemData['pop_price_ht'] = round($itemData['pop_price_ht'],2); // round 2 -> 1.99 Euros
            
            $itemData['pop_subtotal'] = $itemData['pop_qty'] * $itemData['pop_price_ht']; // get sub total HT per row
            $itemData['pop_total'] =  ( 1 + ( $itemData['pop_tax_rate'] /100) ) * $itemData['pop_subtotal']; // get total per row
            
            $data['purchase.items'][] = $itemData;
            
            $cpt ++;
        }
        
        // add fake products for shipping cost and zoll cost
        $data['purchase.items'][$cpt +1]['pop_product_name'] = 'Shipping costs';
        $data['purchase.items'][$cpt +1]['pop_tax_rate'] = $data['purchase.po_tax_rate'];
        $data['purchase.items'][$cpt +1]['pop_subtotal'] = $data['purchase.po_shipping_cost'];
        $data['purchase.items'][$cpt +1]['pop_total'] = $data['purchase.po_shipping_cost_ttc'];
        $data['purchase.items'][$cpt +2]['pop_product_name'] = 'Tax & duties';
        $data['purchase.items'][$cpt +2]['pop_tax_rate'] = $data['purchase.po_tax_rate'];
        $data['purchase.items'][$cpt +2]['pop_subtotal'] = $data['purchase.po_zoll_cost'];
        $data['purchase.items'][$cpt +2]['pop_total'] = $data['purchase.po_zoll_cost_ttc'];
        
        // totals
        $data['purchase.total_excl_tax'] = $order->getTotalHt();   
        $data['purchase.total_incl_tax'] = $order->getTotalTtc();
        $data['purchase.supplier_address'] = $datas['supplier.sup_name']."\n".$data['supplier.sup_address1']."\n".$data['supplier.sup_address2']."\n".$data['supplier.sup_zipcode']."\n".$data['supplier.sup_city']."\n".$data['supplier.sup_country']."\n Tel: ".$data['supplier.sup_tel']."\n Fax: ".$data['supplier.sup_fax']."\n".$data['supplier.sup_mail'];
        
 // echo'<pre>';    print_r($data);  echo'</pre>';  die();
        
        return $data;
    }

}
