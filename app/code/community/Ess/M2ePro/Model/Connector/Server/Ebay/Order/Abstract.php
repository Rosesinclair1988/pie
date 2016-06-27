<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Connector_Server_Ebay_Order_Abstract
extends Ess_M2ePro_Model_Connector_Server_Ebay_Abstract
{
    // Parser hack -> Mage::helper('M2ePro')->__('eBay Order status was not updated. Reason: %msg%');
    const LOG_STATUS_NOT_UPDATED = 'eBay Order status was not updated. Reason: %msg%';

    // ########################################

    /**
     * @var $order Ess_M2ePro_Model_Ebay_Order
     */
    protected $order = NULL;
    protected $action = NULL;

    // ########################################

    public function __construct(array $params = array(), Ess_M2ePro_Model_Ebay_Order $order, $action = NULL)
    {
        $this->order = $order;
        $this->action = $action;

        parent::__construct($params, NULL, $order->getParentObject()->getAccount());
    }

    // ########################################

    public function process()
    {
        if (!$this->validateNeedRequestSend()) {
            return false;
        }

        $result = parent::process();

        foreach ($this->messages as $message) {
            if ($message[parent::MESSAGE_TYPE_KEY] != parent::MESSAGE_TYPE_ERROR) {
                continue;
            }

            $log = $this->order->getParentObject()->makeLog(self::LOG_STATUS_NOT_UPDATED, array(
                'msg' => $message[parent::MESSAGE_TEXT_KEY])
            );
            $this->order->getParentObject()->addErrorLog($log);
        }

        return $result;
    }

    //----------------------------------------

    protected function validateNeedRequestSend()
    {
        if (!in_array($this->action,array(
            Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_PAY,
            Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP,
            Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP_TRACK
        ))) {
            throw new LogicException('Invalid action.');
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array();
        $requestData['account'] = $this->order->getParentObject()->getAccount()->getServerHash();
        $requestData['action'] = $this->action;

        $ebayOrderId = $this->order->getData('ebay_order_id');

        if (strpos($ebayOrderId, '-') === false) {
            $requestData['order_id'] = $ebayOrderId;
        } else {
            $orderIdParts = explode('-', $ebayOrderId);

            $requestData['item_id'] = $orderIdParts[0];
            $requestData['transaction_id'] = $orderIdParts[1];
        }

        return $requestData;
    }

    // ########################################
}
