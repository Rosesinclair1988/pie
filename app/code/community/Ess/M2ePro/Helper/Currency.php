<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Helper_Currency extends Mage_Core_Helper_Abstract
{
    public function isBase($currencyCode, $store)
    {
        $baseCurrency = Mage::app()->getStore($store)->getBaseCurrencyCode();

        return $baseCurrency == $currencyCode;
    }

    public function isAllowed($currencyCode, $store)
    {
        $allowedCurrencies = Mage::app()->getStore($store)->getAvailableCurrencyCodes();

        return in_array($currencyCode, $allowedCurrencies);
    }

    public function getConvertRateFromBase($currencyCode, $store)
    {
        if (!$this->isAllowed($currencyCode, $store)) {
            return false;
        }

        $rate = (float)Mage::app()->getStore($store)->getBaseCurrency()->getRate($currencyCode);

        return round($rate, 2);
    }

    public function formatPrice($currencyName, $priceValue)
    {
        return Mage::app()->getLocale()->currency($currencyName)->toCurrency($priceValue);
    }
}
