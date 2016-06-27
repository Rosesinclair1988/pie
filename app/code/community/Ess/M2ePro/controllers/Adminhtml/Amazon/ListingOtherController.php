<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Amazon_ListingOtherController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/listings')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Manage Listings'))
             ->_title(Mage::helper('M2ePro')->__('3rd Party Listings'));

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/listings/listing_other');
    }

    //#############################################

    public function indexAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('*/adminhtml_listingOther/index');
        }

        /** @var $block Ess_M2ePro_Block_Adminhtml_Listing_Other */
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_listing_other');
        $block->disableEbayTab();

        $this->getResponse()->setBody($block->getAmazonTabHtml());
    }

    public function gridAction()
    {
        $response = $this->loadLayout()->getLayout()
                         ->createBlock('M2ePro/adminhtml_amazon_listing_other_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    public function mapToProductAction()
    {
        $productId = $this->getRequest()->getPost('productId');
        $sku = $this->getRequest()->getPost('sku');
        $productOtherId = $this->getRequest()->getPost('otherProductId');

        if ((!$productId && !$sku) || !$productOtherId) {
            exit();
        }

        $collection = Mage::getModel('catalog/product')->getCollection()
            ->joinField('qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left');

        $productId && $collection->addFieldToFilter('entity_id', $productId);
        $sku && $collection->addFieldToFilter('sku', $sku);

        $tempData = $collection->getSelect()->query()->fetch();
        $tempData || exit('1');

        //TODO temporarily type simple filter
        $magentoProduct = Mage::getModel('M2ePro/Magento_Product')->loadProduct($tempData['entity_id']);
        $magentoProduct->isProductWithVariations() && exit('2');

        $productId || $productId = $tempData['entity_id'];
        $productOtherInstance = Mage::helper('M2ePro/Component_Amazon')
            ->getModel('Listing_Other')
            ->load($productOtherId);
        $productOtherInstance->addData(array('product_id'=>$productId))->save();

        $dataForAdd = array(
            'account_id' => $productOtherInstance->getData('account_id'),
            'marketplace_id' => $productOtherInstance->getData('marketplace_id'),
            'sku' => $productOtherInstance->getData('sku'),
            'product_id' => $productOtherInstance->getData('product_id'),
            'store_id' => $productOtherInstance->getChildObject()->getRelatedStoreId()
        );

        Mage::getModel('M2ePro/Amazon_Item')->setData($dataForAdd)->save();

        $logModel = Mage::getModel('M2ePro/Listing_Other_Log');
        $logModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);
        $logModel->addProductMessage($productOtherInstance->getId(),
                                     Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                                     NULL,
                                     Ess_M2ePro_Model_Listing_Other_Log::ACTION_MAP_LISTING,
                                     // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully mapped');
                                     'Item was successfully mapped',
                                     Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                                     Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

        exit('0');
    }

    public function mapAutoToProductAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            exit ('You should select one or more products');
        }

        $productIds = explode(',', $productIds);

        $productsForMapping = array();
        foreach ($productIds as $productId) {

            /** @var $listingOther Ess_M2ePro_Model_Listing_Other */
            $listingOther = Mage::helper('M2ePro/Component_Amazon')
                                    ->getObject('Listing_Other',$productId);

            if ($listingOther->getProductId()) {
                continue;
            }

            $productsForMapping[] = $listingOther;
        }

        /** @var $mappingModel Ess_M2ePro_Model_Amazon_Listing_Other_Mapping */
        $mappingModel = Mage::getModel('M2ePro/Amazon_Listing_Other_Mapping');
        $mappingModel->initialize();
        $mappingModel->autoMapOtherListingsProducts($productsForMapping);
    }

    public function tryToMoveToListingAction()
    {
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = Mage::helper('M2ePro/Component_Amazon')
            ->getModel('Listing')
            ->load($listingId);

        $failedProducts = array();
        foreach ($selectedProducts as $selectedProduct) {
            $otherListingProductInstance = Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Listing_Other')
                ->load($selectedProduct);

            if (!$listingInstance->addProduct($otherListingProductInstance->getData('product_id'),true)) {
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

        $otherLogModel = Mage::getModel('M2ePro/Listing_Other_Log');
        $otherLogModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);

        $listingLogModel = Mage::getModel('M2ePro/Listing_Log');
        $listingLogModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);

        $errors = 0;
        foreach ($selectedProducts as $otherListingProduct) {

            $otherListingProductInstance = Mage::helper('M2ePro/Component_Amazon')
                ->getModel('Listing_Other')
                ->load($otherListingProduct);

            $listingProductInstance = $listingInstance
                ->addProduct($otherListingProductInstance->getData('product_id'));

            if (!($listingProductInstance instanceof Ess_M2ePro_Model_Listing_Product)) {

                $otherLogModel->addProductMessage(
                    $otherListingProductInstance->getId(),
                    Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                    NULL,
                    Ess_M2ePro_Model_Listing_Other_Log::ACTION_MOVE_LISTING,
                    // Parser hack -> Mage::helper('M2ePro')->__('Product already exists in M2E listing(s).');
                    'Product already exists in M2E listing(s).',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                $errors++;
                continue;
            }

            $dataForUpdate = array(
                'item_id' => $otherListingProductInstance->getChildObject()->getItemId(),
                'general_id' => $otherListingProductInstance->getChildObject()->getGeneralId(),
                'sku' => $otherListingProductInstance->getChildObject()->getSku(),
                'online_price' => $otherListingProductInstance->getChildObject()->getOnlinePrice(),
                'online_qty' => $otherListingProductInstance->getChildObject()->getOnlineQty(),
                'is_afn_channel' => (int)$otherListingProductInstance->getChildObject()->isAfnChannel(),
                'is_isbn_general_id' => (int)$otherListingProductInstance->getChildObject()->isIsbnGeneralId(),
                'start_date' => $otherListingProductInstance->getChildObject()->getStartDate(),
                'end_date' => $otherListingProductInstance->getChildObject()->getEndDate(),
                'status' => $otherListingProductInstance->getStatus(),
                'status_changer' => Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_COMPONENT
            );

            $listingProductInstance->addData($dataForUpdate)->save();

            $otherLogModel->addProductMessage(
                $otherListingProductInstance->getId(),
                Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                NULL,
                Ess_M2ePro_Model_Listing_Other_Log::ACTION_MOVE_LISTING,
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully moved');
                'Item was successfully moved',
                Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            $listingLogModel->addProductMessage(
                $listingId,
                $otherListingProductInstance->getProductId(),
                Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER,
                NULL,
                Ess_M2ePro_Model_Listing_Log::ACTION_MOVE_FROM_OTHER_LISTING,
                // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully moved');
                'Item was successfully moved',
                Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            $otherListingProductInstance->deleteInstance();
        };

        ($errors == 0)
            ? exit(json_encode(array('result'=>'success')))
            : exit(json_encode(array('result'=>'error',
                                     'errors'=>$errors)));
    }

    //#############################################
}
