<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Order_Log extends Mage_Core_Model_Abstract
{
    const TYPE_SUCCESS = 0;
    const TYPE_NOTICE  = 1;
    const TYPE_ERROR   = 2;
    const TYPE_WARNING = 3;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Order_Log');
    }

    // ########################################

    public function addSuccess($componentMode, $orderId, $message)
    {
        $this->addNew($componentMode, $orderId, $message, self::TYPE_SUCCESS);
    }

    public function addNotice($componentMode, $orderId, $message)
    {
        $this->addNew($componentMode, $orderId, $message, self::TYPE_NOTICE);
    }

    public function addWarning($componentMode, $orderId, $message)
    {
        $this->addNew($componentMode, $orderId, $message, self::TYPE_WARNING);
    }

    public function addError($componentMode, $orderId, $message)
    {
        $this->addNew($componentMode, $orderId, $message, self::TYPE_ERROR);
    }

    private function addNew($componentMode, $orderId, $message, $type)
    {
        $log = array(
            'component_mode' => $componentMode,
            'order_id'       => (int)$orderId,
            'message'        => $message,
            'type'           => (int)$type,
        );

        $this->setId(null)
             ->setData($log)
             ->save();
    }

    // ########################################

    public function getLastByOrderId($orderId)
    {
        $collection = Mage::getModel('M2ePro/Order_Log')->getCollection()
            ->addFieldToFilter('order_id', (int)$orderId)
            ->setOrder('id', 'DESC');
        $collection->getSelect()->limit(1);

        return $collection->getFirstItem();
    }

    // ########################################

    public function deleteInstance()
    {
        return parent::delete();
    }

    // ########################################
}
