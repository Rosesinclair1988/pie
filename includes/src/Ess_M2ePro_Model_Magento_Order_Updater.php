<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Magento_Order_Updater
{
    // ->__('Cancel is not allowed for Orders which were already Canceled.')
    // ->__('Cancel is not allowed for Orders with Invoiced Items.')
    // ->__('Cancel is not allowed for Orders which were put on Hold.')
    // ->__('Cancel is not allowed for Orders which were Completed or Closed.')
    // ->__('Unknown.')
    const CANCEL_FAILED_REASON_CANCELED = 'Cancel is not allowed for Orders which were already Canceled.';
    const CANCEL_FAILED_REASON_INVOICED = 'Cancel is not allowed for Orders with Invoiced Items.';
    const CANCEL_FAILED_REASON_HOLD     = 'Cancel is not allowed for Orders which were put on Hold.';
    const CANCEL_FAILED_REASON_STATE    = 'Cancel is not allowed for Orders which were Completed or Closed.';
    const CANCEL_FAILED_REASON_UNKNOWN  = 'Unknown.';

    // ########################################

    /** @var $magentoOrder Mage_Sales_Model_Order */
    private $magentoOrder = NULL;

    private $needSave = false;

    // ########################################

    /**
     * Set magento order for updating
     *
     * @param Mage_Sales_Model_Order $order
     * @return Ess_M2ePro_Model_Magento_Order_Updater
     */
    public function setMagentoOrder(Mage_Sales_Model_Order $order)
    {
        $this->magentoOrder = $order;

        return $this;
    }

    // ########################################

    /**
     * Update shipping and billing addresses
     *
     * @param array $addressInfo
     */
    public function updateShippingAddress(array $addressInfo)
    {
        $billingAddress = $this->magentoOrder->getBillingAddress();
        if ($billingAddress instanceof Mage_Sales_Model_Order_Address) {
            $billingAddress->addData($addressInfo);
            $billingAddress->implodeStreetAddress()->save();
        }

        $shippingAddress = $this->magentoOrder->getShippingAddress();
        if ($shippingAddress instanceof Mage_Sales_Model_Order_Address) {
            $shippingAddress->addData($addressInfo);
            $shippingAddress->implodeStreetAddress()->save();
        }
    }

    // ########################################

    /**
     * Update customer address data
     *
     * @param array $customerInfo
     * @return null
     */
    public function updateCustomer(array $customerInfo)
    {
        // Update order data for guest account
        // ---------------------------
        if ($this->magentoOrder->getCustomerIsGuest()) {

            if ($this->magentoOrder->getCustomerEmail() != $customerInfo['email']) {
                $this->magentoOrder->setCustomerEmail($customerInfo['email']);
                $this->needSave = true;
            }

            return;
        }
        // ---------------------------

        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->load($this->magentoOrder->getCustomerId());
        if (!$customer->getId()) {
            return;
        }

        if ($customer->getEmail() != $customerInfo['email'] &&
            strpos($customer->getEmail(), Ess_M2ePro_Model_Magento_Customer::FAKE_EMAIL_POSTFIX) !== false) {
            $customer->setEmail($customerInfo['email'])->save();
        }

        // Find same customer address
        // ---------------------------
        foreach ($customer->getAddressesCollection() as $address) {
            /** @var $address Mage_Customer_Model_Address */
            if ($address->getData('firstname') != $customerInfo['firstname'] ||
                $address->getData('lastname') != $customerInfo['lastname'] ||
                $address->getData('city') != $customerInfo['city'] ||
                $address->getData('telephone') != $customerInfo['telephone'] ||
                $address->getData('postcode') != $customerInfo['postcode']) {
                continue;
            }

            $newStreets = array_diff($customerInfo['street'], $address->getStreet());

            if (count($newStreets) > 0) {
                $newStreets = array_merge($address->getStreet(), $newStreets);
                $address->setStreet($newStreets)->save();

                return;
            }
        }
        // ---------------------------

        /** @var $customerAddress Mage_Customer_Model_Address */
        $customerAddress = Mage::getModel('customer/address')
            ->setData($customerInfo)
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling(false)
            ->setIsDefaultShipping(false);
        $customerAddress->implodeStreetAddress();
        $customerAddress->save();
    }

    // ########################################

    /**
     * Update payment data (payment method, transactions, etc)
     *
     * @param array $newPaymentData
     */
    public function updatePaymentData(array $newPaymentData)
    {
        $payment = $this->magentoOrder->getPayment();

        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            $payment->setAdditionalData(serialize($newPaymentData))->save();
        }
    }

    // ########################################

    /**
     * Add notes
     *
     * @param mixed $comments
     * @return null
     */
    public function updateComments($comments)
    {
        if (empty($comments)) {
            return;
        }

        !is_array($comments) && $comments = array($comments);

        $header = '<br /><b><u>' . Mage::helper('M2ePro')->__('M2E Pro Notes') . ':</u></b><br /><br />';
        $comments = implode('<br /><br />', $comments);

        $this->magentoOrder->addStatusHistoryComment($header . $comments);
        $this->needSave = true;
    }

    // ########################################

    /**
     * Update status
     *
     * @param $status
     * @return null
     */
    public function updateStatus($status)
    {
        if ($status == '' || $this->magentoOrder->getStatus() == $status) {
            return;
        }

        $this->magentoOrder->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status);
        $this->needSave = true;
    }

    // ########################################

    public function canCancel()
    {
        return $this->magentoOrder->canCancel();
    }

    public function getReasonWhyCancelNotPossible()
    {
        if ($this->magentoOrder->isCanceled()) {
            return self::CANCEL_FAILED_REASON_CANCELED;
        }

        if ($this->magentoOrder->canUnhold()) {
            return self::CANCEL_FAILED_REASON_HOLD;
        }

        if ($this->magentoOrder->getState() === Mage_Sales_Model_Order::STATE_COMPLETE ||
            $this->magentoOrder->getState() === Mage_Sales_Model_Order::STATE_CLOSED) {
            return self::CANCEL_FAILED_REASON_STATE;
        }

        $allInvoiced = true;
        foreach ($this->magentoOrder->getAllItems() as $item) {
            if ($item->getQtyToInvoice()) {
                $allInvoiced = false;
                break;
            }
        }
        if ($allInvoiced) {
            return self::CANCEL_FAILED_REASON_INVOICED;
        }

        return self::CANCEL_FAILED_REASON_UNKNOWN;
    }

    public function cancel()
    {
        $this->magentoOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
        $this->magentoOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_UNHOLD, true);

        if (!$this->canCancel()) {
            return;
        }

        $this->magentoOrder->cancel();
        $this->needSave = true;
    }

    // ########################################

    /**
     * Save magento order only once and only if it's needed
     */
    public function finishUpdate()
    {
        if ($this->needSave) {
            $this->magentoOrder->save();
        }
    }

    // ########################################
}
