<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Ebay_Order_Item_Builder extends Mage_Core_Model_Abstract
{
    // Parser hack -> Mage::helper('M2ePro')->__('Combined order with ID "%id%" was created for this transaction.');
    const LOG_COMBINED_ORDER_CREATED = 'Combined order with ID "%id%" was created for this transaction.';

    // ########################################

    public function initialize(array $data)
    {
        // Init general data
        // ------------------
        $this->setData('order_id', $data['order_id']);
        $this->setData('listing_type', $data['listing_type']);
        $this->setData('transaction_id', $data['transaction_id']);
        $this->setData('item_id', $data['item_id']);
        $this->setData('title', $data['item_title']);
        $this->setData('sku', $data['item_sku']);
        $this->setData('condition_display_name', $data['item_condition_display_name']);
        // ------------------

        // Init sale data
        // ------------------
        $this->setData('price', (float)$data['price']);
        $this->setData('buy_it_now_price', (float)$data['buy_it_now_price']);
        $this->setData('currency', $data['currency']);
        $this->setData('qty_purchased', (int)$data['qty_purchased']);
        $this->setData('auto_pay', (int)$data['auto_pay']);
        $this->setData('variation', json_encode($data['variation']));
        // ------------------
    }

    // ########################################

    public function process(Ess_M2ePro_Model_Order $order, $isNew = false, $isCombined = false)
    {
        $isCombined && $isNew && $this->processNewCombinedItem($order);

        return $this->createOrderItem();
    }

    protected function processNewCombinedItem(Ess_M2ePro_Model_Order $newOrder)
    {
        $relatedOrdersCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Order')
            ->addFieldToFilter('main_table.account_id', $newOrder->getAccountId())
            ->addFieldToFilter('main_table.id', array('neq' => $newOrder->getId()));

        $relatedOrdersCollection->getSelect()
            ->distinct(true)
            ->join(
                array('oi'=>Mage::getResourceModel('M2ePro/Order_Item')->getMainTable()),
                '`oi`.`order_id` = `main_table`.`id`',
                array()
            )
            ->join(
                array('eoi'=>Mage::getResourceModel('M2ePro/Ebay_Order_Item')->getMainTable()),
                '`eoi`.`order_item_id` = `oi`.`id`',
                array()
            )
            ->where('`eoi`.`item_id` = ?', $this->getData('item_id'))
            ->where('`eoi`.`transaction_id` = ?', $this->getData('transaction_id'));

        foreach ($relatedOrdersCollection->getItems() as $relatedOrder) {
            /** @var $relatedOrder Ess_M2ePro_Model_Order */
            if (is_null($relatedOrder->getMagentoOrderId())) {
                $relatedOrder->deleteInstance();
            } else {
                $log = $relatedOrder->makeLog(self::LOG_COMBINED_ORDER_CREATED, array(
                    '!id' => $newOrder->getData('ebay_order_id'))
                );
                $relatedOrder->addWarningLog($log);
            }
        }
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Order_Item
     */
    protected function createOrderItem()
    {
        $item = Mage::helper('M2ePro/Component_Ebay')->getCollection('Order_Item')
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('item_id', $this->getData('item_id'))
            ->addFieldToFilter('transaction_id', $this->getData('transaction_id'))
            ->getFirstItem();

        $item->addData($this->getData());
        $item->save();

        return $item;
    }

    // ########################################
}
