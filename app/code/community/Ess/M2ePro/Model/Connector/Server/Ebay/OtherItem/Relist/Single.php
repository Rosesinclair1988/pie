<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_OtherItem_Relist_Single
extends Ess_M2ePro_Model_Connector_Server_Ebay_OtherItem_Abstract
{
    // ########################################

    protected function getCommand()
    {
        return array('item','update','relist');
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Other_Log::ACTION_RELIST_PRODUCT;
    }

    // ########################################

    protected function validateNeedRequestSend()
    {
        if (!$this->otherListing->isRelistable()) {

            $message = array(
                // ->__('The item either is listed, or not listed yet or not available');
                parent::MESSAGE_TEXT_KEY => 'The item either is listed, or not listed yet or not available',
                parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
            );

            $this->addProductsLogsMessage($this->otherListing,$message,
                                          Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

            return false;
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array();
        $requestData['item_id'] = $this->otherListing->getChildObject()->getItemId();
        $requestData['title'] = $this->otherListing->getChildObject()->getTitle();
        $requestData['qty'] = $this->otherListing->getChildObject()->getOnlineQty();
        return $requestData;
    }

    //----------------------------------------

    protected function validateResponseData($response)
    {
        return true;
    }

    protected function prepareResponseData($response)
    {
        if ($this->resultType != parent::MESSAGE_TYPE_ERROR) {

            if ($response['already_active']) {

                $dataForUpdate = array(
                    'status' => Ess_M2ePro_Model_Listing_Product::STATUS_LISTED,
                    'start_date' => Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::ebayTimeToString(
                        $response['start_date_raw']
                    ),
                    'end_date' => Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::ebayTimeToString(
                        $response['end_date_raw']
                    )
                );

                $this->otherListing->addData($dataForUpdate)->save();

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Item already was started on eBay');
                    parent::MESSAGE_TEXT_KEY => 'Item already was started on eBay',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_ERROR
                );

                $this->addProductsLogsMessage($this->otherListing,$message,
                                              Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

            } else {

                $newEbayOldItems = $this->otherListing->getData('old_items');
                is_null($newEbayOldItems) && $newEbayOldItems = '';
                $newEbayOldItems != '' && $newEbayOldItems .= ',';
                $newEbayOldItems .= $this->otherListing->getData('item_id');

                $dataForUpdate = array(
                    'item_id' => $response['ebay_item_id'],
                    'old_items' => $newEbayOldItems,
                    'online_qty_sold' => 0,
                    'online_bids' => 0,
                    'start_date' => Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::ebayTimeToString(
                        $response['start_date_raw']
                    ),
                    'end_date' => Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::ebayTimeToString(
                        $response['end_date_raw']
                    ),
                    'status' => Ess_M2ePro_Model_Listing_Product::STATUS_LISTED
                );

                $this->otherListing->addData($dataForUpdate)->save();

                $message = array(
                    // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully relisted');
                    parent::MESSAGE_TEXT_KEY => 'Item was successfully relisted',
                    parent::MESSAGE_TYPE_KEY => parent::MESSAGE_TYPE_SUCCESS
                );

                $this->addProductsLogsMessage($this->otherListing,$message,
                                              Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

            }
        }

        return $response;
    }

    // ########################################
}
