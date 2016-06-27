<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Order_View_Item extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayOrderViewItem');
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

        $this->addColumn('tax_amount', array(
            'header'         => Mage::helper('M2ePro')->__('Tax Amount'),
            'align'          => 'left',
            'width'          => '80px',
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => array($this, 'callbackColumnTaxAmount')
        ));

        $this->addColumn('tax_percent', array(
            'header'         => Mage::helper('M2ePro')->__('Tax Percent'),
            'align'          => 'left',
            'width'          => '80px',
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => array($this, 'callbackColumnTaxPercent')
        ));

        $this->addColumn('qty_sold', array(
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
        $html = '<b>'.Mage::helper('M2ePro')->escapeHtml($row->getTitle()).'</b><br />';

        $variation = $row->getChildObject()->getVariation();
        if (!empty($variation)) {
            foreach ($variation as $optionName => $optionValue) {
                $optionNameHtml = Mage::helper('M2ePro')->escapeHtml($optionName);
                $optionValueHtml = Mage::helper('M2ePro')->escapeHtml($optionValue);

                $html .= <<<HTML
<span style="font-weight: bold; font-style: italic; padding-left: 10px;">
{$optionNameHtml}:&nbsp;
</span>
{$optionValueHtml}<br />
HTML;
            }
        }

        $eBayItemUrl = Mage::helper('M2ePro/Component_Ebay')->getItemUrl(
            $row->getItemId(),
            $this->order->getAccount()->getChildObject()->getMode(),
            $this->order->getMarketplaceId()
        );

        $html .= '<a href="'.$eBayItemUrl.'" target="_blank">' . Mage::helper('M2ePro')->__('View on eBay').'</a>';

        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('adminhtml/catalog_product/edit', array('id' => $productId));
            $html .= ' | <a href="'.$productUrl . '" target="_blank">' . Mage::helper('M2ePro')->__('View').'</a>';
        }

        return $html;
    }

    public function callbackColumnOriginalPrice($value, $row, $column, $isExport)
    {
        $productId = $row->getData('product_id');
        $formattedPrice = '0';

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
            $row->getData('currency'), ($row->getData('qty_purchased') * $row->getData('price'))
        );
    }

    public function callbackColumnTaxAmount($value, $row, $column, $isExport)
    {
        $taxRate = (float)$this->order->getData('tax_rate');
        $taxAmount = 0;

        if ($taxRate != 0) {
            /** @var $taxCalculator Mage_Tax_Model_Calculation */
            $taxCalculator = Mage::getSingleton('tax/calculation');
            $taxAmount = $taxCalculator->calcTaxAmount(
                $row->getData('price'), $taxRate, $this->order->getChildObject()->hasVat(), true
            );
        }

        return Mage::helper('M2ePro/Currency')->formatPrice($row->getData('currency'), $taxAmount);
    }

    public function callbackColumnTaxPercent($value, $row, $column, $isExport)
    {
        $taxRate = (float)$this->order->getData('tax_rate');

        if ($taxRate == 0) {
            return '0%';
        }

        return sprintf('%.2f%%', $taxRate);
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
