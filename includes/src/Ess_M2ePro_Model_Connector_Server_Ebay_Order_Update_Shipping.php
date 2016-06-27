<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Order_Update_Shipping
extends Ess_M2ePro_Model_Connector_Server_Ebay_Order_Abstract
{
    // ->__('Shipping status for eBay order was not updated. Reason: eBay Failure.');
    // ->__('Tracking number "%num%" for "%code%" has been sent to eBay.');
    // ->__('Shipping status for eBay order was updated to Shipped.');
    const LOG_EBAY_FAILURE     = 'Shipping status for eBay order was not updated. Reason: eBay Failure.';
    const LOG_TRACK_SUBMITTED  = 'Tracking number "%num%" for "%code%" has been sent to eBay.';
    const LOG_SHIPPING_UPDATED = 'Shipping status for eBay order was updated to Shipped.';

    // ########################################

    private $carrierCode = NULL;
    private $trackingNumber = NULL;

    // ########################################

    public function __construct(array $params = array(), Ess_M2ePro_Model_Ebay_Order $order, $action)
    {
        parent::__construct($params, $order, $action);

        if ($this->action == Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP_TRACK) {
            $this->carrierCode = $params['carrier_code'];
            $this->trackingNumber = $params['tracking_number'];
        }
    }

    // ########################################

    protected function getCommand()
    {
        return array('sales', 'update', 'status');
    }

    // ########################################

    protected function getRequestData()
    {
        $requestData = parent::getRequestData();

        if ($this->action == Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP_TRACK) {
            $requestData['carrier_code'] = $this->carrierCode;
            $requestData['tracking_number'] = $this->trackingNumber;
        }

        return $requestData;
    }

    // ########################################

    protected function validateResponseData($response)
    {
        return true;
    }

    protected function prepareResponseData($response)
    {
        if ($this->resultType != parent::MESSAGE_TYPE_ERROR) {

            if (!isset($response['result']) || !$response['result']) {
                $this->order->getParentObject()->addErrorLog(self::LOG_EBAY_FAILURE);

                return false;
            }

            if ($this->action == Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP_TRACK) {
                $trackingDetails = $this->order->getShippingTrackingDetails();
                $trackingDetails[] = array(
                    'title'  => $this->carrierCode,
                    'number' => $this->trackingNumber
                );
                $this->order->setData('shipping_tracking_details', json_encode($trackingDetails))->save();

                $log = Mage::getSingleton('M2ePro/Log_Abstract')->encodeDescription(self::LOG_TRACK_SUBMITTED, array(
                    '!num'  => $this->trackingNumber,
                    '!code' => $this->carrierCode
                ));
                $this->order->getParentObject()->addSuccessLog($log);
            }

            if (!$this->order->isShippingCompleted()) {
                $this->order->setData('shipping_status',Ess_M2ePro_Model_Ebay_Order::SHIPPING_STATUS_COMPLETED)->save();
                $this->order->getParentObject()->addSuccessLog(self::LOG_SHIPPING_UPDATED);
            }

        }

        return $response;
    }

    // ########################################
}
