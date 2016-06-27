<?php

class Bigento_Tva20_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function applyTVA20($restore = false)
    {
        $newrate = $this->getAllTVA20($restore);
        foreach ($newrate as $rate) {
            try {
                $rate->setRate($rate->getNewRate());
                $rate->unsetData('new_rate');
                $rate->save();
            } catch (Exception $e) {
                Mage::log('applyTVA20 : ' . $e->getTraceAsString(), null, 'Bigento_Tva20.log', true);
            }
        }
        $this->refreshCache();
    }

    public function getAllTVA20($restore = false)
    {
        $newrate = array();
        $rateCollection = Mage::getModel('tax/calculation_rate')->getCollection()
            ->joinRegionTable();
        foreach ($rateCollection as $rate) {
            if ($restore) {
                if ($this->restoreTVA($rate)) {
                    $newrate[] = $rate;
                }
            }
            else {
                if ($this->checkTVA($rate)) {
                    $newrate[] = $rate;
                }
            }
        }
        return $newrate;
    }

    public function checkTVA($rate)
    {
        if ($rate->getRate() == '19.6000' || $rate->getRate() == 19.6000) {
            $rate->setNewRate('20.0000');
            return true;
        }
        if ($rate->getRate() == '7.0000' || $rate->getRate() == 7.0000) {
            $rate->setNewRate('10.0000');
            return true;
        }
        return false;
    }

    public function restoreTVA($rate)
    {
        if ($rate->getRate() == '20.0000' || $rate->getRate() == 20.0000) {
            $rate->setNewRate('19.6000');
            return true;
        }
        if ($rate->getRate() == '10.0000' || $rate->getRate() == 10.0000) {
            $rate->setNewRate('7.0000');
            return true;
        }
        return false;
    }

    public function getHappyNewYearDate($format = false)
    {
        $newyear = $date = new Zend_Date(mktime(0, 01, 0, 1, 1, 2014), Zend_Date::TIMESTAMP);
        if ($format) {
            return mage::helper('core')->formatDate($newyear, 'medium', true);
        }
        else {
            return $newyear;
        }
    }

    public function refreshCache()
    {
        try {
            $process = Mage::getModel('index/indexer')->getProcessByCode('catalog_product_price');
            $process->reindexAll();

            $allTypes = Mage::app()->useCache();
            foreach ($allTypes as $type => $blah) {
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            Mage::log('refreshCache : ' . $e->getTraceAsString(), null, 'Bigento_Tva20.log', true);
        }
    }



}
