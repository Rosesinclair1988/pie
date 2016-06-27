<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_WizardController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/wizard')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Wizard'));

        $this->getLayout()->getBlock('head')
             ->addJs('M2ePro/WizardHandler.js');

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro');
    }

    //#############################################

    public function indexAction()
    {
        if (Mage::getSingleton('M2ePro/Wizard')->isInstallationWelcome() ||
            Mage::getSingleton('M2ePro/Wizard')->isUpgradeWelcome()) {
            $this->_redirect('*/*/welcome');
            return;
        }

        if (Mage::getSingleton('M2ePro/Wizard')->isInstallationActive()) {
            $this->_redirect('*/*/installation');
            return;
        }

        if (Mage::getSingleton('M2ePro/Wizard')->isUpgradeActive()) {
            $this->_redirect('*/*/upgrade');
            return;
        }

        if (Mage::getSingleton('M2ePro/Wizard')->isInstallationFinished() &&
            Mage::getSingleton('M2ePro/Wizard')->isUpgradeFinished()) {
            $this->_redirect('*/*/congratulation');
            return;
        }

        $this->_redirect('*/adminhtml_about/index');
    }

    //---------------------------

    public function welcomeAction()
    {
        if (!Mage::getSingleton('M2ePro/Wizard')->isInstallationWelcome() &&
            !Mage::getSingleton('M2ePro/Wizard')->isUpgradeWelcome()) {
            $this->_redirect('*/*/index');
            return;
        }

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_wizard_welcome'))
             ->renderLayout();
    }

    public function installationAction()
    {
        if (!Mage::getSingleton('M2ePro/Wizard')->isInstallationActive()) {
            $this->_redirect('*/*/index');
            return;
        }

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_wizard_installation'))
             ->renderLayout();
    }

    public function upgradeAction()
    {
        if (!Mage::getSingleton('M2ePro/Wizard')->isUpgradeActive()) {
            $this->_redirect('*/*/index');
            return;
        }

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_wizard_upgrade'))
             ->renderLayout();
    }

    public function congratulationAction()
    {
        if (!Mage::getSingleton('M2ePro/Wizard')->isInstallationFinished() ||
            !Mage::getSingleton('M2ePro/Wizard')->isUpgradeFinished()) {
            $this->_redirect('*/*/index');
            return;
        }

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_wizard_congratulation'))
             ->renderLayout();
    }

    //#############################################

    public function skipAction()
    {
        $wizardMode = $this->getRequest()->getParam('mode');

        if ($wizardMode != Ess_M2ePro_Model_Wizard::MODE_UPGRADE) {
            $wizardMode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;
        }

        if ($wizardMode == Ess_M2ePro_Model_Wizard::MODE_INSTALLATION) {
            Mage::getSingleton('M2ePro/Wizard')->setStatus(
                Ess_M2ePro_Model_Wizard::STATUS_SKIP, Ess_M2ePro_Model_Wizard::MODE_INSTALLATION
            );
        }

        Mage::getSingleton('M2ePro/Wizard')->setStatus(
            Ess_M2ePro_Model_Wizard::STATUS_SKIP, Ess_M2ePro_Model_Wizard::MODE_UPGRADE
        );

        Mage::getSingleton('M2ePro/Wizard')->clearMenuCache();

        $this->_redirect('*/*/congratulation');
    }

    public function completeAction()
    {
        $wizardMode = $this->getRequest()->getParam('mode');

        if ($wizardMode != Ess_M2ePro_Model_Wizard::MODE_UPGRADE) {
            $wizardMode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;
        }

        if ($wizardMode == Ess_M2ePro_Model_Wizard::MODE_INSTALLATION) {
            Mage::getSingleton('M2ePro/Wizard')->setStatus(
                Ess_M2ePro_Model_Wizard::STATUS_COMPLETE, Ess_M2ePro_Model_Wizard::MODE_INSTALLATION
            );
        }

        Mage::getSingleton('M2ePro/Wizard')->setStatus(
            Ess_M2ePro_Model_Wizard::STATUS_COMPLETE, Ess_M2ePro_Model_Wizard::MODE_UPGRADE
        );

        Mage::getSingleton('M2ePro/Wizard')->clearMenuCache();

        $this->_redirect('*/*/congratulation');
    }

    //#############################################

    public function setStatusAction()
    {
        $wizardMode = $this->getRequest()->getParam('mode');

        if ($wizardMode != Ess_M2ePro_Model_Wizard::MODE_UPGRADE) {
            $wizardMode = Ess_M2ePro_Model_Wizard::MODE_INSTALLATION;
        }

        $status = $this->getRequest()->getParam('status');
        $status && Mage::getSingleton('M2ePro/Wizard')->setStatus($status, $wizardMode);

        if ($wizardMode == Ess_M2ePro_Model_Wizard::MODE_INSTALLATION &&
            Mage::getSingleton('M2ePro/Wizard')->isInstallationFinished()) {
            Mage::getSingleton('M2ePro/Wizard')->clearMenuCache();
        }
    }

    public function getHiddenStepsAction()
    {
        $hiddenSteps = array();
        if (!Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $hiddenSteps[] = Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_EBAY;
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $hiddenSteps[] = Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_AMAZON;
        }

        exit(json_encode($hiddenSteps));
    }

    //#############################################
}
