<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Order_Proxy
{
    const CHECKOUT_GUEST    = 'guest';
    const CHECKOUT_REGISTER = 'register';

    // ########################################

    /** @var $order Ess_M2ePro_Model_Ebay_Order|Ess_M2ePro_Model_Amazon_Order */
    protected $order = NULL;

    protected $items = array();

    /** @var $store Mage_Core_Model_Store */
    protected $store = NULL;

    protected $addressData = array();

    protected $comments = array();

    // ########################################

    /**
     * Set order object which should be a source for magento order
     *
     * @param Ess_M2ePro_Model_Component_Child_Abstract $order
     * @return Ess_M2ePro_Model_Order_Proxy
     */
    public function setOrder(Ess_M2ePro_Model_Component_Child_Abstract $order)
    {
        $this->order = $order;

        return $this;
    }

    // ########################################

    /**
     * Return proxy objects for order items
     *
     * @return Ess_M2ePro_Model_Order_Item_Proxy[]
     */
    public function getItems()
    {
        if (count($this->items) == 0) {
            foreach ($this->order->getParentObject()->getItemsCollection()->getItems() as $item) {
                $this->items[] = $item->getProxy();
            }
        }

        return $this->items;
    }

    // ########################################

    /**
     * Set store where order will be imported to
     *
     * @param Mage_Core_Model_Store $store
     * @return Ess_M2ePro_Model_Order_Proxy
     */
    public function setStore(Mage_Core_Model_Store $store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Return store order will be imported to
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->store;
    }

    // ########################################

    /**
     * Return checkout method
     *
     * @abstract
     * @return string
     */
    abstract public function getCheckoutMethod();

    /**
     * Check whether checkout method is guest
     *
     * @return bool
     */
    public function isCheckoutMethodGuest()
    {
        return $this->getCheckoutMethod() == self::CHECKOUT_GUEST;
    }

    // ########################################

    /**
     * Return buyer email
     *
     * @abstract
     * @return string
     */
    abstract public function getBuyerEmail();

    /**
     * Return customer object
     *
     * @abstract
     * @return Mage_Customer_Model_Customer
     */
    abstract public function getCustomer();

    /**
     * Return shipping address info
     *
     * @return array
     */
    public function getAddressData()
    {
        if (empty($this->addressData)) {
            $rawAddressData = $this->order->getRawAddressData();

            $recipientNameParts = $this->getNameParts($rawAddressData['recipient_name']);
            $this->addressData['firstname'] = $recipientNameParts['firstname'];
            $this->addressData['lastname'] = $recipientNameParts['lastname'];

            $customerNameParts = $this->getNameParts($rawAddressData['buyer_name']);
            $this->addressData['customer_firstname'] = $customerNameParts['firstname'];
            $this->addressData['customer_lastname'] = $customerNameParts['lastname'];

            $this->addressData['email'] = $rawAddressData['email'];
            $this->addressData['country_id'] = $rawAddressData['country_id'];
            $this->addressData['region'] = $rawAddressData['region'];
            $this->addressData['city'] = $rawAddressData['city'];
            $this->addressData['postcode'] = $rawAddressData['postcode'] ? $rawAddressData['postcode'] : '0000';
            $this->addressData['telephone'] = $rawAddressData['telephone'] ? $rawAddressData['telephone'] : '0000000';
            $this->addressData['street'] = !empty($rawAddressData['street']) ? $rawAddressData['street'] : array();
            $this->addressData['save_in_address_book'] = 0;
            $this->addressData['region_id'] = $this->getRegionId(
                $rawAddressData['region'], $rawAddressData['country_id']
            );
        }

        return $this->addressData;
    }

    // ########################################

    private function getRegionId($regionCode, $countryCode)
    {
        if ($regionCode == '') {
            return 1;
        }

        try {
            $country = Mage::getModel('directory/country')->loadByCode($countryCode);
            $countryId = $country->getId();
        } catch (Exception $e) {
            $countryId = null;
        }

        $regionCollection = Mage::getModel('directory/region')->getCollection();
        $regionCollection->getSelect()->where('code = ? OR default_name = ?', $regionCode);

        if (!is_null($countryId)) {
            $regionCollection->addFieldToFilter('country_id', $countryId);
        }

        $region = $regionCollection->getFirstItem();

        return $region->getId() ? $region->getId() : 1;
    }

    // ########################################

    private function getNameParts($fullName)
    {
        $fullName = trim($fullName);

        $spacePosition = strpos($fullName, ' ');
        $spacePosition === false && $spacePosition = strlen($fullName);

        $firstName = trim(substr($fullName, 0, $spacePosition));
        $lastName = trim(substr($fullName, $spacePosition + 1));

        return array(
            'firstname' => $firstName ? $firstName : 'N/A',
            'lastname'  => $lastName ? $lastName : 'N/A'
        );
    }

    // ########################################

    /**
     * Return order currency code
     *
     * @abstract
     * @return string
     */
    abstract public function getCurrency();

    /**
     * Return payment data
     *
     * @abstract
     * @return array
     */
    abstract public function getPaymentData();

    /**
     * Return shipping data
     *
     * @abstract
     * @return array
     */
    abstract public function getShippingData();

    /**
     * Return order comments array
     *
     * @return array
     */
    public function getComments()
    {
        return $this->comments;
    }

    // ########################################

    /**
     * Return tax rate
     *
     * @abstract
     * @return float
     */
    abstract public function getTaxRate();

    /**
     * Check whether order has Tax (not VAT)
     *
     * @abstract
     * @return bool
     */
    abstract public function hasTax();

    /**
     * Check whether order has VAT (value added tax)
     *
     * @abstract
     * @return bool
     */
    abstract public function hasVat();

    /**
     * Check whether shipping price includes tax
     *
     * @abstract
     * @return bool
     */
    abstract public function isShippingPriceIncludesTax();

    /**
     * Check whether tax mode option is set to "None" in Account settings
     *
     * @abstract
     * @return bool
     */
    abstract public function isTaxModeNone();

    /**
     * Check whether tax mode option is set to "Channel" in Account settings
     *
     * @abstract
     * @return bool
     */
    abstract public function isTaxModeChannel();

    /**
     * Check whether tax mode option is set to "Magento" in Account settings
     *
     * @abstract
     * @return bool
     */
    abstract public function isTaxModeMagento();

    /**
     * Check whether tax mode option is set to "Mixed" in Account settings
     *
     * @abstract
     * @return bool
     */
    public function isTaxModeMixed()
    {
        return !$this->isTaxModeNone() &&
               !$this->isTaxModeChannel() &&
               !$this->isTaxModeMagento();
    }

    // ########################################
}
