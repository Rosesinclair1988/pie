<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Listing_View extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonListingView');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_amazon_listing_view';
        //------------------------------

        // Set header text
        //------------------------------
        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = ' ' . Ess_M2ePro_Helper_Component_Amazon::TITLE;
        } else {
            $componentName = '';
        }

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');
        $headerText = Mage::helper('M2ePro')->__("View%component_name% Listing \"%title%\"");
        $this->_headerText = str_replace(array('%title%','%component_name%'),
                                         array($this->escapeHtml($listingData['title']),$componentName),
                                         $headerText);
        //------------------------------

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        if (!is_null($this->getRequest()->getParam('back'))) {

            $this->_addButton('back', array(
                'label'   => Mage::helper('M2ePro')->__('Back'),
                'onclick' => 'CommonHandlerObj.back_click(\''
                             .Mage::helper('M2ePro')->getBackUrl('*/adminhtml_listing/index')
                             .'\')',
                'class'   => 'back'
            ));
        }

        $this->_addButton('goto_listings', array(
            'label'   => Mage::helper('M2ePro')->__('Listings'),
            'onclick' => 'setLocation(\''
                         .$this->getUrl('*/adminhtml_listing/index',
                                        array('tab' => Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_AMAZON))
                         .'\')',
            'class'   => 'button_link'
        ));

        $this->_addButton('view_log', array(
            'label'   => Mage::helper('M2ePro')->__('View Log'),
            'onclick' => 'setLocation(\''
                         .$this->getUrl('*/adminhtml_log/listing',
                                        array('id'=>$listingData['id'],
                                              'back'=>Mage::helper('M2ePro')
                                                  ->makeBackUrlParam('*/adminhtml_amazon_listing/view',
                                                                     array('id'=>$listingData['id']))))
                         .'\')',
            'class'   => 'button_link'
        ));

        $this->_addButton('reset', array(
            'label'   => Mage::helper('M2ePro')->__('Refresh'),
            'onclick' => 'ListingItemGridHandlerObj.reset_click()',
            'class'   => 'reset'
        ));

        $newListing = $this->getRequest()->getParam('new');
        $tempStr = Mage::helper('M2ePro')->__('Are you sure?');

        if (is_null($newListing)) {

            $this->_addButton('clear_log', array(
                'label'   => Mage::helper('M2ePro')->__('Clear Log'),
                'onclick' => 'deleteConfirm(\''
                             .$tempStr.'\', \''
                             . $this->getUrl('*/adminhtml_listing/clearLog',
                                             array('id'=>$listingData['id'],
                                                   'back'=>Mage::helper('M2ePro')
                                                            ->makeBackUrlParam('*/adminhtml_amazon_listing/view',
                                                                               array('id'=>$listingData['id']))))
                             .'\')',
                'class'   => 'clear_log'
            ));
        }

        $this->_addButton('delete', array(
            'label'   => Mage::helper('M2ePro')->__('Delete'),
            'onclick' => 'deleteConfirm(\''
                         .$tempStr
                         .'\', \''
                         .$this->getUrl('*/adminhtml_'.Ess_M2ePro_Helper_Component_Amazon::NICK.'_listing/delete',
                                        array('id'=>$listingData['id']))
                         .'\')',
            'class'   => 'delete'
        ));

        $this->_addButton('edit_templates', array(
            'label'   => Mage::helper('M2ePro')->__('Edit Templates'),
            'onclick' => '',
            'class'   => 'drop_down edit_template_drop_down'
        ));

        $this->_addButton('edit_settings', array(
            'label'     => Mage::helper('M2ePro')->__('Edit Settings'),
            'onclick'   => 'window.open(\''
                           .$this->getUrl('*/adminhtml_amazon_listing/edit',
                                          array('id'=>$listingData['id'],
                                                'back'=>Mage::helper('M2ePro')
                                                            ->makeBackUrlParam('view',
                                                                               array('id'=>$listingData['id']))))
                           .'\',\'_blank\')',
            'class'     => ''
        ));

        $this->_addButton('add_products', array(
            'label'     => Mage::helper('M2ePro')->__('Add Products'),
            'onclick'   => 'setLocation(\''
                           .$this->getUrl('*/adminhtml_amazon_listing/product',
                                          array('id'=>$listingData['id'],
                                                'back'=>Mage::helper('M2ePro')
                                                            ->makeBackUrlParam('view',
                                                                               array('id'=>$listingData['id']))))
                           .'\')',
            'class'     => 'add'
        ));
        //------------------------------
    }

    protected function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>' .
               '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
               '<div id="listing_view_content_container">'.
               parent::_toHtml() .
               '</div>';
    }

    public function getGridHtml()
    {
        $temp = Mage::helper('M2ePro')->getSessionValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        $model = 'Listing_Product';
        $block = 'listing_moveToListing_failedProducts';
        $componentMode = Ess_M2ePro_Helper_Component_Amazon::NICK;
        $gridId = $componentMode . 'ListingViewGrid' . $listingData['id'];
        $ignoreListings = json_encode(array($listingData['id']));

        $logViewUrl = $this->getUrl('*/adminhtml_log/listing',
                                    array('id'=>$listingData['id'],
                                          'back'=>Mage::helper('M2ePro')
                                                    ->makeBackUrlParam('*/adminhtml_amazon_listing/view',
                                                                       array('id'=>$listingData['id']))));
        $checkLockListing = $this->getUrl('*/adminhtml_listing/checkLockListing',
                                          array('component'=>Ess_M2ePro_Helper_Component_Amazon::NICK));
        $lockListingNow = $this->getUrl('*/adminhtml_listing/lockListingNow',
                                        array('component'=>Ess_M2ePro_Helper_Component_Amazon::NICK));
        $unlockListingNow = $this->getUrl('*/adminhtml_listing/unlockListingNow',
                                          array('component'=>Ess_M2ePro_Helper_Component_Amazon::NICK));
        $getErrorsSummary = $this->getUrl('*/adminhtml_listing/getErrorsSummary');

        $runListProducts = $this->getUrl('*/adminhtml_amazon_listing/runListProducts');
        $runReviseProducts = $this->getUrl('*/adminhtml_amazon_listing/runReviseProducts');
        $runRelistProducts = $this->getUrl('*/adminhtml_amazon_listing/runRelistProducts');
        $runStopProducts = $this->getUrl('*/adminhtml_amazon_listing/runStopProducts');
        $runStopAndRemoveProducts = $this->getUrl('*/adminhtml_amazon_listing/runStopAndRemoveProducts');

        $prepareData = $this->getUrl('*/adminhtml_listing/prepareMoveToListing');
        $getMoveToListingGridHtml = $this->getUrl('*/adminhtml_listing/moveToListingGrid');
        $getFailedProductsGridHtml = $this->getUrl('*/adminhtml_listing/getFailedProductsGrid');
        $tryToMoveToListing = $this->getUrl('*/adminhtml_amazon_listing/tryToMoveToListing');
        $moveToListing = $this->getUrl('*/adminhtml_amazon_listing/moveToListing');

        $tempDropDownHtml = Mage::helper('M2ePro')->escapeJs($this->getEditTemplateDropDownHtml());

        $popupTitle = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Moving Amazon Items.'));
        $failedProductsPopupTitle = Mage::helper('M2ePro')
                                        ->escapeJs(Mage::helper('M2ePro')
                                        ->__('Products failed to move'));

        $taskCompletedMessage = Mage::helper('M2ePro')
                                    ->escapeJs(Mage::helper('M2ePro')
                                    ->__('Task completed. Please wait ...'));
        $taskCompletedSuccessMessage = Mage::helper('M2ePro')
                                        ->escapeJs(Mage::helper('M2ePro')
                                        ->__('"%title%" task has successfully submitted to be processed.'));
        $taskCompletedWarningMessage = Mage::helper('M2ePro')
                         ->escapeJs(Mage::helper('M2ePro')
                         ->__('"%title%" task has completed with warnings. <a href="%url%">View log</a> for details.'));
        $taskCompletedErrorMessage = Mage::helper('M2ePro')
                         ->escapeJs(Mage::helper('M2ePro')
                         ->__('"%title%" task has completed with errors. <a href="%url%">View log</a> for details.'));

        $lockedObjNoticeMessage = Mage::helper('M2ePro')
                                    ->escapeJs(Mage::helper('M2ePro')
                                    ->__('Some Amazon request(s) are being processed now.'));
        $sendingDataToAmazonMessage = Mage::helper('M2ePro')
                                        ->escapeJs(Mage::helper('M2ePro')
                                        ->__('Sending %execute% product(s) data on Amazon.'));
        $viewAllProductLogMessage = Mage::helper('M2ePro')
                                        ->escapeJs(Mage::helper('M2ePro')
                                        ->__('View All Product Log.'));

        $listingLockedMessage = Mage::helper('M2ePro')
                                    ->escapeJs(Mage::helper('M2ePro')
                                    ->__('The listing was locked by another process. Please try again later.'));
        $listingEmptyMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Listing is empty.'));

        $listingAllItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                        ->__('Listing All Items On Amazon'));
        $listingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                             ->__('Listing Selected Items On Amazon'));
        $revisingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                              ->__('Revising Selected Items On Amazon'));
        $relistingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                               ->__('Relisting Selected Items On Amazon'));
        $stoppingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                              ->__('Stopping Selected Items On Amazon'));
        $stoppingAndRemovingSelectedItemsMessage = Mage::helper('M2ePro')
                                                    ->escapeJs(Mage::helper('M2ePro')
                                                    ->__('Stopping And Removing Selected Items On Amazon'));

        $successfullyMovedMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')
                                                          ->__('Product(s) was successfully moved.'));
        $productsWereNotMovedMessage = Mage::helper('M2ePro')
                                          ->escapeJs(Mage::helper('M2ePro')
                                          ->__('Product(s) was not moved. <a href="%url%">View log</a> for details.'));
        $someProductsWereNotMovedMessage = Mage::helper('M2ePro')
                                   ->escapeJs(Mage::helper('M2ePro')
                                   ->__('Some product(s) was not moved. <a href="%url%">View log</a> for details.'));

        $selectItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Please select items.'));
        $selectActionMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Please select action.'));

        $successWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Success'));
        $noticeWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Notice'));
        $warningWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Warning'));
        $errorWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Error'));
        $closeWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Close'));

        $textConfirm = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Are you sure?'));

        $searchAsinManual = $this->getUrl('*/adminhtml_amazon_listing/searchAsinManual');
        $suggestedAsinGridHmtl = $this->getUrl('*/adminhtml_amazon_listing/getSuggestedAsinGrid');
        $searchAsinAuto = $this->getUrl('*/adminhtml_amazon_listing/searchAsinAuto');
        $mapToAsin = $this->getUrl('*/adminhtml_amazon_listing/mapToAsin');
        $unmapFromAsin = $this->getUrl('*/adminhtml_amazon_listing/unmapFromAsin');

        $enterProductSearchQueryMessage = Mage::helper('M2ePro')
                                            ->escapeJs(Mage::helper('M2ePro')
                                            ->__('Please enter product name or ASIN/ISBN/UPC/EAN.'));
        $autoMapAsinProgressTitle = Mage::helper('M2ePro')
                                        ->escapeJs(Mage::helper('M2ePro')
                                        ->__('Assign ASIN/ISBN to Item(s)'));
        $autoMapAsinErrorMessage = Mage::helper('M2ePro')
                                       ->escapeJs(Mage::helper('M2ePro')
                                       ->__('Server is currently unavailable. Please try again later.'));

        $javascriptsMain = <<<JAVASCRIPT
