<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Listing_Other_Log extends Ess_M2ePro_Model_Log_Abstract
{
    const ACTION_UNKNOWN = 1;
    const _ACTION_UNKNOWN = 'System';

    const ACTION_RELIST_PRODUCT = 2;
    const _ACTION_RELIST_PRODUCT = 'Relist product';
    const ACTION_STOP_PRODUCT = 3;
    const _ACTION_STOP_PRODUCT = 'Stop product';

    const ACTION_ADD_LISTING = 4;
    const _ACTION_ADD_LISTING = 'Add new listing';
    const ACTION_DELETE_LISTING = 5;
    const _ACTION_DELETE_LISTING = 'Delete existing listing';

    const ACTION_MAP_LISTING = 6;
    const _ACTION_MAP_LISTING = 'Map listing to magento product';

    const ACTION_UNMAP_LISTING = 8;
    const _ACTION_UNMAP_LISTING = 'Unmap listing from magento product';

    const ACTION_MOVE_LISTING = 7;
    const _ACTION_MOVE_LISTING = 'Move to existing M2E listing';

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Listing_Other_Log');
    }

    //####################################

    public function addGlobalMessage($initiator = parent::INITIATOR_UNKNOWN,
                                     $actionId = NULL,
                                     $action = NULL,
                                     $description = NULL,
                                     $type = NULL,
                                     $priority = NULL)
    {
        $dataForAdd = $this->makeDataForAdd(  NULL ,
                                              $this->makeCreator() ,
                                              $initiator,
                                              $actionId ,
                                              $action ,
                                              $description ,
                                              $type ,
                                              $priority );

        $this->createMessage($dataForAdd);
    }

    public function addProductMessage($listingOtherId,
                                      $initiator = parent::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL)
    {
        $dataForAdd = $this->makeDataForAdd(  $listingOtherId ,
                                              $this->makeCreator() ,
                                              $initiator,
                                              $actionId ,
                                              $action ,
                                              $description ,
                                              $type ,
                                              $priority );

        $this->createMessage($dataForAdd);
    }

    //####################################

    public function getActionTitle($type)
    {
        return $this->getActionTitleByClass(__CLASS__,$type);
    }

    public function getActionsTitles()
    {
        return $this->getActionsTitlesByClass(__CLASS__,'ACTION_');
    }

    public function clearMessages($listingOtherId = NULL)
    {
        $columnName = !is_null($listingOtherId) ? 'listing_other_id' : NULL;
        $this->clearMessagesByTable('M2ePro/Listing_Other_Log',$columnName,$listingOtherId);
    }

    //####################################

    private function createMessage($dataForAdd)
    {
        if (!is_null($dataForAdd['listing_other_id'])) {
            $listingOther = Mage::helper('M2ePro/Component')->getComponentObject(
                $this->componentMode,'Listing_Other',$dataForAdd['listing_other_id']
            );
            !is_null($listingOther) && $dataForAdd['title'] = $listingOther->getChildObject()->getTitle();
        }

        $dataForAdd['component_mode'] = $this->componentMode;

        Mage::getModel('M2ePro/Listing_Other_Log')
                 ->setData($dataForAdd)
                 ->save()
                 ->getId();
    }

    private function makeDataForAdd($listingOtherId,
                                    $creator,
                                    $initiator = parent::INITIATOR_UNKNOWN,
                                    $actionId = NULL,
                                    $action = NULL,
                                    $description = NULL,
                                    $type = NULL,
                                    $priority = NULL)
    {
        $dataForAdd = array();

        if (!is_null($listingOtherId)) {
            $dataForAdd['listing_other_id'] = (int)$listingOtherId;
        } else {
            $dataForAdd['listing_other_id'] = NULL;
        }

        $dataForAdd['creator'] = $creator;
        $dataForAdd['initiator'] = $initiator;

        if (!is_null($actionId)) {
            $dataForAdd['action_id'] = (int)$actionId;
        } else {
            $dataForAdd['action_id'] = NULL;
        }

        if (!is_null($action)) {
            $dataForAdd['action'] = (int)$action;
        } else {
            $dataForAdd['action'] = self::ACTION_UNKNOWN;
        }

        if (!is_null($description)) {
            $dataForAdd['description'] = $description;
        } else {
            $dataForAdd['description'] = NULL;
        }

        if (!is_null($type)) {
            $dataForAdd['type'] = (int)$type;
        } else {
            $dataForAdd['type'] = self::TYPE_NOTICE;
        }

        if (!is_null($priority)) {
            $dataForAdd['priority'] = (int)$priority;
        } else {
            $dataForAdd['priority'] = self::PRIORITY_LOW;
        }

        return $dataForAdd;
    }

    //####################################
}
