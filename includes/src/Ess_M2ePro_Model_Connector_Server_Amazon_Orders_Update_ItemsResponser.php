<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class   Ess_M2ePro_Model_Connector_Server_Amazon_Orders_Update_ItemsResponser
extends Ess_M2ePro_Model_Connector_Server_Amazon_Responser
{
    // Parser hack -> Mage::helper('M2ePro')->__('Amazon Order status was not updated. Reason: %msg%');
    // Parser hack -> Mage::helper('M2ePro')->__('Amazon Order status was updated to Shipped.');
    // Parser hack -> Mage::helper('M2ePro')->__('Tracking number "%num%" for "%code%" has been sent to Amazon.');

    const LOG_SHIPPING_STATUS_NOT_UPDATED = 'Amazon Order status was not updated. Reason: %msg%';
    const LOG_SHIPPING_STATUS_UPDATED     = 'Amazon Order status was updated to Shipped.';
    const LOG_TRACK_SUBMITTED             = 'Tracking number "%num%" for "%code%" has been sent to Amazon.';

    // ########################################

    protected function unsetLocks($fail = false, $message = NULL)
    {
        $this->getOrder()->deleteObjectLocks('update_shipping_status', $this->hash);
    }

    // ########################################

    protected function validateResponseData($response)
    {
        return true;
    }

    protected function processResponseData($response)
    {
        $hasError = false;
        $messages = !empty($response['messages']) ? $response['messages'] : array();

        foreach ($messages as $message) {
            $isError = ($message[Ess_M2ePro_Model_Connector_Server_Protocol::MESSAGE_TYPE_KEY] ==
                        Ess_M2ePro_Model_Connector_Server_Protocol::MESSAGE_TYPE_ERROR);

            if ($isError) {
                $logMessage = $this->getOrder()->makeLog(self::LOG_SHIPPING_STATUS_NOT_UPDATED, array(
                    'msg' => $message[Ess_M2ePro_Model_Connector_Server_Protocol::MESSAGE_TEXT_KEY]
                ));
                $this->getOrder()->addErrorLog($logMessage);
                $hasError = true;
            }
        }

        if ($hasError) {
            return false;
        }

        $this->getOrder()->setData('status', Ess_M2ePro_Model_Amazon_Order::STATUS_SHIPPED)->save();
        $this->getOrder()->addSuccessLog(self::LOG_SHIPPING_STATUS_UPDATED);

        if (!empty($this->params['tracking_details'])) {
            $log = $this->getOrder()->makeLog(self::LOG_TRACK_SUBMITTED, array(
                '!num' => $this->params['tracking_details']['tracking_number'],
                'code' => $this->params['tracking_details']['carrier_name']
            ));
            $this->getOrder()->addSuccessLog($log);
        }

        return array();
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Order
     */
    private function getOrder()
    {
        return $this->getObjectByParam('Order', 'order_id');
    }

    // ########################################
}