<script type="text/javascript">

    if (typeof M2ePro == 'undefined') {
        M2ePro = {};
        M2ePro.url = {};
        M2ePro.formData = {};
        M2ePro.customData = {};
        M2ePro.text = {};
    }

    M2ePro.productsIdsForList = '{$productsIdsForList}';

    M2ePro.url.logViewUrl = '{$logViewUrl}';
    M2ePro.url.checkLockListing = '{$checkLockListing}';
    M2ePro.url.lockListingNow = '{$lockListingNow}';
    M2ePro.url.unlockListingNow = '{$unlockListingNow}';
    M2ePro.url.getErrorsSummary = '{$getErrorsSummary}';

    M2ePro.url.runListProducts = '{$runListProducts}';
    M2ePro.url.runReviseProducts = '{$runReviseProducts}';
    M2ePro.url.runRelistProducts = '{$runRelistProducts}';
    M2ePro.url.runStopProducts = '{$runStopProducts}';
    M2ePro.url.runStopAndRemoveProducts = '{$runStopAndRemoveProducts}';

    M2ePro.url.searchAsinManual = '{$searchAsinManual}';
    M2ePro.url.searchAsinAuto = '{$searchAsinAuto}';
    M2ePro.url.suggestedAsinGrid = '{$suggestedAsinGridHmtl}';
    M2ePro.url.mapToAsin = '{$mapToAsin}';
    M2ePro.url.unmapFromAsin = '{$unmapFromAsin}';

    M2ePro.url.prepareData = '{$prepareData}';
    M2ePro.url.getGridHtml = '{$getMoveToListingGridHtml}';
    M2ePro.url.getFailedProductsGridHtml = '{$getFailedProductsGridHtml}';
    M2ePro.url.tryToMoveToListing = '{$tryToMoveToListing}';
    M2ePro.url.moveToListing = '{$moveToListing}';

    M2ePro.text.popup_title = '{$popupTitle}';
    M2ePro.text.failed_products_popup_title = '{$failedProductsPopupTitle}';

    M2ePro.text.task_completed_message = '{$taskCompletedMessage}';
    M2ePro.text.task_completed_success_message = '{$taskCompletedSuccessMessage}';
    M2ePro.text.task_completed_warning_message = '{$taskCompletedWarningMessage}';
    M2ePro.text.task_completed_error_message = '{$taskCompletedErrorMessage}';

    M2ePro.text.locked_obj_notice = '{$lockedObjNoticeMessage}';
    M2ePro.text.sending_data_message = '{$sendingDataToAmazonMessage}';
    M2ePro.text.view_all_product_log_message = '{$viewAllProductLogMessage}';

    M2ePro.text.listing_locked_message = '{$listingLockedMessage}';
    M2ePro.text.listing_empty_message = '{$listingEmptyMessage}';

    M2ePro.text.listing_all_items_message = '{$listingAllItemsMessage}';
    M2ePro.text.listing_selected_items_message = '{$listingSelectedItemsMessage}';
    M2ePro.text.revising_selected_items_message = '{$revisingSelectedItemsMessage}';
    M2ePro.text.relisting_selected_items_message = '{$relistingSelectedItemsMessage}';
    M2ePro.text.stopping_selected_items_message = '{$stoppingSelectedItemsMessage}';
    M2ePro.text.stopping_and_removing_selected_items_message = '{$stoppingAndRemovingSelectedItemsMessage}';

    M2ePro.text.successfully_moved = '{$successfullyMovedMessage}';
    M2ePro.text.products_were_not_moved = '{$productsWereNotMovedMessage}';
    M2ePro.text.some_products_were_not_moved = '{$someProductsWereNotMovedMessage}';

    M2ePro.text.select_items_message = '{$selectItemsMessage}';
    M2ePro.text.select_action_message = '{$selectActionMessage}';

    M2ePro.text.success_word = '{$successWord}';
    M2ePro.text.notice_word = '{$noticeWord}';
    M2ePro.text.warning_word = '{$warningWord}';
    M2ePro.text.error_word = '{$errorWord}';
    M2ePro.text.close_word = '{$closeWord}';

    M2ePro.text.confirm = '{$textConfirm}';

    M2ePro.text.enter_productSearch_query = '{$enterProductSearchQueryMessage}';
    M2ePro.text.automap_asin_progress_title = '{$autoMapAsinProgressTitle}';
    M2ePro.text.automap_error_message = '{$autoMapAsinErrorMessage}';

    M2ePro.customData.block = '{$block}';
    M2ePro.customData.model = '{$model}';
    M2ePro.customData.componentMode = '{$componentMode}';
    M2ePro.customData.gridId = '{$gridId}';
    M2ePro.customData.ignoreListings = '{$ignoreListings}';

    Event.observe(window, 'load', function() {
        ListingActionHandlerObj = new ListingActionHandler(M2ePro,{$listingData['id']});
        AmazonListingMoveToListingHandlerObj = new ListingMoveToListingHandler(M2ePro);
        AmazonListingProductSearchHandlerObj = new AmazonListingProductSearchHandler(M2ePro);
        ListingItemGridHandlerObj = new ListingItemGridHandler( M2ePro,
                                                                'amazonListingViewGrid{$listingData['id']}',
                                                                1,
                                                                2,
                                                                ListingActionHandlerObj,
                                                                AmazonListingMoveToListingHandlerObj,
                                                                AmazonListingProductSearchHandlerObj );
        ListingItemGridHandlerObj.setMaxProductsInPart(1000,0);
        ListingProgressBarObj = new ProgressBar('listing_view_progress_bar');
        GridWrapperObj = new AreaWrapper('listing_view_content_container');

        $$('.edit_template_drop_down')[0].innerHTML += '{$tempDropDownHtml}';

        DropDownObj = new DropDown();
        DropDownObj.prepare($$('.edit_template_drop_down')[0]);

        if (M2ePro.productsIdsForList) {
            eval(ListingItemGridHandlerObj.gridId+'_massactionJsObject.checkedString = M2ePro.productsIdsForList;');
            $$('select#'+ListingItemGridHandlerObj.gridId+'_massaction-select option')[1].selected = 'selected';
            ListingItemGridHandlerObj.massactionSubmitClick(true);
        }
    });

