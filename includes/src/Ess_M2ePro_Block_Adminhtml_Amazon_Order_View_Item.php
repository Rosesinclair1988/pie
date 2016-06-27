<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Order_View_Item extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonOrderViewItem');
        //------------------------------

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
        //------------------------------

        /** @var $order Ess_M2ePro_Model_Order */
        $this->order = Mage::helper('M2ePro')->getGlobalValue('temp_data');
    }

    protected function _prepareCollection()
    {
        $collection = $this->order->getItemsCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('M2ePro')->__('Product'),
            'align'     => 'left',
            'width'     => '*',
            'index'     => 'product_id',
            'frame_callback' => array($this, 'callbackColumnProduct')
        ));

        $this->addColumn('original_price', array(
            'header'    => Mage::helper('M2ePro')->__('Original Price'),
            'align'     => 'left',
            'width'     => '80px',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnOriginalPrice')
        ));

        $this->addColumn('price', array(
            'header'    => Mage::helper('M2ePro')->__('Price'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'price',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('qty_purchased', array(
            'header'    => Mage::helper('M2ePro')->__('Qty'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'qty_purchased'
        ));

        $this->addColumn('row_total', array(
            'header'    => Mage::helper('M2ePro')->__('Row Total'),
            'align'     => 'left',
            'width'     => '80px',
            'frame_callback' => array($this, 'callbackColumnRowTotal')
        ));

        return parent::_prepareColumns();
    }

    //##############################################################

    public function callbackColumnProduct($value, $row, $column, $isExport)
    {
        $skuHtml = '';
        if ($row->getSku()) {
            $skuLabel = Mage::helper('M2ePro')->__('SKU');
            $sku = Mage::helper('M2ePro')->escapeHtml($row->getSku());

            $skuHtml = <<<HTML
<b>{$skuLabel}:</b> {$sku}<br />
HTML;
        }

        $generalIdLabel = Mage::helper('M2ePro')->__($row->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN');
        $generalId = Mage::helper('M2ePro')->escapeHtml($row->getGeneralId());

        $generalIdHtml = <<<HTML
<b>{$generalIdLabel}:</b> {$generalId}<br />
HTML;

        $itemTitle = Mage::helper('M2ePro')->escapeHtml($row->getTitle());

        $itemUrl = Mage::helper('M2ePro/Component_Amazon')->getItemUrl($row->getGeneralId(),
                                                                       $this->order->getData('marketplace_id'));
        $itemLinkText = Mage::helper('M2ePro')->__('View on Amazon');

        $productLink = '';
        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $productId));
            $productLink = ' | <a href="'.$productUrl.'" target="_blank">'.Mage::helper('M2ePro')->__('View').'</a>';
        }

        return <<<HTML
<b>{$itemTitle}</b><br />
<div style="padding-left: 10px;">
    {$skuHtml}
    {$generalIdHtml}
</div>
<a href="{$itemUrl}" target="_blank">{$itemLinkText}</a>
{$productLink}
HTML;
    }

    public function callbackColumnOriginalPrice($value, $row, $column, $isExport)
    {
        $productId = $row->getData('product_id');
        $formattedPrice = Mage::helper('M2ePro')->__('N/A');

        if ($productId && $product = Mage::getModel('catalog/product')->load($productId)) {
            $formattedPrice = $product->getFormatedPrice();
        }

        return $formattedPrice;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        return Mage::helper('M2ePro/Currency')->formatPrice($row->getData('currency'), $row->getData('price'));
    }

    public function callbackColumnRowTotal($value, $row, $column, $isExport)
    {
        return Mage::helper('M2ePro/Currency')->formatPrice(
            $row->getData('currency'), $row->getData('price') * $row->getData('qty_purchased')
        );
    }

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/orderItemGrid', array('_current' => true));
    }
}
