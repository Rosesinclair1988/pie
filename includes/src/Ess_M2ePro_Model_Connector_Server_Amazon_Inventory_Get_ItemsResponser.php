<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class   Ess_M2ePro_Model_Connector_Server_Amazon_Inventory_Get_ItemsResponser
extends Ess_M2ePro_Model_Connector_Server_Amazon_Responser
{
    // ########################################

    protected function unsetLocks($fail = false, $message = NULL) {}

    // ########################################

    protected function validateResponseData($response)
    {
        if (!isset($response['data'])) {
            return false;
        }

        return true;
    }

    protected function processResponseData($response)
    {
        $items = array();

        foreach ($response['data'] as $listing) {

            $item = array();

            $item['title'] = $listing['title'];
            $item['description'] = $listing['description'] ;
            $item['notice'] = $listing['notice'];

            $item['price'] = $listing['price'];
            $item['qty'] = $listing['qty'];
            $item['start_date'] = $listing['start_date'];

            $item['identifiers'] = array();
            $item['identifiers']['sku'] = $listing['identifiers']['sku'];
            $item['identifiers']['item_id'] = $listing['identifiers']['item_id'];
            $item['identifiers']['product_id'] = $listing['identifiers']['general_id'];

            $item['identifiers']['is_asin'] = $listing['identifiers']['is_asin'];
            $item['identifiers']['is_isbn'] = $listing['identifiers']['is_isbn'];

            $item['channel']['is_mfn'] = $listing['channel']['is_mfn'];
            $item['channel']['is_afn'] = $listing['channel']['is_afn'];

            $items[] = $item;
        }

        return $items;
    }

    // ########################################
}
