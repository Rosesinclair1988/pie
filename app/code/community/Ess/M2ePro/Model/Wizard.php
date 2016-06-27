<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Wizard
{
    const STATUS_NONE            = 0;

    const STATUS_CRON            = 1;
    const STATUS_SETTINGS        = 2;
    const STATUS_LICENSE         = 3;
    const STATUS_MARKETPLACES    = 4;
    const STATUS_ACCOUNTS_EBAY   = 5;
    const STATUS_ACCOUNTS_AMAZON = 6;
    const STATUS_SYNCHRONIZATION = 7;

    const STATUS_SKIP            = 99;
    const STATUS_COMPLETE        = 100;

    const MODE_INSTALLATION = 'installation';
    const MODE_UPGRADE      = 'upgrade';

    //####################################

    public function getStatus($mode = self::MODE_INSTALLATION)
    {
        $suffix = ($mode == self::MODE_UPGRADE) ? self::MODE_UPGRADE . '/' : '';
        $status = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue("/wizard/{$suffix}", 'status');
        if (is_null($status)) {
            $this->setStatus(Ess_M2ePro_Model_Wizard::STATUS_NONE, $mode);
            $status = Ess_M2ePro_Model_Wizard::STATUS_NONE;
        }
        return (int)$status;
    }

    public function setStatus($status = self::STATUS_NONE, $mode = self::MODE_INSTALLATION)
    {
        $suffix = ($mode == self::MODE_UPGRADE) ? self::MODE_UPGRADE . '/' : '';
        Mage::helper('M2ePro/Module')->getConfig()->setGroupValue("/wizard/{$suffix}", 'status', (int)$status);
    }

    //------------------------------------

    public function isWelcome($mode = self::MODE_INSTALLATION)
    {
        return in_array($this->getStatus($mode),array(self::STATUS_NONE));
    }

    public function isFinished($mode = self::MODE_INSTALLATION)
    {
        return in_array($this->getStatus($mode),array(self::STATUS_COMPLETE,self::STATUS_SKIP));
    }

    //------------------------------------

    public function isInstallationActive()
    {
        return !$this->isInstallationFinished() && !$this->isInstallationWelcome();
    }

    public function isInstallationWelcome()
    {
        return in_array($this->getStatus(self::MODE_INSTALLATION),array(self::STATUS_NONE));
    }

    public function isInstallationFinished()
    {
        return $this->isFinished(self::MODE_INSTALLATION);
    }

    //------------------------------------

    public function isUpgradeActive()
    {
        if ($this->isInstallationActive()) {
            return false;
        }

        return !$this->isUpgradeFinished() && !$this->isUpgradeWelcome();
    }

    public function isUpgradeWelcome()
    {
        return in_array($this->getStatus(self::MODE_UPGRADE),array(self::STATUS_NONE));
    }

    public function isUpgradeFinished()
    {
        return $this->isFinished(self::MODE_UPGRADE);
    }

    //####################################

    public function clearMenuCache()
    {
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                                       array(Mage_Adminhtml_Block_Page_Menu::CACHE_TAGS));
    }

    //####################################
}
