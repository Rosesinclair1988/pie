<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_GlobalPDF_Block_Catalog_Products extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('ProductsGrid');
        $this->_parentTemplate = $this->getTemplate();
        $this->setEmptyText(Mage::helper('GlobalPDF')->__('No Items'));
        $this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {	
    		
    	//Charge la collection
        $collection = Mage::getModel('Catalog/Product')
        	->getCollection()
        	->addAttributeToSelect('*');
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {               
        $this->addColumn('Sku', array(
            'header'=> Mage::helper('GlobalPDF')->__('Sku'),
            'index' => 'sku'
        ));
        
        $this->addColumn('name', array(
            'header'=> Mage::helper('GlobalPDF')->__('Name'),
            'index' => 'name',
        ));
                
        $this->addColumn('status', array(
            'header'    => Mage::helper('GlobalPDF')->__('Status'),
            'width'     => '80',
            'index'     => 'status',
            'type'  => 'options',
            'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));
		
        $this->addColumn('visibility', array(
            'header'    => Mage::helper('GlobalPDF')->__('Visibility'),
            'width'     => '80',
            'index'     => 'visibility',
            'type'  => 'options',
            'options' => Mage::getModel('catalog/product_visibility')->getOptionArray()
        ));
        
        $this->addColumn('select', array(
            'header'    => Mage::helper('GlobalPDF')->__('Select'),
            'width'     => '80',
            'index'     => 'entity_id',
			'renderer' 	=> 'MDN_GlobalPDF_Block_Widget_Grid_Column_Renderer_SelectProduct'
        ));
		
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('GlobalPDF/Catalog/ProductsGrid', array('_current'=>true));
    }

    public function getGridParentHtml()
    {
        $templateName = Mage::getDesign()->getTemplateFilename($this->_parentTemplate, array('_relative'=>true));
        return $this->fetchView($templateName);
    }
    
    public function getRowUrl($row)
    {
    	//nothing
    }
    
}
