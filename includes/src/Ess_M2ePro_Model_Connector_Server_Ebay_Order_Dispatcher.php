<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher extends Mage_Core_Model_Abstract
{
    const ACTION_PAY        = 1;
    const ACTION_SHIP       = 2;
    const ACTION_SHIP_TRACK = 3;

    // ########################################

    public function process($action, $orders, array $params = array())
    {
        $orders = $this->prepareOrders($orders);

        switch ($action) {
            case self::ACTION_PAY:
                $result = $this->processOrders(
                    $orders, $action, 'Ess_M2ePro_Model_Connector_Server_Ebay_Order_Update_Payment', $params
                );
                break;

            case self::ACTION_SHIP:
            case self::ACTION_SHIP_TRACK:
                $result = $this->processOrders(
                    $orders, $action, 'Ess_M2ePro_Model_Connector_Server_Ebay_Order_Update_Shipping', $params
                );
                break;

            default;
                $result = false;
                break;
        }

        return $result;
    }

    // ########################################

    protected function processOrders(array $orders, $action, $connectorNameSingle, array $params = array())
    {
        if (count($orders) == 0) {
            return false;
        }

        foreach ($orders as $order) {

            try {
                $connector = new $connectorNameSingle($params, $order, $action);
                if (!$connector->process()) {
                    return false;
                }
            } catch (Exception $e) {
                $log = $order->getParentObject()->makeLog(
                    Ess_M2ePro_Model_Connector_Server_Ebay_Order_Abstract::LOG_STATUS_NOT_UPDATED,
                    array('msg' => $e->getMessage())
                );
                $order->getParentObject()->addErrorLog($log);

                return false;
            }

        }

        return true;
    }

    // ########################################

    protected function prepareOrders($orders)
    {
        !is_array($orders) && $orders = array($orders);
        $preparedOrders = array();

        foreach ($orders as $order) {
            if ($order instanceof Ess_M2ePro_Model_Ebay_Order) {
                $preparedOrders[] = $order;
            } else if (is_numeric($order)) {
                $preparedOrders[] = Mage::getModel('M2ePro/Ebay_Order')->load($order);
            }
        }

        return $preparedOrders;
    }

    // ########################################
}
