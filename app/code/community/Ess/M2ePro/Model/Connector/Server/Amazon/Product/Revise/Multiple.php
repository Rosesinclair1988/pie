<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Amazon_Product_Revise_Multiple
extends Ess_M2ePro_Model_Connector_Server_Amazon_Product_Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','update','entities');
    }

    // ########################################

    protected function getActionIdentifier()
    {
        return 'revise';
    }

    protected function getResponserModel()
    {
        return 'Amazon_Product_Revise_MultipleResponser';
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function prepareListingsProducts($listingsProducts)
    {
        $tempListingsProducts = array();

        foreach ($listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            if (!$listingProduct->isListed()) {

                // Parser hack -> Mage::helper('M2ePro')->__('Item is not listed or not available');
                $this->addListingsProductsLogsMessage($listingProduct, 'Item is not listed or not available',
                                                      Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                continue;
            }

            if ($listingProduct->isLockedObject(NULL) ||
                $listingProduct->isLockedObject('in_action') ||
                $listingProduct->isLockedObject($this->getActionIdentifier().'_action')) {

                // ->__('Another action is being processed. Try again when the action is completed.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct, 'Another action is being processed. Try again when the action is completed.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                continue;
            }

            $tempListingsProducts[] = $listingProduct;
        }

        return $tempListingsProducts;
    }

    // ########################################

    protected function getRequestData()
    {
        $output = array(
            'items' => array()
        );

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            $requestData = Mage::getModel('M2ePro/Amazon_Connector_Product_Helper')
                                         ->getReviseRequestData($listingProduct,$this->params);

            $listing = array(
                'id' => $listingProduct->getId(),
                'sku' => $requestData['sku'],
                'price' => $requestData['price'],
                'currency' => $requestData['currency'],
                'qty' => $requestData['qty'],
                'handling_time' => $requestData['handling_time']
            );

            if ($requestData['sale_price'] > 0) {
                $listing['sale_price'] = $requestData['sale_price'];
                $listing['sale_start_date'] = Mage::helper('M2ePro')->getDate(
                    $requestData['sale_price_start_date'], false
                );
                $listing['sale_end_date'] = Mage::helper('M2ePro')->getDate(
                    $requestData['sale_price_end_date'], false
                );
            }

            $this->listingProductRequestsData[$listingProduct->getId()] = array(
                'native_data' => $requestData,
                'sended_data' => $listing
            );

            $output['items'][] = $listing;
        }

        return $output;
    }

    // ########################################
}
