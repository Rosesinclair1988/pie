<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Listing_MoveToListing_FailedProducts_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('listingFailedProductsGrid');
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
        $failedProducts = json_decode($this->getRequest()->getParam('failed_products'),1);

        $collection = Mage::getModel('M2ePro/'.$model)->getCollection();
        $collection->addFieldToFilter('main_table.id',array('in' => $failedProducts));
        $collection->getSelect()->distinct();
        //--------------------------------

        // Communicate with magento product table
        //--------------------------------
        $temp = new Zend_Db_Expr('MAX(`store_id`)');
        $dbSelect = Mage::getResourceModel('core/config')->getReadConnection()
            ->select()
            ->from(Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_varchar'),$temp)
            ->where("`entity_id` = `main_table`.`product_id`")
            ->where("`attribute_id` = `ea`.`attribute_id`");

        $collection->getSelect()->join(
            array('cpe'=>Mage::getSingleton('core/resource')->getTableName('catalog_product_entity')),
            '(cpe.entity_id = `main_table`.product_id)',
            array('sku')
        );
        $collection->getSelect()->join(
            array('cisi'=>Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_item')),
            '(cisi.product_id = `main_table`.product_id AND cisi.stock_id = 1)',
            array('is_in_stock')
        );
        $collection->getSelect()->join(
            array('cpev'=>Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_varchar')),
            "( `cpev`.`entity_id` = `main_table`.product_id AND cpev.store_id = (".$dbSelect->__toString()."))",
            array('value')
        );
        $collection->getSelect->join(
            array('ea'=>Mage::getSingleton('core/resource')->getTableName('eav_attribute')),
            '(`cpev`.`attribute_id` = `ea`.`attribute_id` AND `ea`.`attribute_code` = \'name\')',
            array()
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('M2ePro')->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'product_id',
            'filter_index' => 'main_table.product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('M2ePro')->__('Product Title / SKU'),
            'align'     => 'left',
            'width'     => '250px',
            'type'      => 'text',
            'index'     => 'value',
            'filter_index' => 'cpev.value',
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
                Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED => Mage::helper('M2ePro')->__('Not Listed'),
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

        $value = '<span>'.Mage::helper('M2ePro')->escapeHtml($value).'</span>';

        $tempSku = $row->getData('magento_sku');
        if (is_null($tempSku)) {
            $tempSku = Mage::getModel('M2ePro/Magento_Product')->setProductId($row->getData('product_id'))->getSku();
        }

        $value .= '<br/><strong>'.Mage::helper('M2ePro')->__('SKU').':</strong> ';
        $value .= Mage::helper('M2ePro')->escapeHtml($tempSku);

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
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            default:
                break;
        }

        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('cpev.value LIKE ? OR cpe.sku LIKE ?', '%'.$value.'%');
    }

    // ####################################

    protected function _toHtml()
    {
        $javascriptsMain = <<<JAVASCRIPT
<script type="text/javascript">

    $$('#listingFailedProductsGrid div.grid th').each(function(el){
        el.style.padding = '4px';
    });

    $$('#listingFailedProductsGrid div.grid td').each(function(el){
        el.style.padding = '4px';
    });

</script>
JAVASCRIPT;

        return parent::_toHtml() . $javascriptsMain;
    }

    // ####################################

    public function getGridUrl()
    {
        return $this->getUrl('*/adminhtml_listing/failedProductsGrid', array(
            '_current'=>true
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    // ####################################
}
