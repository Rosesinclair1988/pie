<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class   Ess_M2ePro_Block_Adminhtml_Listing_Other_MoveToListing_FailedProducts_Grid
extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('listingOtherFailedProductsGrid');
        //------------------------------

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //------------------------------
    }

    protected function _prepareCollection()
    {
        $model = $this->getRequest()->getParam('model');
        $component = $this->getRequest()->getParam('component');
        $failedProducts = json_decode($this->getRequest()->getParam('failed_products'),1);

        $collection = Mage::helper('M2ePro/Component_'.ucfirst($component))->getCollection($model);
        $collection->addFieldToFilter('id',array('in' => $failedProducts));

//        var_dump($collection->getItems());die;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header' => Mage::helper('M2ePro')->__('Product ID'),
            'align'  => 'left',
            'width'  => '100px',
            'type'   => 'number',
            'index'  => 'product_id',
            'filter_index' => 'product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('M2ePro')->__('Product Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'width' => '250px',
            'index' => 'title',
            'filter_index' => 'second_table.title',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('M2ePro')->__('Status'),
            'width' => '80px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN => Mage::helper('M2ePro')->__('Unknown'),
                Ess_M2ePro_Model_Listing_Product::STATUS_LISTED => Mage::helper('M2ePro')->__('Active'),
                Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED => Mage::helper('M2ePro')->__('Inactive')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        return parent::_prepareColumns();
    }

    // ####################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('product_id');
        $url = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $productId));
        return '<a href="'.$url.'" target="_blank">'.$productId.'</a>';
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        if (strlen($value) > 60) {
            $value = substr($value, 0, 60) . '...';
        }

        $value = '<span>' . Mage::helper('M2ePro')->escapeHtml($value) . '</span>';

        $tempSku = $row->getData('sku');
        is_null($tempSku) && $tempSku = 'N/A';

        $value .= '<br/><strong>'.Mage::helper('M2ePro')->__('SKU').':</strong> ';
        $value .= Mage::helper('M2ePro')->escapeHtml($tempSku);

        return $value;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        $url = Mage::helper('M2ePro/Component_Amazon')->getItemUrl($value, $row->getData('marketplace_id'));
        return '<a href="'.$url.'" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnSku($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        if ((bool)$row->getData('is_afn_channel')) {
            return '--';
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        return $value;
    }

    public function callbackColumnAfnChannel($value, $row, $column, $isExport)
    {
        switch ($row->getData('is_afn_channel')) {
            case Ess_M2ePro_Model_Amazon_Listing_Product::IS_ISBN_GENERAL_ID_YES:
                $value = '<span style="font-weight: bold;">' . $value . '</span>';
                break;

            default:
                break;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {

            case Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN:
            case Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED:
                $value = '<span style="color: gray;">' . $value . '</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED:
                $value = '<span style="color: red;">' . $value . '</span>';
                break;

            default:
                break;
        }

        return $value;
    }

    public function callbackColumnStartDate($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        return $value;
    }

    public function callbackColumnEndDate($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%'.$value.'%');
    }

    // ####################################

    protected function _toHtml()
    {
        $javascriptsMain = <<<JAVASCRIPT
<script type="text/javascript">

    $$('#listingOtherFailedProductsGrid div.grid th').each(function(el){
        el.style.padding = '4px';
    });

    $$('#listingOtherFailedProductsGrid div.grid td').each(function(el){
        el.style.padding = '4px';
    });

</script>
JAVASCRIPT;

        return parent::_toHtml() . $javascriptsMain;
    }

    // ####################################

    public function getGridUrl()
    {
        return $this->getUrl('*/adminhtml_listingOther/failedProductsGrid', array(
            '_current'=>true
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    // ####################################
}
