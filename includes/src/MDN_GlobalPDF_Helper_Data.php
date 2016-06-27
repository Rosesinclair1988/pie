<?php

class MDN_GlobalPDF_Helper_Data extends Mage_Core_Helper_Abstract {

    private $_customDatas = array();

    /**
     * Return directory where are stored templates
     */
    public function getTemplateDirectory() {
        $directory = Mage::getBaseDir() . DS . 'media/GlobalPdf/';
        $directory .= Mage::getStoreConfig('globalpdf/general/theme') . '/';

        return $directory;
    }

    public function getHeaderTemplatePath() {
        return $this->getTemplateDirectory() . 'header.xml';
    }

    public function getEventTemplatePath() {
        return $this->getTemplateDirectory() . 'event.xml';
    }

    public function getFooterTemplatePath() {
        return $this->getTemplateDirectory() . 'footer.xml';
    }

    public function getInvoiceTemplatePath() {
        return $this->getTemplateDirectory() . 'invoice.xml';
    }

    public function getCreditMemoTemplatePath() {
        return $this->getTemplateDirectory() . 'creditmemo.xml';
    }

    public function getOrderTemplatePath() {
        return $this->getTemplateDirectory() . 'order.xml';
    }

    public function getCatalogTemplatePath() {
        return $this->getTemplateDirectory() . 'catalog.xml';
    }

    public function getShipmentTemplatePath() {
        return $this->getTemplateDirectory() . 'shipment.xml';
    }

    public function getProductTemplatePath() {
        return $this->getTemplateDirectory() . 'product.xml';
    }

    public function getCartTemplatePath() {
        return $this->getTemplateDirectory() . 'cart.xml';
    }

    public function getQuotationTemplatePath() {
        return $this->getTemplateDirectory() . 'quotation.xml';
    }

    public function getProductReturnTemplatePath() {
        return $this->getTemplateDirectory() . 'productreturn.xml';
    }

    public function getPurchaseTemplatePath() {
        return $this->getTemplateDirectory() . 'purchase.xml';
    }
    
    //set Additional datas
    public function setAdditionalDatas($data) {
        if ($data == null)
            $data = array();

        //add general codes
        $data['current_store_id'] = Mage::app()->getStore()->getId();
        $store_id = (array_key_exists('store_id', $data) ? $data['store_id'] : $data['current_store_id']);
        if (empty($data['store_address']))
            $data['store_address'] = Mage::getStoreConfig('globalpdf/general/store_address', $store_id);
        $data['company_name'] = Mage::getStoreConfig('globalpdf/general/company_name', $store_id);
        $data['legal_information'] = Mage::getStoreConfig('globalpdf/general/legal_information', $store_id);
        $data['logo'] = Mage::getBaseDir() . DS . 'media/upload/image/' . Mage::getStoreConfig('globalpdf/general/logo', $store_id);
        $data['catalog_image_directory'] = Mage::getBaseDir() . DS . 'media/catalog/product';
        $data['globalpdf_image_directory'] = $this->getTemplateDirectory() . 'img/';

        $data['store_currency'] = Mage::app()->getStore()->getDefaultCurrencyCode();

        return $data;
    }

    public function getMissingImagePath() {
        return $this->getTemplateDirectory() . 'img/missing.jpg';
    }

    public function getCacheDirectory() {
        $directory = Mage::getBaseDir() . DS . 'media' . DS . 'GlobalPdf' . DS . 'cache' . DS;
        return $directory;
    }

    public function cacheEnabled() {
        return Mage::getStoreConfig('globalpdf/general/enable_cache');
    }

    public function addCustomData($varName, $varValue) {
        $this->_customDatas[$varName] = $varValue;
    }

    public function getCustomData() {
        return $this->_customDatas;
    }

}

?>