</script>
JAVASCRIPT;

        $helpBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_view_help');
        $productSearchMenuBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_productSearch_menu');
        $productSearchBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_productSearch_main');

        return $javascriptsMain .
               $helpBlock->toHtml() .
               $productSearchMenuBlock->toHtml() .
               $productSearchBlock->toHtml() .
               parent::getGridHtml();
    }

    public function getEditTemplateDropDownHtml()
    {
        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');
        $sellingFormatTemplate = Mage::helper('M2ePro')->__('Selling Format Template');
        $descriptionTemplate = Mage::helper('M2ePro')->__('Description Template');
        $synchronizationTemplate = Mage::helper('M2ePro')->__('Synchronization Template');

        return <<<HTML
<ul style="display: none;">
    <li href="{$this->getUrl('*/adminhtml_amazon_template_sellingFormat/edit',
                             array('id'=>$listingData['template_selling_format_id']))}"
        target="_blank">{$sellingFormatTemplate}</li>
    <li href="{$this->getUrl('*/adminhtml_amazon_template_description/edit',
                             array('id'=>$listingData['template_description_id']))}"
        target="_blank">{$descriptionTemplate}</li>
    <li href="{$this->getUrl('*/adminhtml_amazon_template_synchronization/edit',
                             array('id'=>$listingData['template_synchronization_id']))}"
        target="_blank">{$synchronizationTemplate}</li>
</ul>
HTML;
    }

}
