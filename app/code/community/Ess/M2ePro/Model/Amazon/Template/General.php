<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Template_General extends Ess_M2ePro_Model_Component_Child_Amazon_Abstract
{
    const SKU_MODE_NONE             = 0;
    const SKU_MODE_PRODUCT_ID       = 3;
    const SKU_MODE_DEFAULT          = 1;
    const SKU_MODE_CUSTOM_ATTRIBUTE = 2;

    const GENERAL_ID_MODE_NONE             = 0;
    const GENERAL_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    const WORLDWIDE_ID_MODE_NONE             = 0;
    const WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    const CONDITION_MODE_NONE             = 0;
    const CONDITION_MODE_DEFAULT          = 1;
    const CONDITION_MODE_CUSTOM_ATTRIBUTE = 2;

    const CONDITION_NEW                    = 'New';
    const CONDITION_USED_LIKE_NEW          = 'UsedLikeNew';
    const CONDITION_USED_VERY_GOOD         = 'UsedVeryGood';
    const CONDITION_USED_GOOD              = 'UsedGood';
    const CONDITION_USED_ACCEPTABLE        = 'UsedAcceptable';
    const CONDITION_COLLECTIBLE_LIKE_NEW   = 'CollectibleLikeNew';
    const CONDITION_COLLECTIBLE_VERY_GOOD  = 'CollectibleVeryGood';
    const CONDITION_COLLECTIBLE_GOOD       = 'CollectibleGood';
    const CONDITION_COLLECTIBLE_ACCEPTABLE = 'CollectibleAcceptable';
    const CONDITION_REFURBISHED            = 'Refurbished';
    const CONDITION_CLUB                   = 'Club';

    const CONDITION_NOTE_MODE_NONE             = 0;
    const CONDITION_NOTE_MODE_CUSTOM_VALUE     = 1;
    const CONDITION_NOTE_MODE_CUSTOM_ATTRIBUTE = 2;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Template_General');
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return Ess_M2ePro_Model_Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    // ########################################

    public function getListings($asObjects = false, array $filters = array())
    {
        return $this->getParentObject->getListings($asObjects,$filters);
    }

    // ########################################

    public function getSkuMode()
    {
        return (int)$this->getData('sku_mode');
    }

    public function isSkuNoneMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_NONE;
    }

    public function isSkuProductIdMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_PRODUCT_ID;
    }

    public function isSkuDefaultMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_DEFAULT;
    }

    public function isSkuAttributeMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    public function getSkuSource()
    {
        return array(
            'mode'      => $this->getSkuMode(),
            'attribute' => $this->getData('sku_custom_attribute')
        );
    }

    //-------------------------

    public function getGeneralIdMode()
    {
        return (int)$this->getData('general_id_mode');
    }

    public function isGeneralIdNoneMode()
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_NONE;
    }

    public function isGeneralIdAttributeMode()
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    public function getGeneralIdSource()
    {
        return array(
            'mode'      => $this->getGeneralIdMode(),
            'attribute' => $this->getData('general_id_custom_attribute')
        );
    }

    //-------------------------

    public function getWorldwideIdMode()
    {
        return (int)$this->getData('worldwide_id_mode');
    }

    public function isWorldwideIdNoneMode()
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_NONE;
    }

    public function isWorldwideIdAttributeMode()
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    public function getWorldwideIdSource()
    {
        return array(
            'mode'      => $this->getWorldwideIdMode(),
            'attribute' => $this->getData('worldwide_id_custom_attribute')
        );
    }

    //-------------------------

    public function getConditionMode()
    {
        return (int)$this->getData('condition_mode');
    }

    public function isConditionNoneMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_NONE;
    }

    public function isConditionDefaultMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_DEFAULT;
    }

    public function isConditionAttributeMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_CUSTOM_ATTRIBUTE;
    }

    public function getConditionSource()
    {
        return array(
            'mode'      => $this->getData('condition_mode'),
            'value'     => $this->getData('condition_value'),
            'attribute' => $this->getData('condition_custom_attribute')
        );
    }

    public function getConditionValues()
    {
        $temp = $this->getData('cache_condition_values');

        if (!empty($temp)) {
            return $temp;
        }

        $reflectionClass = new ReflectionClass (__CLASS__);
        $tempConstants = $reflectionClass->getConstants();

        $values = array();
        foreach ($tempConstants as $key => $value) {
            $prefixKey = strtolower(substr($key,0,14));
            if (substr($prefixKey,0,10) != 'condition_' ||
                in_array($prefixKey,array('condition_mode','condition_note'))) {
                continue;
            }
            $values[] = $value;
        }

        $this->setData('cache_condition_values',$values);

        return $values;
    }

    //-------------------------

    public function getConditionNoteMode()
    {
        return (int)$this->getData('condition_note_mode');
    }

    public function isConditionNoteNoneMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_NONE;
    }

    public function isConditionNoteValueMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_CUSTOM_VALUE;
    }

    public function isConditionNoteAttributeMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_CUSTOM_ATTRIBUTE;
    }

    public function getConditionNoteSource()
    {
        return array(
            'mode'      => $this->getData('condition_note_mode'),
            'value'     => $this->getData('condition_note_value'),
            'attribute' => $this->getData('condition_note_custom_attribute')
        );
    }

    //----------------------------------------

    public function getHandlingTime()
    {
        return (int)$this->getData('handling_time');
    }

    // ########################################

    public function getUsedAttributes()
    {
        return array();
    }

    // ########################################
}
