<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Filter extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonListingFilter');
        //------------------------------

        $this->setTemplate('M2ePro/amazon/listing/filter.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $maxRecordsQuantity = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/autocomplete/',
                                                                                        'max_records_quantity');
        $maxRecordsQuantity <= 0 && $maxRecordsQuantity = 100;
        //-------------------------------

        //-------------------------------
        $this->selectedSellingFormatTemplate = (int)$this->getRequest()
                                                         ->getParam('filter_amazon_selling_format_template');
        $sellingFormatTemplatesCollection = Mage::helper('M2ePro/Component_Amazon')
                                                                        ->getCollection('Template_SellingFormat')
                                                                        ->setOrder('title', 'ASC');

        if ($sellingFormatTemplatesCollection->getSize() < $maxRecordsQuantity) {
            $this->sellingFormatTemplatesDropDown = true;
            $sellingFormatTemplates = array();

            foreach ($sellingFormatTemplatesCollection->getItems() as $item) {
                $sellingFormatTemplates[$item->getId()] = Mage::helper('M2ePro')->escapeHtml($item->getTitle());
            }
            $this->sellingFormatTemplates = $sellingFormatTemplates;
        } else {
            $this->sellingFormatTemplatesDropDown = false;
            $this->sellingFormatTemplates = array();

            if ($this->selectedSellingFormatTemplate > 0) {
                $this->selectedSellingFormatTemplateValue = Mage::helper('M2ePro/Component_Amazon')
                                                                            ->getModel('Template_SellingFormat')
                                                                            ->load($this->selectedSellingFormatTemplate)
                                                                            ->getTitle();
            } else {
                $this->selectedSellingFormatTemplateValue = '';
            }
        }

        $this->sellingFormatTemplateUrl = $this->makeCutUrlForTemplate('filter_amazon_selling_format_template');
        //-------------------------------

        //-------------------------------
        $this->selectedDescriptionTemplate = (int)$this->getRequest()->getParam('filter_amazon_description_template');
        $descriptionsTemplatesCollection = Mage::helper('M2ePro/Component_Amazon')
                                                                            ->getCollection('Template_Description')
                                                                            ->setOrder('title', 'ASC');

        if ($descriptionsTemplatesCollection->getSize() < $maxRecordsQuantity) {
            $this->descriptionsTemplatesDropDown = true;
            $descriptionsTemplates = array();

            foreach ($descriptionsTemplatesCollection->getItems() as $item) {
                $descriptionsTemplates[$item->getId()] = Mage::helper('M2ePro')->escapeHtml($item->getTitle());
            }
            $this->descriptionsTemplates = $descriptionsTemplates;
        } else {
            $this->descriptionsTemplatesDropDown = false;
            $this->descriptionsTemplates = array();

            if ($this->selectedDescriptionTemplate > 0) {
                $this->selectedDescriptionTemplateValue = Mage::helper('M2ePro/Component_Amazon')
                                                                            ->getModel('Template_Description')
                                                                            ->load($this->selectedDescriptionTemplate)
                                                                            ->getTitle();
            } else {
                $this->selectedDescriptionTemplateValue = '';
            }
        }

        $this->descriptionTemplateUrl = $this->makeCutUrlForTemplate('filter_amazon_description_template');
        //-------------------------------

        //-------------------------------
        $this->selectedSynchronizationTemplate = (int)$this->getRequest()
                                                                   ->getParam('filter_amazon_synchronization_template');
        $synchronizationsTemplatesCollection = Mage::helper('M2ePro/Component_Amazon')
                                                                        ->getCollection('Template_Synchronization')
                                                                        ->setOrder('title', 'ASC');

        if ($synchronizationsTemplatesCollection->getSize() < $maxRecordsQuantity) {
            $this->synchronizationsTemplatesDropDown = true;
            $synchronizationsTemplates = array();

            foreach ($synchronizationsTemplatesCollection->getItems() as $item) {
                $synchronizationsTemplates[$item->getId()] = Mage::helper('M2ePro')->escapeHtml($item->getTitle());
            }
            $this->synchronizationsTemplates = $synchronizationsTemplates;
        } else {
            $this->synchronizationsTemplatesDropDown = false;
            $this->synchronizationsTemplates = array();

            if ($this->selectedSynchronizationTemplate > 0) {
                $this->selectedSynchronizationTemplateValue = Mage::helper('M2ePro/Component_Amazon')
                                                                        ->getModel('Template_Synchronization')
                                                                        ->load($this->selectedSynchronizationTemplate)
                                                                        ->getTitle();
            } else {
                $this->selectedSynchronizationTemplateValue = '';
            }
        }

        $this->synchronizationTemplateUrl = $this->makeCutUrlForTemplate('filter_amazon_synchronization_template');
        //-------------------------------

        return parent::_beforeToHtml();
    }

    protected function makeCutUrlForTemplate($templateUrlParamName)
    {
        $paramsFilters = array(
            'filter_amazon_selling_format_template',
            'filter_amazon_description_template',
            'filter_amazon_synchronization_template'
        );

        $params = array();
        foreach ($paramsFilters as $value) {
            if ($value != $templateUrlParamName) {
                $params[$value] = $this->getRequest()->getParam($value);
            }
        }

        $params['tab'] = Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON;

        return $this->getUrl('*/adminhtml_listing/*',$params);
    }
}
