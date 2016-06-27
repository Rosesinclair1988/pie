<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Amazon_ListingController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/listings')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Manage Listings'))
             ->_title(Mage::helper('M2ePro')->__('Amazon Listings'));

        $this->getLayout()->getBlock('head')
             ->addItem('js_css', 'prototype/windows/themes/default.css')
             ->addCss('M2ePro/css/Plugin/ProgressBar.css')
             ->addCss('M2ePro/css/Plugin/AreaWrapper.css')
             ->addCss('M2ePro/css/Plugin/DropDown.css')
             ->addCss('M2ePro/css/Plugin/AutoComplete.css')
             ->addJs('prototype/window.js')
             ->addJs('M2ePro/Plugin/DropDown.js')
             ->addJs('M2ePro/Plugin/ProgressBar.js')
             ->addJs('M2ePro/Plugin/AreaWrapper.js')
             ->addJs('M2ePro/Plugin/AutoComplete.js')
             ->addJs('M2ePro/Template/AttributeSetHandler.js')
             ->addJs('M2ePro/Listing/ProductGridHandler.js')
             ->addJs('M2ePro/Listing/ItemGridHandler.js')
             ->addJs('M2ePro/Listing/ActionHandler.js')
             ->addJs('M2ePro/Listing/Category/TreeHandler.js')
             ->addJs('M2ePro/Amazon/Listing/SettingsHandler.js')
             ->addJs('M2ePro/Amazon/Listing/ChannelSettingsHandler.js')
             ->addJs('M2ePro/Amazon/Listing/CategoryHandler.js')
             ->addJs('M2ePro/Listing/MoveToListingHandler.js')
             ->addJs('M2ePro/Amazon/Listing/ProductSearchHandler.js');

        if (Mage::helper('M2ePro/Magento')->isCommunityEdition()) {
            version_compare(Mage::getVersion(), '1.7.0.0', '>=')
                ? $this->getLayout()->getBlock('head')->addCss('lib/prototype/windows/themes/magento.css')
                : $this->getLayout()->getBlock('head')->addItem('js_css', 'prototype/windows/themes/magento.css');
        } else {
            $this->getLayout()->getBlock('head')->addCss('lib/prototype/windows/themes/magento.css');
            $this->getLayout()->getBlock('head')->addItem('js_css', 'prototype/windows/themes/magento.css');
        }

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/listings/listing');
    }

    //#############################################

    public function indexAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('*/adminhtml_listing/index');
        }

        /** @var $block Ess_M2ePro_Block_Adminhtml_Listing */
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_listing');
        $block->disableEbayTab();

        $this->getResponse()->setBody($block->getAmazonTabHtml());
    }

    public function listingGridAction()
    {
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_grid');
        $this->getResponse()->setBody($block->toHtml());
    }

    //#############################################

    public function searchAction()
    {
        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_search'))
             ->renderLayout();
    }

    public function searchGridAction()
    {
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_search_grid');
        $this->getResponse()->setBody($block->toHtml());
    }

    //#############################################

    public function addAction()
    {
        // Get step param
        //----------------------------
        $step = $this->getRequest()->getParam('step');

        if (is_null($step)) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        // Switch step param
        //----------------------------
        switch ($step) {
            case '1':
                $this->addStepOne();
                break;
            case '2':
                $this->addStepTwo();
                break;
            case '3':
                $this->addStepThree();
                break;
            case '4':
                $this->addStepFour();
                break;
            case '5':
                $this->addStepFive();
                break;
            default:
                $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
                break;
        }
        //----------------------------
    }

    public function addStepOne()
    {
        // Check clear param
        //----------------------------
        $clearAction = $this->getRequest()->getParam('clear');

        if (!is_null($clearAction)) {
            if ($clearAction == 'yes') {
                Mage::helper('M2ePro')->setSessionValue('temp_data', array());
                Mage::helper('M2ePro')->setSessionValue('temp_listing_categories', array());
                $this->_redirect('*/*/add',array('step'=>'1'));
                return;
            } else {
                $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
                return;
            }
        }
        //----------------------------

        // Check exist temp data
        //----------------------------
        if (is_null(Mage::helper('M2ePro')->getSessionValue('temp_data')) ||
            is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories'))) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        // If it post request
        //----------------------------
        if ($this->getRequest()->isPost()) {

            $post = $this->getRequest()->getPost();

            if ($post['synchronization_start_type'] != Ess_M2ePro_Model_Listing::SYNCHRONIZATION_START_TYPE_DATE) {
                $synchronizationStartDate = Mage::helper('M2ePro')->getCurrentGmtDate();
            } else {
                $synchronizationStartDate = $post['synchronization_start_date'];
            }
            if ($post['synchronization_stop_type'] != Ess_M2ePro_Model_Listing::SYNCHRONIZATION_START_TYPE_THROUGH) {
                $synchronizationStopDate = Mage::helper('M2ePro')->getCurrentGmtDate();
            } else {
                $synchronizationStopDate = $post['synchronization_stop_date'];
            }

            $temp = array(
                'title' => strip_tags($post['title']),
                'attribute_sets' => $post['attribute_sets'],

                'template_selling_format_id'    => $post['template_selling_format_id'],
                'template_selling_format_title' => Mage::helper('M2ePro/Component_Amazon')
                    ->getModel('Template_SellingFormat')
                    ->load((int)$post['template_selling_format_id'])
                    ->getTitle(),
                'template_description_id' => $post['template_description_id'],
                'template_description_title' => Mage::helper('M2ePro/Component_Amazon')
                    ->getModel('Template_Description')
                    ->load((int)$post['template_description_id'])
                    ->getTitle(),
                'template_synchronization_id'    => $post['template_synchronization_id'],
                'template_synchronization_title' => Mage::helper('M2ePro/Component_Amazon')
                    ->getModel('Template_Synchronization')
                    ->load((int)$post['template_synchronization_id'])
                    ->getTitle(),
                'synchronization_start_type' => $post['synchronization_start_type'],
                'synchronization_start_through_metric' => $post['synchronization_start_through_metric'],
                'synchronization_start_through_value' => $post['synchronization_start_through_value'],
                'synchronization_start_date' => $synchronizationStartDate,

                'synchronization_stop_type' => $post['synchronization_stop_type'],
                'synchronization_stop_through_metric' => $post['synchronization_stop_through_metric'],
                'synchronization_stop_through_value' => $post['synchronization_stop_through_value'],
                'synchronization_stop_date' => $synchronizationStopDate,
            );

            $sessionData = Mage::helper('M2ePro')->getSessionValue('temp_data');
            is_null($sessionData) && $sessionData = array();

            Mage::helper('M2ePro')->setSessionValue('temp_data', array_merge($sessionData, $temp));

            $this->_redirect('*/*/add',array('step'=>'2'));
            return;
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue(
            'temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data')
        );
        Mage::helper('M2ePro')->setGlobalValue(
            'temp_listing_categories', Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')
        );

        Mage::helper('M2ePro')->setSessionValue('temp_listing_categories', array());

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_stepOne'))
             ->renderLayout();
    }

    public function addStepTwo()
    {
        // Check exist temp data
        //----------------------------
        if (is_null(Mage::helper('M2ePro')->getSessionValue('temp_data')) ||
            count(Mage::helper('M2ePro')->getSessionValue('temp_data')) == 0 ||
            is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories'))) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        // If it post request
        //----------------------------
        if ($this->getRequest()->isPost()) {

            $post = $this->getRequest()->getPost();

            $temp = array(
                'account_id' => $post['account_id'],
                'marketplace_id' => $post['marketplace_id'],
                'sku_mode' => $post['sku_mode'],
                'sku_custom_attribute' => $post['sku_custom_attribute'],
                'general_id_mode' => $post['general_id_mode'],
                'general_id_custom_attribute' => $post['general_id_custom_attribute'],
                'worldwide_id_mode' => $post['worldwide_id_mode'],
                'worldwide_id_custom_attribute' => $post['worldwide_id_custom_attribute'],
                'condition_mode' => $post['condition_mode'],
                'condition_value' => $post['condition_value'],
                'condition_custom_attribute' => $post['condition_custom_attribute'],
                'condition_note_mode' => $post['condition_note_mode'],
                'condition_note_value' => $post['condition_note_value'],
                'condition_note_custom_attribute' => $post['condition_note_custom_attribute'],
                'handling_time' => $post['handling_time']
            );

            $sessionData = Mage::helper('M2ePro')->getSessionValue('temp_data');
            Mage::helper('M2ePro')->setSessionValue('temp_data', array_merge($sessionData, $temp));

            $this->_redirect('*/*/add',array('step'=>'3'));
            return;
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue('temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data'));

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_stepTwo'))
             ->renderLayout();
    }

    public function addStepThree()
    {
        // Check exist temp data
        //----------------------------
        if (is_null(Mage::helper('M2ePro')->getSessionValue('temp_data')) ||
            count(Mage::helper('M2ePro')->getSessionValue('temp_data')) == 0 ||
            is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories'))) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        // If it post request
        //----------------------------
        if ($this->getRequest()->isPost()) {

            $post = $this->getRequest()->getPost();

            $temp = array(
                'store_id' => $post['store_id'],
                'source_products' => $post['source_products'],
                'hide_products_others_listings' => $post['hide_products_others_listings']
            );

            $sessionData = Mage::helper('M2ePro')->getSessionValue('temp_data');
            is_null($sessionData) && $sessionData = array();

            Mage::helper('M2ePro')->setSessionValue('temp_data', array_merge($sessionData, $temp));

            $this->_redirect('*/*/add',array('step'=>'4'));
            return;
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue('temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data'));

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_stepThree'))
             ->renderLayout();
    }

    public function addStepFour()
    {
        // Check exist temp data
        //----------------------------
        if (is_null(Mage::helper('M2ePro')->getSessionValue('temp_data')) ||
            count(Mage::helper('M2ePro')->getSessionValue('temp_data')) == 0 ||
            is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories'))) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        // Get remember param
        //----------------------------
        $rememberCategories = $this->getRequest()->getParam('remember_categories');

        if (!is_null($rememberCategories)) {
            if ($rememberCategories == 'yes') {

                // Get selected_categories param
                //---------------
                $selectedCategoriesIds = array();

                $selectedCategories = $this->getRequest()->getParam('selected_categories');
                if (!is_null($selectedCategories)) {
                    $selectedCategoriesIds = explode(',',$selectedCategories);
                }
                $selectedCategoriesIds = array_unique($selectedCategoriesIds);
                //---------------

                // Save selected categories
                //---------------
                $m2eProData = Mage::helper('M2ePro')->getSessionValue('temp_data');
                $m2eProData['categories_add_action'] = $this->getRequest()->getParam('categories_add_action');
                $m2eProData['categories_delete_action'] = $this->getRequest()->getParam('categories_delete_action');
                Mage::helper('M2ePro')->setSessionValue('temp_data', $m2eProData);
                Mage::helper('M2ePro')->setSessionValue('temp_listing_categories', $selectedCategoriesIds);
                //---------------

                // Goto step four
                //---------------
                $this->_redirect('*/*/add',array('step'=>'5'));
                //---------------

                return;

            } else {
                $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
                return;
            }
        }
        //----------------------------

        // Get save param
        //----------------------------
        $save = $this->getRequest()->getParam('save');

        if (!is_null($save)) {
            if ($save == 'yes') {

                // Get selected_products param
                //---------------
                $selectedProductsIds = array();

                $selectedProducts = $this->getRequest()->getParam('selected_products');
                if (!is_null($selectedProducts)) {
                    $selectedProductsIds = explode(',',$selectedProducts);
                }
                $selectedProductsIds = array_unique($selectedProductsIds);
                //---------------

                // Get selected_categories param
                //---------------
                $selectedCategoriesIds = array();

                $selectedCategories = $this->getRequest()->getParam('selected_categories');
                if (!is_null($selectedCategories)) {
                    $selectedCategoriesIds = explode(',',$selectedCategories);
                    $m2eProData = Mage::helper('M2ePro')->getSessionValue('temp_data');
                    $m2eProData['categories_add_action'] = $this->getRequest()->getParam('categories_add_action');
                    $m2eProData['categories_delete_action'] = $this->getRequest()->getParam('categories_delete_action');
                    Mage::helper('M2ePro')->setSessionValue('temp_data', $m2eProData);
                }
                $selectedCategoriesIds = array_unique($selectedCategoriesIds);
                //---------------

                // Get session selected_categories
                //---------------
                $selectedSessionCategoriesIds = Mage::helper('M2ePro')->getSessionValue('temp_listing_categories');
                $selectedSessionCategoriesIds = array_unique($selectedSessionCategoriesIds);
                //---------------

                // Prepare listing data
                //---------------
                $sessionData = Mage::helper('M2ePro')->getSessionValue('temp_data');

                if (!empty($sessionData['synchronization_start_date'])) {
                    $sessionData['synchronization_start_date'] = Mage::helper('M2ePro')->timezoneDateToGmt(
                        $sessionData['synchronization_start_date']
                    );
                }
                if (!empty($sessionData['synchronization_stop_date'])) {
                    $sessionData['synchronization_stop_date'] = Mage::helper('M2ePro')->timezoneDateToGmt(
                        $sessionData['synchronization_stop_date']
                    );
                }

                Mage::helper('M2ePro')->setSessionValue('temp_data', $sessionData);
                //---------------

                // Add new listing
                //---------------
                $generalTemplate = Mage::helper('M2ePro/Component_Amazon')->getModel('Template_General');
                $generalTemplate->addData($sessionData)->save();

                $sessionData['template_general_id'] = $generalTemplate->getId();

                $listing = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')
                                                                  ->addData($sessionData)
                                                                  ->save();
                //---------------

                // Attribute sets
                //--------------------
                $oldListingAttributeSets = Mage::getModel('M2ePro/AttributeSet')
                    ->getCollection()
                    ->addFieldToFilter('object_type',Ess_M2ePro_Model_AttributeSet::OBJECT_TYPE_LISTING)
                    ->addFieldToFilter('object_id',(int)$listing->getId())
                    ->getItems();

                foreach ($oldListingAttributeSets as $oldAttributeSet) {
                    /** @var $oldAttributeSet Ess_M2ePro_Model_AttributeSet */
                    $oldAttributeSet->deleteInstance();
                }

                $oldGeneralTemplateAttributeSets = Mage::getModel('M2ePro/AttributeSet')
                    ->getCollection()
                    ->addFieldToFilter('object_type',Ess_M2ePro_Model_AttributeSet::OBJECT_TYPE_TEMPLATE_GENERAL)
                    ->addFieldToFilter('object_id',(int)$generalTemplate->getId())
                    ->getItems();

                foreach ($oldGeneralTemplateAttributeSets as $oldAttributeSet) {
                    /** @var $oldAttributeSet Ess_M2ePro_Model_AttributeSet */
                    $oldAttributeSet->deleteInstance();
                }

                if (!is_array($sessionData['attribute_sets'])) {
                    $sessionData['attribute_sets'] = explode(',', $sessionData['attribute_sets']);
                }
                foreach ($sessionData['attribute_sets'] as $newAttributeSet) {
                    $dataForAdd = array(
                        'object_type' => Ess_M2ePro_Model_AttributeSet::OBJECT_TYPE_LISTING,
                        'object_id' => (int)$listing->getId(),
                        'attribute_set_id' => (int)$newAttributeSet
                    );
                    Mage::getModel('M2ePro/AttributeSet')->setData($dataForAdd)->save();

                    $dataForAdd = array(
                        'object_type' => Ess_M2ePro_Model_AttributeSet::OBJECT_TYPE_TEMPLATE_GENERAL,
                        'object_id' => (int)$generalTemplate->getId(),
                        'attribute_set_id' => (int)$newAttributeSet
                    );
                    Mage::getModel('M2ePro/AttributeSet')->setData($dataForAdd)->save();
                }
                //--------------------

                // Set message to log
                //---------------
                $tempLog = Mage::getModel('M2ePro/Listing_Log');
                $tempLog->setComponentMode($listing->getComponentMode());
                $tempLog->addListingMessage(
                    $listing->getId(),
                    Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                    NULL,
                    Ess_M2ePro_Model_Listing_Log::ACTION_ADD_LISTING,
                    // Parser hack -> Mage::helper('M2ePro')->__('Listing was successfully added');
                    'Listing was successfully added',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH
                );
                //---------------

                // Add products
                //---------------
                if (count($selectedProductsIds) > 0 &&
                    count($selectedCategoriesIds) == 0 &&
                    count($selectedSessionCategoriesIds) == 0) {
                    foreach ($selectedProductsIds as $productId) {
                        $listing->addProduct($productId);
                    }
                }
                //---------------

                // Add categories
                //---------------
                if (count($selectedProductsIds) == 0 &&
                    count($selectedCategoriesIds) > 0 &&
                    count($selectedSessionCategoriesIds) == 0) {
                    foreach ($selectedCategoriesIds as $categoryId) {
                        $listing->addProductsFromCategory($categoryId);
                        Mage::getModel('M2ePro/Listing_Category')
                                           ->setData(array('listing_id'=>$listing->getId(),'category_id'=>$categoryId))
                                           ->save();
                    }
                }
                //---------------

                // Add categories and products
                //---------------
                if (count($selectedProductsIds) > 0 &&
                    count($selectedCategoriesIds) == 0 &&
                    count($selectedSessionCategoriesIds) > 0) {
                    foreach ($selectedSessionCategoriesIds as $categoryId) {
                        Mage::getModel('M2ePro/Listing_Category')
                                           ->setData(array('listing_id'=>$listing->getId(),'category_id'=>$categoryId))
                                           ->save();
                    }
                    foreach ($selectedProductsIds as $productId) {
                        $listing->addProduct($productId);
                    }
                }
                //---------------

                // Clear session data
                //---------------
                Mage::helper('M2ePro')->setSessionValue('temp_data', array());
                Mage::helper('M2ePro')->setSessionValue('temp_listing_categories', array());
                //---------------

                $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Listing was successfully added.'));

                if ($this->getRequest()->getParam('back') == 'list') {
                    $this->_redirect('*/*/index');
                } else {
                    $this->_redirect('*/*/view',array('id'=>$listing->getId(),'new'=>'yes'));
                }

                return;

            } else {
                $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
                return;
            }
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue(
            'temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data')
        );
        Mage::helper('M2ePro')->setGlobalValue(
            'temp_listing_categories', Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')
        );

        Mage::helper('M2ePro')->setSessionValue('temp_listing_categories', array());

        // Load layout and start render
        //----------------------------
        $this->_initAction();

        $temp = Mage::helper('M2ePro')->getSessionValue('temp_data');
        if ($temp['source_products'] == Ess_M2ePro_Model_Listing::SOURCE_PRODUCTS_CUSTOM) {
            $blockContent = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_stepFourProduct');
        } else if ($temp['source_products'] == Ess_M2ePro_Model_Listing::SOURCE_PRODUCTS_CATEGORIES) {
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $blockContent = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_stepFourCategory');
        } else {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }

        $this->_addContent($blockContent);

        $this->renderLayout();
        //----------------------------
    }

    public function addStepFive()
    {
        // Check exist temp data
        //----------------------------
        if (is_null(Mage::helper('M2ePro')->getSessionValue('temp_data')) ||
            count(Mage::helper('M2ePro')->getSessionValue('temp_data')) == 0 ||
            is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')) ||
            count(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')) == 0) {
            $this->_redirect('*/*/add',array('step'=>'1','clear'=>'yes'));
            return;
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue(
            'temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data')
        );
        Mage::helper('M2ePro')->setGlobalValue(
            'temp_listing_categories', Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')
        );

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_add_StepFive'))
             ->renderLayout();
    }

    //#############################################

    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!$model->getId() && $id) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Listing does not exist.'));
            return $this->_redirect('*/adminhtml_listing/index');
        }

        // Check listing lock item
        //----------------------------
        $lockItem = Mage::getModel(
            'M2ePro/Listing_LockItem',array('id' => $id, 'component' => Ess_M2ePro_Helper_Component_Amazon::NICK)
        );
        if ($lockItem->isExist()) {
            $this->_getSession()->addWarning(
                Mage::helper('M2ePro')->__('The listing is locked by another process. Please try again later.')
            );
        }
        //----------------------------

        // Check listing lock object
        //----------------------------
        if ($model->isLockedObject('products_in_action')) {
            $this->_getSession()->addNotice(
                Mage::helper('M2ePro')->__('Some Amazon request(s) are being processed now.')
            );
        }
        //----------------------------

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $model->getData());

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_view'))
             ->renderLayout();
    }

    public function viewGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!$model->getId() && $id) {
            Mage::helper('M2ePro')->setGlobalValue('temp_data', array());
        } else {
            Mage::helper('M2ePro')->setGlobalValue('temp_data', $model->getData());
        }

        $response = $this->loadLayout()->getLayout()
                         ->createBlock('M2ePro/adminhtml_amazon_listing_view_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!$model->getId()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Listing does not exist.'));
            return $this->_redirect('*/adminhtml_listing/index');
        }

        $generalTemplate = $model->getGeneralTemplate();

        $additionalData = array(
            'template_selling_format_title'  => Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Template_SellingFormat')
                ->load($model->getData('template_selling_format_id'))
                ->getTitle(),
            'template_description_title'     => Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Template_Description')
                ->load($model->getData('template_description_id'))
                ->getTitle(),
            'template_synchronization_title' => Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Template_Synchronization')
                ->load($model->getData('template_synchronization_id'))
                ->getTitle(),
            'account_id' => $generalTemplate->getData('account_id'),
            'marketplace_id' => $generalTemplate->getData('marketplace_id'),
            'sku_mode' => $generalTemplate->getData('sku_mode'),
            'sku_custom_attribute' => $generalTemplate->getData('sku_custom_attribute'),
            'general_id_mode' => $generalTemplate->getData('general_id_mode'),
            'general_id_custom_attribute' => $generalTemplate->getData('general_id_custom_attribute'),
            'worldwide_id_mode' => $generalTemplate->getData('worldwide_id_mode'),
            'worldwide_id_custom_attribute' => $generalTemplate->getData('worldwide_id_custom_attribute'),
            'attribute_sets' => $model->getAttributeSetsIds(),
            'condition_mode' => $generalTemplate->getData('condition_mode'),
            'condition_value' => $generalTemplate->getData('condition_value'),
            'condition_custom_attribute' => $generalTemplate->getData('condition_custom_attribute'),
            'condition_note_mode' => $generalTemplate->getData('condition_note_mode'),
            'condition_note_value' => $generalTemplate->getData('condition_note_value'),
            'condition_note_custom_attribute' => $generalTemplate->getData('condition_note_custom_attribute'),
            'handling_time' => $generalTemplate->getData('handling_time')
        );

        Mage::helper('M2ePro')->setGlobalValue('temp_data', array_merge($model->getData(), $additionalData));

        $this->_initAction()
             ->_addLeft($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_edit_tabs'))
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_edit'))
             ->renderLayout();
    }

    public function saveAction()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/adminhtml_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!$model->getId() && $id) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Listing does not exist.'));
            return $this->_redirect('*/adminhtml_listing/index');
        }

        // Base prepare
        //--------------------
        $data = array();
        //--------------------

        // tab: settings
        //--------------------
        $keys = array(
            'title',
            'template_selling_format_id',
            'template_description_id',
            'template_synchronization_id',

            'synchronization_start_type',
            'synchronization_start_through_metric',
            'synchronization_start_through_value',
            'synchronization_start_date',

            'synchronization_stop_type',
            'synchronization_stop_through_metric',
            'synchronization_stop_through_value',
            'synchronization_stop_date',

            'categories_add_action',
            'categories_delete_action'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        //--------------------

        // Prepare listing data
        //---------------
        if (!empty($data['synchronization_start_date'])) {
            $data['synchronization_start_date'] = Mage::helper('M2ePro')->timezoneDateToGmt(
                $data['synchronization_start_date']
            );
        }
        if (!empty($data['synchronization_stop_date'])) {
            $data['synchronization_stop_date'] = Mage::helper('M2ePro')->timezoneDateToGmt(
                $data['synchronization_stop_date']
            );
        }
        //---------------

        // Prepare listing data
        //---------------
        if ($model->getData('template_synchronization_id') != $data['template_synchronization_id']) {

            $model->setSynchronizationAlreadyStart(false);
            $model->setSynchronizationAlreadyStop(false);
        }

        if ($model->getData('synchronization_start_type') != $data['synchronization_start_type'] ||
            $model->getData('synchronization_start_through_metric') != $data['synchronization_start_through_metric'] ||
            $model->getData('synchronization_start_through_value') != $data['synchronization_start_through_value'] ||
            $model->getData('synchronization_start_date') != $data['synchronization_start_date']) {

            $model->setSynchronizationAlreadyStart(false);
        }

        if ($model->getData('synchronization_stop_type') != $data['synchronization_stop_type'] ||
            $model->getData('synchronization_stop_through_metric') != $data['synchronization_stop_through_metric'] ||
            $model->getData('synchronization_stop_through_value') != $data['synchronization_stop_through_value'] ||
            $model->getData('synchronization_stop_date') != $data['synchronization_stop_date']) {

            $model->setSynchronizationAlreadyStop(false);
        }
        //---------------

        $model->addData($data)->save();

        $templateData = array();

        // tab: channel settings
        //---------------
        $keys = array(
            'account_id',
            'marketplace_id',

            'sku_mode',
            'sku_custom_attribute',
            'general_id_mode',
            'general_id_custom_attribute',
            'worldwide_id_mode',
            'worldwide_id_custom_attribute',

            'condition_mode',
            'condition_value',
            'condition_custom_attribute',

            'condition_note_mode',
            'condition_note_value',
            'condition_note_custom_attribute',

            'handling_time'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $templateData[$key] = $post[$key];
            }
        }
        //---------------
        $generalTemplate = $model->getGeneralTemplate();

        //changing existance check status for not listed products in this listing
        if ($id && $generalTemplate->getData('sku_mode') != $templateData['sku_mode'] ) {

            /** @var $collection Varien_Data_Collection_Db */
            $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
            $collection->addFieldToFilter('listing_id',$id);
            $collection->addFieldToFilter('status',Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED);

            $tempItems = $collection->toArray();

            if ($tempItems['totalRecords']) {

                $productIdsToUpdate = array();
                foreach ($tempItems['items'] as $item) {
                    $productIdsToUpdate[] = $item['listing_product_id'];
                }

                $productPacks = array_chunk($productIdsToUpdate,100);

                /* @var $writeConnection Varien_Db_Adapter_Pdo_Mysql */
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                foreach ($productPacks as $productPack) {

                    $writeConnection->update(
                        Mage::getSingleton('core/resource')->getTableName('m2epro_amazon_listing_product'),
                        array(
                            'existance_check_status'
                                => Ess_M2ePro_Model_Amazon_Listing_Product::EXISTANCE_CHECK_STATUS_NONE
                        ),
                        array('listing_product_id IN ('.implode(', ', $productPack).')')
                    );
                }
            }
        }

        $generalTemplate->addData($templateData)->save();

        $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('The listing was successfully saved.'));

        Mage::getModel('M2ePro/Listing_Log')->updateListingTitle($id,$data['title']);

        $this->_redirectUrl(Mage::helper('M2ePro')->getBackUrl('list',array(),array('edit'=>array('id'=>$id))));
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        $ids = $this->getRequest()->getParam('ids');

        if (is_null($id) && is_null($ids)) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Please select item(s) to remove'));
            return $this->_redirect('*/*/index');
        }

        $idsForDelete = array();
        !is_null($id) && $idsForDelete[] = (int)$id;
        !is_null($ids) && $idsForDelete = array_merge($idsForDelete,(array)$ids);

        $deleted = $locked = 0;
        foreach ($idsForDelete as $id) {
            $template = Mage::getModel('M2ePro/Listing')->loadInstance($id);
            if ($template->isLocked()) {
                $locked++;
            } else {
                $tempGeneralTemplate = $template->getGeneralTemplate();
                $template->deleteInstance();
                $tempGeneralTemplate->deleteInstance();
                $deleted++;
            }
        }

        $tempString = Mage::helper('M2ePro')->__('%count% listing(s) were successfully deleted');
        $deleted && $this->_getSession()->addSuccess(str_replace('%count%',$deleted,$tempString));

        $tempString = Mage::helper('M2ePro')->__('%count% listing(s) have listed items and can not be deleted');
        $locked && $this->_getSession()->addError(str_replace('%count%',$locked,$tempString));

        $this->_redirect('*/adminhtml_listing/index');
    }

    //#############################################

    public function productAction()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $model Ess_M2ePro_Model_Listing */
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!$model->getId() && $id) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Listing does not exist..'));
            return $this->_redirect('*/adminhtml_listing/index');
        }

        // Get save param
        //----------------------------
        if ($this->getRequest()->isPost()) {

            // Get selected_products param
            //---------------
            $selectedProductsIds = array();

            $selectedProducts = $this->getRequest()->getParam('selected_products');
            if (!is_null($selectedProducts)) {
                $selectedProductsIds = explode(',',$selectedProducts);
            }
            $selectedProductsIds = array_unique($selectedProductsIds);
            //---------------

            // Add products
            //---------------
            $idsToListAction = array();

            foreach ($selectedProductsIds as $productId) {
                $productInstance = $model->addProduct($productId);
                if ($productInstance instanceof Ess_M2ePro_Model_Listing_Product) {
                    $idsToListAction[] = $productInstance->getId();
                }
            }
            //---------------

            $redirectUrl = Mage::helper('M2ePro')->getBackUrl('list');

            if ($this->getRequest()->getParam('do_list')) {
                $redirectUrl = $this->getUrl('*/adminhtml_amazon_listing/view', array('id'=>$id));
                Mage::helper('M2ePro')->setSessionValue('products_ids_for_list', implode(',',$idsToListAction));
            }

            $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('The products were added to listing.'));
            $this->_redirectUrl($redirectUrl);
            return;
        }
        //----------------------------

        $tempData = $model->getData();
        $tempData['attribute_sets'] = $model->getAttributeSetsIds();
        Mage::helper('M2ePro')->setGlobalValue('temp_data', $tempData);
        Mage::helper('M2ePro')->setGlobalValue('temp_listing_categories', array());

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_product'))
             ->renderLayout();
    }

    public function productGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing')->load($id);

        if (!is_null($id)) {
            if (!is_null($model->getId())) {
                $tempData = $model->getData();
                $tempData['attribute_sets'] = $model->getAttributeSetsIds();
                Mage::helper('M2ePro')->setGlobalValue('temp_data', $tempData);
            } else {
                Mage::helper('M2ePro')->setGlobalValue('temp_data', array());
            }
            Mage::helper('M2ePro')->setGlobalValue('temp_listing_categories',array());
        } else {
            if (!is_null(Mage::helper('M2ePro')->getSessionValue('temp_data'))) {
                Mage::helper('M2ePro')->setGlobalValue(
                    'temp_data', Mage::helper('M2ePro')->getSessionValue('temp_data')
                );
            } else {
                Mage::helper('M2ePro')->setGlobalValue('temp_data', array());
            }
            if (!is_null(Mage::helper('M2ePro')->getSessionValue('temp_listing_categories'))) {
                Mage::helper('M2ePro')->setGlobalValue(
                    'temp_listing_categories', Mage::helper('M2ePro')->getSessionValue('temp_listing_categories')
                );
            } else {
                Mage::helper('M2ePro')->setGlobalValue('temp_listing_categories', array());
            }
        }

        $response = $this->loadLayout()->getLayout()
                         ->createBlock('M2ePro/adminhtml_amazon_listing_product_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    public function getMarketplacesForAccountAction()
    {
        $accountId = $this->getRequest()->getParam('account_id');

        if (is_null($accountId)) {
            exit(json_encode(array()));
        }

        /** @var $account Ess_M2ePro_Model_Amazon_Account */
        $account = Mage::getModel('M2ePro/Amazon_Account')->loadInstance($accountId);

        $marketplacesCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Marketplace');
        $marketplacesCollection->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE)
                               ->setOrder('title', 'ASC');

        if ($marketplacesCollection->getSize() <= 0) {
            exit(json_encode(array()));
        }

        $marketplaces = array();
        foreach ($marketplacesCollection->getItems() as $marketplace) {
            if ($account->isExistMarketplaceItem($marketplace->getId())) {
                $marketplaces[] = array(
                    'code'  => $marketplace->getId(),
                    'label' => $marketplace->getTitle()
                );
            }
        }

        exit(json_encode($marketplaces));
    }

    //#############################################

    public function tryToMoveToListingAction()
    {
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = Mage::helper('M2ePro/Component_Amazon')
            ->getModel('Listing')
            ->load($listingId);

        $failedProducts = array();
        foreach ($selectedProducts as $selectedProduct) {
            $listingProductInstance = Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Listing_Product')
                ->load($selectedProduct);

            if (!$listingInstance->addProduct($listingProductInstance->getData('product_id'),true)) {
                $failedProducts[] = $selectedProduct;
            }
        }

        count($failedProducts) == 0 && exit(json_encode(array(
            'result' => 'success'
        )));

        exit(json_encode(array(
            'result' => 'fail',
            'failed_products' => $failedProducts
        )));
    }

    public function moveToListingAction()
    {
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = Mage::helper('M2ePro/Component_Amazon')
            ->getModel('Listing')
            ->load($listingId);

        $logModel = Mage::getModel('M2ePro/Listing_Log');
        $logModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);

        $errors = 0;
        foreach ($selectedProducts as $listingProductId) {

            $listingProductInstance = Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Listing_Product')
                ->load($listingProductId);

            if ($listingInstance->hasProduct($listingProductInstance->getData('product_id'))) {

                $logModel->addProductMessage( $listingProductInstance->getData('listing_id'),
                                              $listingProductInstance->getData('product_id'),
                                              Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                                              NULL,
                                              Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_TO_LISTING,
                                              // Parser hack -> Mage::helper('M2ePro')->__('Item was not moved');
                                              'Item was not moved',
                                              Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                              Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                $errors++;
                continue;
            }

            if ($listingInstance->isHideProductsOthersListings()) {

                $table = Mage::getResourceModel('M2ePro/Listing_Product')->getMainTable();
                $dbSelect = Mage::getResourceModel('core/config')->getReadConnection()
                    ->select()
                    ->from($table,new Zend_Db_Expr('DISTINCT `product_id`'))
                    ->where('`product_id` = ?',(int)$listingProductInstance->getData('product_id'))
                    ->where('`component_mode` = ?',(string)$listingInstance->getComponentMode());

                $productArray = Mage::getResourceModel('core/config')
                    ->getReadConnection()
                    ->fetchCol($dbSelect);

                if (count($productArray) > 0) {

                    $logModel->addProductMessage( $listingProductInstance->getData('listing_id'),
                                                  $listingProductInstance->getData('product_id'),
                                                  Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                                                  NULL,
                                                  Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_TO_LISTING,
                                                  // Parser hack -> Mage::helper('M2ePro')->__('Item was not moved');
                                                  'Item was not moved',
                                                  Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                    $errors++;
                    continue;
                }
            }

            if ($listingProductInstance->isLockedObject() ||
                $listingProductInstance->isLockedObject('in_action')) {

                $logModel->addProductMessage( $listingProductInstance->getData('listing_id'),
                                              $listingProductInstance->getData('product_id'),
                                              Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                                              NULL,
                                              Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_TO_LISTING,
                                              // Parser hack -> Mage::helper('M2ePro')->__('Item was not moved');
                                              'Item was not moved',
                                              Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                              Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                $errors++;
                continue;
            }

            $logModel->addProductMessage(
                $listingId,
                $listingProductInstance->getData('product_id'),
                Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                NULL,
                Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_TO_LISTING,
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully moved');
                'Item was successfully moved',
                Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            $logModel->addProductMessage(
                $listingProductInstance->getData('listing_id'),
                $listingProductInstance->getData('product_id'),
                Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                NULL,
                Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_TO_LISTING,
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully moved');
                'Item was successfully moved',
                Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            $listingProductInstance->setData('listing_id', $listingId)->save();
        };

        ($errors == 0)
            ? exit(json_encode(array('result'=>'success')))
            : exit(json_encode(array('result'=>'error',
                                     'errors'=>$errors)));

    }

    //#############################################

    protected function processConnector($action, array $params = array())
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select products';
        }

        $params['status_changer'] = Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_USER;

        $listingsProductsIds = explode(',', $listingsProductsIds);

        $dispatcherObject = Mage::getModel('M2ePro/Amazon_Connector')->getProductDispatcher();
        $result = (int)$dispatcherObject->process($action, $listingsProductsIds, $params);
        $actionId = (int)$dispatcherObject->getLogsActionId();

        $listingProductObject = Mage::helper('M2ePro/Component_Amazon')
                                        ->getModel('Listing_Product')
                                        ->load($listingsProductsIds[0]);

        $isProcessingItems = false;
        if (!is_null($listingProductObject->getId())) {
            $isProcessingItems = (bool)$listingProductObject->getListing()
                                    ->isLockedObject('products_in_action');
        }

        if ($result == Ess_M2ePro_Model_Amazon_Connector_Product_Requester::STATUS_ERROR) {
            return json_encode(
                array('result'=>'error','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems)
            );
        }

        if ($result == Ess_M2ePro_Model_Amazon_Connector_Product_Requester::STATUS_WARNING) {
            return json_encode(
                array('result'=>'warning','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems)
            );
        }

        if ($result == Ess_M2ePro_Model_Amazon_Connector_Product_Requester::STATUS_SUCCESS) {
            return json_encode(
                array('result'=>'success','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems)
            );
        }

        return json_encode(
            array('result'=>'error','action_id'=>$actionId,'is_processing_items'=>$isProcessingItems)
        );
    }

    //---------------------------------------------

    public function runListProductsAction()
    {
        exit($this->processConnector(Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_LIST));
    }

    public function runReviseProductsAction()
    {
        exit($this->processConnector(Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_REVISE));
    }

    public function runRelistProductsAction()
    {
        exit($this->processConnector(Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_RELIST));
    }

    public function runStopProductsAction()
    {
        exit($this->processConnector(Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_STOP));
    }

    public function runStopAndRemoveProductsAction()
    {
        exit($this->processConnector(
            Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_STOP, array('remove' => true)
        ));
    }

    //#############################################

    public function getSuggestedAsinGridAction()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            exit('ERROR: No product id!');
        }

        /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
        $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',$productId);

        $marketplaceId = $listingProduct->getGeneralTemplate()->getMarketplaceId();

        $suggestedData = $listingProduct->getData('general_id_search_suggest_data');
        if (!empty($suggestedData)) {
            Mage::helper('M2ePro')->setGlobalValue('product_id',$productId);
            Mage::helper('M2ePro')->setGlobalValue('is_suggestion',true);
            Mage::helper('M2ePro')->setGlobalValue('marketplace_id',$marketplaceId);
            Mage::helper('M2ePro')->setGlobalValue('temp_data',@json_decode($suggestedData,true));
            $response = $this->loadLayout()->getLayout()
                             ->createBlock('M2ePro/adminhtml_amazon_listing_productSearch_grid')->toHtml();
        } else {
            $response = Mage::helper('M2ePro')->__('NO DATA');
        }

        $this->getResponse()->setBody($response);
    }

    //--------------------------------------------

    public function searchAsinManualAction()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $query = $this->getRequest()->getParam('query');

        if (empty($productId)) {
            exit('No product_id!');
        }

        /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
        $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',$productId);
        $marketplaceObj = $listingProduct->getGeneralTemplate()->getMarketplace();
        $accountObj = $listingProduct->getGeneralTemplate()->getAccount();

        $tempCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
        $tempCollection->getSelect()->join(
            array('l'=>Mage::getResourceModel('M2ePro/Listing')->getMainTable()),
            '`main_table`.`listing_id` = `l`.`id`',
            null
        );
        $tempCollection->getSelect()->join(
            array('tg'=>Mage::getResourceModel('M2ePro/Template_General')->getMainTable()),
            '`l`.`template_general_id` = `tg`.`id`',
            null
        );
        $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_PROCESSING;
        $tempCollection->addFieldToFilter('general_id_search_status',$temp)
                       ->addFieldToFilter('tg.marketplace_id', $marketplaceObj->getId())
                       ->addFieldToFilter('tg.account_id', $accountObj->getId());

        $isThereActiveSearch = $tempCollection->getSize();

        $message  = Mage::helper('M2ePro')->__('There is another active automatic ASIN/ISBN search task ');
        $message .= Mage::helper('M2ePro')->__('for some item(s). Please wait until this search process is finished.');

        if ($isThereActiveSearch) {
            $response = array('result' => 'error','data' => $message);
            exit(json_encode($response));
        }

        $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_NONE;
        if ($listingProduct->isNotListed() &&
            !$listingProduct->isLockedObject('in_action') &&
            !$listingProduct->getData('category_id') && !$listingProduct->getData('general_id') &&
            $listingProduct->getData('general_id_search_status') == $temp) {

            $marketplaceObj = $listingProduct->getGeneralTemplate()->getMarketplace();
            $accountObj = $listingProduct->getGeneralTemplate()->getAccount();

            /** @var $dispatcher Ess_M2ePro_Model_Amazon_Search_Dispatcher */
            $dispatcher = Mage::getModel('M2ePro/Amazon_Search_Dispatcher');
            $result = $dispatcher->runManual($listingProduct,$query,$marketplaceObj,$accountObj);

            $message = Mage::helper('M2ePro')->__('Server is currently unavailable. Please try again later.');
            if ($result === false) {
                $response = array('result' => 'error','data' => $message);
                exit(json_encode($response));
            }

            Mage::helper('M2ePro')->setGlobalValue('temp_data',$result);
            Mage::helper('M2ePro')->setGlobalValue('product_id',$productId);
            Mage::helper('M2ePro')->setGlobalValue('marketplace_id',$marketplaceObj->getId());
        } else {
            Mage::helper('M2ePro')->setGlobalValue('temp_data',array());
        }

        $data = $this->loadLayout()->getLayout()
                     ->createBlock('M2ePro/adminhtml_amazon_listing_productSearch_grid')->toHtml();

        $response = array(
            'result' => 'success',
            'data' => $data
        );

        exit(json_encode($response));
    }

    public function searchAsinAutoAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            exit ('You should select one or more products');
        }

        $productIds = explode(',', $productIds);

        $productsToSearch = array();
        foreach ($productIds as $productId) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',$productId);

            $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_NONE;
            if ($listingProduct->isNotListed() &&
                !$listingProduct->isLockedObject('in_action') &&
                !$listingProduct->getData('category_id') && !$listingProduct->getData('general_id') &&
                $listingProduct->getData('general_id_search_status') == $temp) {

                $productsToSearch[] = $listingProduct;
            }
        }

        if (!empty($productsToSearch)) {
            /** @var $dispatcher Ess_M2ePro_Model_Amazon_Search_Dispatcher */
            $dispatcher = Mage::getModel('M2ePro/Amazon_Search_Dispatcher');
            $result = $dispatcher->runAutomatic($productsToSearch);

            if ($result === false) {
                exit('1');
            }
        }

        exit('0');
    }

    //--------------------------------------------

    public function mapToAsinAction()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $generalId = $this->getRequest()->getParam('general_id');

        if (empty($productId) || empty($generalId) ||
           (!Mage::helper('M2ePro/Component_Amazon')->isASIN($generalId) &&
            !Mage::helper('M2ePro/Component_Amazon')->isISBN($generalId))) {
            exit('You should provide correct parameters.');
        }

        /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
        $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',$productId);

        $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_NONE;
        if ($listingProduct->isNotListed() &&
            !$listingProduct->isLockedObject('in_action') &&
            !$listingProduct->getData('category_id') &&
            $listingProduct->getData('general_id_search_status') == $temp) {

            $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_SET_MANUAL;
            $listingProduct->setData('general_id',$generalId);
            $listingProduct->setData('category_id',NULL);
            $listingProduct->setData('general_id_search_status',$temp);
            $listingProduct->setData('general_id_search_suggest_data',NULL);

            $listingProduct->save();
        }
        exit('0');
    }

    public function unmapFromAsinAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            exit('You should provide correct parameters.');
        }

        $productIds = explode(',', $productIds);

        foreach ($productIds as $productId) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',$productId);

            if ($listingProduct->isNotListed() &&
                !$listingProduct->isLockedObject('in_action')) {

                $temp = Ess_M2ePro_Model_Amazon_Listing_Product::GENERAL_ID_SEARCH_STATUS_NONE;
                $listingProduct->setData('general_id',NULL);
                $listingProduct->setData('category_id',NULL);
                $listingProduct->setData('general_id_search_status',$temp);
                $listingProduct->setData('general_id_search_suggest_data',NULL);

                $listingProduct->save();
            }
        }

        exit('0');
    }

    //#############################################

    public function confirmTutorialAction()
    {
        Mage::helper('M2ePro/Module')->getConfig()->setGroupValue('/cache/', 'amazon_listing_tutorial_showed',1);
        $this->_redirect('*/adminhtml_listing/index',
            array('tab' => Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON));
    }

    //#############################################
}
