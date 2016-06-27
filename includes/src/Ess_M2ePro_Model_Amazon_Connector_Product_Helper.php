<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Connector_Product_Helper
{
    // ########################################

    public function getListRequestData(Ess_M2ePro_Model_Listing_Product $listingProduct, array $params = array())
    {
        $requestData = $this->getReviseRequestData($listingProduct,$params);

        if (empty($requestData['sku'])) {
            if ($listingProduct->getChildObject()->getSku()) {
                $requestData['sku'] = $listingProduct->getChildObject()->getSku();
            } else {
                $requestData['sku'] = $listingProduct->getChildObject()->getAddingSku();
            }
        }

        $productId = NULL;
        $productIdType = NULL;

        $tempGeneralId = $listingProduct->getChildObject()->getGeneralId();
        empty($tempGeneralId) && $tempGeneralId = $listingProduct->getChildObject()->getAddingGeneralId();

        if (!empty($tempGeneralId)) {

            $productId = $tempGeneralId;
            $productIdType = Mage::helper('M2ePro/Component_Amazon')->isASIN($productId) ?
                           'ASIN' : 'ISBN';
        } else {

            $tempWorldwideId = $listingProduct->getChildObject()->getWorldwideId();
            empty($tempWorldwideId) && $tempWorldwideId = $listingProduct->getChildObject()->getAddingWorldwideId();

            $productId = $tempWorldwideId;
            $productIdType = Mage::helper('M2ePro/Component_Amazon')->isUPC($productId) ?
                           'UPC' : 'EAN';
        }

        $requestData['product_id'] = $productId;
        $requestData['product_id_type'] = $productIdType;

        $requestData['condition'] = $listingProduct->getChildObject()->getAddingCondition();
        $requestData['condition_note'] = $listingProduct->getChildObject()->getAddingConditionNote();

        return $requestData;
    }

    public function updateAfterListAction(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                          array $nativeRequestData = array(),
                                          array $params = array())
    {
        // Update Listing Product
        //---------------------
        $dataForUpdate = array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_LISTED,
            'status_changer' => $params['status_changer'],
            'sku' => $nativeRequestData['sku'],
            'is_afn_channel' => Ess_M2ePro_Model_Amazon_Listing_Product::IS_AFN_CHANNEL_NO,
            'start_date' => Mage::helper('M2ePro')->getCurrentGmtDate(),
            'end_date' => NULL
        );

        if (in_array($nativeRequestData['product_id_type'],array('ASIN','ISBN'))) {

            $dataForUpdate['general_id'] = $nativeRequestData['product_id'];

            $isIsbnGeneralId = (int)Mage::helper('M2ePro/Component_Amazon')->isISBN($nativeRequestData['product_id']);
            $dataForUpdate['is_isbn_general_id'] = $isIsbnGeneralId;

        } else if (in_array($nativeRequestData['product_id_type'],array('UPC','EAN'))) {

            $dataForUpdate['worldwide_id'] = $nativeRequestData['product_id'];

            $isUpcWorldwideId = (int)Mage::helper('M2ePro/Component_Amazon')->isUPC($nativeRequestData['product_id']);
            $dataForUpdate['is_upc_worldwide_id'] = $isUpcWorldwideId;
        }

        if (isset($nativeRequestData['qty'])) {

            $dataForUpdate['online_qty'] = (int)$nativeRequestData['qty'];

            if ((int)$dataForUpdate['online_qty'] > 0) {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
                $dataForUpdate['end_date'] = NULL;
            } else {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
                $dataForUpdate['end_date'] = Mage::helper('M2ePro')->getCurrentGmtDate();
            }
        }

        if (isset($nativeRequestData['price'])) {
            $dataForUpdate['online_price'] = (float)$nativeRequestData['price'];
        }

        $listingProduct->addData($dataForUpdate)->save();
        //---------------------

        // Add Amazon Item record
        //---------------------
        $dataForAdd = array(
            'account_id' => $listingProduct->getListing()->getGeneralTemplate()->getAccountId(),
            'marketplace_id' => $listingProduct->getListing()->getGeneralTemplate()->getMarketplaceId(),
            'sku' => $listingProduct->getChildObject()->getSku(),
            'product_id' => $listingProduct->getProductId(),
            'store_id' => $listingProduct->getListing()->getStoreId()
        );

        Mage::getModel('M2ePro/Amazon_Item')->setData($dataForAdd)->save();
        //---------------------

        // Update Variations
        //---------------------
        Mage::getModel('M2ePro/Amazon_Connector_Product_HelperVariations')
                   ->updateAfterAction($listingProduct);
        //---------------------
    }

    //----------------------------------------

    public function getRelistRequestData(Ess_M2ePro_Model_Listing_Product $listingProduct, array $params = array())
    {
        return $this->getReviseRequestData($listingProduct,$params);
    }

    public function updateAfterRelistAction(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                            array $nativeRequestData = array(),
                                            array $params = array())
    {
        // Update Listing Product
        //---------------------
        $dataForUpdate = array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_LISTED,
            'status_changer' => $params['status_changer'],
            'is_afn_channel' => Ess_M2ePro_Model_Amazon_Listing_Product::IS_AFN_CHANNEL_NO
        );

        if (isset($nativeRequestData['qty'])) {

            $dataForUpdate['online_qty'] = (int)$nativeRequestData['qty'];

            if ((int)$dataForUpdate['online_qty'] > 0) {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
                $dataForUpdate['end_date'] = NULL;
            } else {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
                $dataForUpdate['end_date'] = Mage::helper('M2ePro')->getCurrentGmtDate();
            }
        }

        if (isset($nativeRequestData['price'])) {
            $dataForUpdate['online_price'] = (float)$nativeRequestData['price'];
        }

        $listingProduct->addData($dataForUpdate)->save();
        //---------------------

        // Update Variations
        //---------------------
        Mage::getModel('M2ePro/Amazon_Connector_Product_HelperVariations')
                   ->updateAfterAction($listingProduct);
        //---------------------
    }

    //----------------------------------------

    public function getReviseRequestData(Ess_M2ePro_Model_Listing_Product $listingProduct, array $params = array())
    {
        // Set permissions
        //-----------------
        $permissions = array(
            'general'=>true,
            'variations'=>true,
            'qty'=>true,
            'price'=>true
        );

        if (isset($params['only_data'])) {
            foreach ($permissions as &$value) {
                $value = false;
            }
            $permissions = array_merge($permissions,$params['only_data']);
        }

        if (isset($params['all_data'])) {
            foreach ($permissions as &$value) {
                $value = true;
            }
        }
        //-----------------

        $requestData = array();

        // Prepare Variations
        //-------------------
        Mage::getModel('M2ePro/Amazon_Listing_Product_Variation_Updater')->updateVariations($listingProduct);
        $tempVariations = Mage::getModel('M2ePro/Amazon_Connector_Product_HelperVariations')
                                                ->getRequestData($listingProduct);

        $requestData['is_variation_item'] = false;
        if (is_array($tempVariations) && count($tempVariations) > 0) {
            $requestData['is_variation_item'] = true;
        }
        //-------------------

        // Get Variations
        //-------------------
        if ($permissions['variations'] && $requestData['is_variation_item']) {
            $requestData['variation'] = $tempVariations;
        }
        //-------------------

        // Get Main Data
        //-------------------
        $requestData['sku'] = $listingProduct->getChildObject()->getSku();
        $requestData['handling_time'] = $listingProduct->getGeneralTemplate()->getChildObject()->getHandlingTime();

        if ($permissions['qty'] && !$requestData['is_variation_item']) {
            $requestData['qty'] = $listingProduct->getChildObject()->getQty();
        }

        if ($permissions['price'] && !$requestData['is_variation_item']) {

            $requestData['price'] = $listingProduct->getChildObject()->getPrice();
            $requestData['sale_price'] = $listingProduct->getChildObject()->getSalePrice();
            $requestData['currency'] = $listingProduct->getSellingFormatTemplate()->getChildObject()->getCurrency();

            if ($requestData['sale_price'] > 0) {
                if ($listingProduct->getSellingFormatTemplate()->getChildObject()->isSalePriceModeSpecial()) {
                    $requestData['sale_price_start_date'] = $listingProduct->getMagentoProduct()
                                                                           ->getSpecialPriceFromDate();
                    $requestData['sale_price_end_date'] = $listingProduct->getMagentoProduct()
                                                                         ->getSpecialPriceToDate();
                } else {
                    $requestData['sale_price_start_date'] = $listingProduct->getSellingFormatTemplate()
                                                                           ->getChildObject()
                                                                           ->getSalePriceStartDate();
                    $requestData['sale_price_end_date'] = $listingProduct->getSellingFormatTemplate()
                                                                         ->getChildObject()
                                                                         ->getSalePriceEndDate();
                }
            }
        }
        //-------------------

        return $requestData;
    }

    public function updateAfterReviseAction(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                            array $nativeRequestData = array(),
                                            array $params = array())
    {
        // Update Listing Product
        //---------------------
        $dataForUpdate = array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_LISTED,
            'status_changer' => $params['status_changer'],
            'is_afn_channel' => Ess_M2ePro_Model_Amazon_Listing_Product::IS_AFN_CHANNEL_NO
        );

        if (isset($nativeRequestData['qty'])) {

            $dataForUpdate['online_qty'] = (int)$nativeRequestData['qty'];

            if ((int)$dataForUpdate['online_qty'] > 0) {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
                $dataForUpdate['end_date'] = NULL;
            } else {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
                $dataForUpdate['end_date'] = Mage::helper('M2ePro')->getCurrentGmtDate();
            }
        }

        if (isset($nativeRequestData['price'])) {
            $dataForUpdate['online_price'] = (float)$nativeRequestData['price'];
        }

        $listingProduct->addData($dataForUpdate)->save();
        //---------------------

        // Update Variations
        //---------------------
        Mage::getModel('M2ePro/Amazon_Connector_Product_HelperVariations')
                   ->updateAfterAction($listingProduct);
        //---------------------
    }

    //----------------------------------------

    public function getStopRequestData(Ess_M2ePro_Model_Listing_Product $listingProduct, array $params = array())
    {
        $requestData = array();

        // Get Amazon Item Info
        //-------------------
        $requestData['sku'] = $listingProduct->getChildObject()->getSku();
        $requestData['qty'] = 0;
        //-------------------

        return $requestData;
    }

    public function updateAfterStopAction(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                          array $nativeRequestData = array(),
                                          array $params = array())
    {
        // Update Listing Product
        //---------------------
        $dataForUpdate = array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED,
            'status_changer' => $params['status_changer'],
            'is_afn_channel' => Ess_M2ePro_Model_Amazon_Listing_Product::IS_AFN_CHANNEL_NO
        );

        if (isset($nativeRequestData['qty'])) {

            $dataForUpdate['online_qty'] = (int)$nativeRequestData['qty'];

            if ((int)$dataForUpdate['online_qty'] > 0) {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
                $dataForUpdate['end_date'] = NULL;
            } else {
                $dataForUpdate['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
                $dataForUpdate['end_date'] = Mage::helper('M2ePro')->getCurrentGmtDate();
            }
        }

        if (isset($nativeRequestData['price'])) {
            $dataForUpdate['online_price'] = (float)$nativeRequestData['price'];
        }

        $listingProduct->addData($dataForUpdate)->save();
        //---------------------

        // Update Variations
        //---------------------
        Mage::getModel('M2ePro/Amazon_Connector_Product_HelperVariations')
                   ->updateAfterAction($listingProduct);
        //---------------------
    }

    // ########################################
}
