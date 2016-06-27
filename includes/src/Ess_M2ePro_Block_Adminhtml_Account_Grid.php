<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Account_Grid extends Ess_M2ePro_Block_Adminhtml_Component_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('accountGrid');
        //------------------------------

        // Set default values
        //------------------------------
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //------------------------------
    }

    // ####################################

    protected function _prepareCollection()
    {
        // Get collection of synchronizations
        $collection = Mage::getModel('M2ePro/Account')->getCollection();

        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) == 1) {
            if (Mage::helper('M2ePro/Component_Ebay')->isActive()) {
                $collection->addFieldToFilter('component_mode', Ess_M2ePro_Helper_Component_Ebay::NICK);
            }
            if (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
                $collection->addFieldToFilter('component_mode', Ess_M2ePro_Helper_Component_Amazon::NICK);
            }
        }

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('M2ePro')->__('ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'id',
            'filter_index' => 'main_table.id'
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('M2ePro')->__('Title'),
            'align'     => 'left',
            //'width'     => '200px',
            'type'      => 'text',
            'index'     => 'title',
            'filter_index' => 'main_table.title',
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $ebayTitle = Mage::helper('M2ePro')->__(Ess_M2ePro_Helper_Component_Ebay::TITLE);
        $amazonTitle = Mage::helper('M2ePro')->__(Ess_M2ePro_Helper_Component_Amazon::TITLE);
        $options = array(
            Ess_M2ePro_Helper_Component_Ebay::NICK   => $ebayTitle,
            Ess_M2ePro_Helper_Component_Amazon::NICK => $amazonTitle
        );

        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $this->addColumn('component_mode', array(
                'header'         => Mage::helper('M2ePro')->__('Channel'),
                'align'          => 'left',
                'width'          => '100px',
                'type'           => 'options',
                'index'          => 'component_mode',
                'filter_index'   => 'main_table.component_mode',
                'sortable'       => false,
                'options'        => $options
            ));
        }

        $this->addColumn('create_date', array(
            'header'    => Mage::helper('M2ePro')->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ));

        $this->addColumn('update_date', array(
            'header'    => Mage::helper('M2ePro')->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ));

        $confirm  = Mage::helper('M2ePro')->__('Be attentive! By deleting account ');
        $confirm .= Mage::helper('M2ePro')->__('you delete all information on it from M2E Pro server. ');
        $confirm .= Mage::helper('M2ePro')->__('This will cause inappropriate work of all accounts\' copies.');

        $this->addColumn('actions', array(
            'header'    => Mage::helper('M2ePro')->__('Actions'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption'   => Mage::helper('M2ePro')->__('Edit'),
                    'url'       => array('base'=> '*/*/edit'),
                    'field'     => 'id'
                ),
                array(
                    'caption'   => Mage::helper('M2ePro')->__('Delete'),
                    'url'       => array('base'=> '*/*/delete'),
                    'field'     => 'id',
                    'confirm'  => $confirm
                )
            )
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        //--------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        //--------------------------------

        // Set delete action
        //--------------------------------
        $confirm  = Mage::helper('M2ePro')->__('Be attentive! By deleting account');
        $confirm .= Mage::helper('M2ePro')->__(' you delete all information on it from M2E Pro server. ');
        $confirm .= Mage::helper('M2ePro')->__('This will cause inappropriate work of all accounts\' copies.');
        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('M2ePro')->__('Delete'),
             'url'      => $this->getUrl('*/*/delete'),
             'confirm'  => $confirm
        ));
        //--------------------------------

        return parent::_prepareMassaction();
    }

    // ####################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return Mage::helper('M2ePro')->escapeHtml($value);
    }

    // ####################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/accountGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getComponentRowUrl($row, 'account', 'edit', array('id' => $row->getData('id')));
    }

    // ####################################
}