<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_View extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayListingView');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_ebay_listing_view';
        //------------------------------

        // Set header text
        //------------------------------
        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = ' ' . Ess_M2ePro_Helper_Component_Ebay::TITLE;
        } else {
            $componentName = '';
        }

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');
        $headerText = Mage::helper('M2ePro')->__("View%component_name% Listing \"%title%\"");
        $this->_headerText = str_replace(array('%title%','%component_name%'),
                                         array($this->escapeHtml($listingData['title']),$componentName), $headerText);
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

            $url = Mage::helper('M2ePro')->getBackUrl('*/adminhtml_listing/index');
            $this->_addButton('back', array(
                'label'     => Mage::helper('M2ePro')->__('Back'),
                'onclick'   => 'CommonHandlerObj.back_click(\''.$url.'\')',
                'class'     => 'back'
            ));
        }

        $url = $this->getUrl('*/adminhtml_listing/index',array(
            'tab' => Ess_M2ePro_Block_Adminhtml_Component_Abstract::TAB_ID_EBAY
        ));
        $this->_addButton('goto_listings', array(
            'label'     => Mage::helper('M2ePro')->__('Listings'),
            'onclick'   => 'setLocation(\''.$url.'\')',
            'class'     => 'button_link'
        ));

        $url = $this->getUrl('*/adminhtml_log/listing',array(
            'id'=>$listingData['id'],
            'back'=>Mage::helper('M2ePro')->makeBackUrlParam('*/adminhtml_ebay_listing/view',array(
                'id'=>$listingData['id']
            ))
        ));
        $this->_addButton('view_log', array(
            'label'     => Mage::helper('M2ePro')->__('View Log'),
            'onclick'   => 'setLocation(\'' .$url.'\')',
            'class'     => 'button_link'
        ));

        $this->_addButton('reset', array(
            'label'     => Mage::helper('M2ePro')->__('Refresh'),
            'onclick'   => 'ListingItemGridHandlerObj.reset_click()',
            'class'     => 'reset'
        ));

        $newListing = $this->getRequest()->getParam('new');
        $tempStr = Mage::helper('adminhtml')->__('Are you sure?');

        if (is_null($newListing)) {
            $url = $this->getUrl('*/adminhtml_listing/clearLog',array(
                'id'=>$listingData['id'],
                'back'=>Mage::helper('M2ePro')->makeBackUrlParam('*/adminhtml_ebay_listing/view',array(
                    'id'=>$listingData['id']
                ))
            ));
            $this->_addButton('clear_log', array(
                'label'     => Mage::helper('M2ePro')->__('Clear Log'),
                'onclick'   => 'deleteConfirm(\''.$tempStr.'\', \'' . $url . '\')',
                'class'     => 'clear_log'
            ));
        }

        $url = $this->getUrl('*/adminhtml_'.Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing/delete',array(
            'id'=>$listingData['id']
        ));
        $this->_addButton('delete', array(
            'label'     => Mage::helper('M2ePro')->__('Delete'),
            'onclick'   => 'deleteConfirm(\''. $tempStr.'\', \'' . $url . '\')',
            'class'     => 'delete'
        ));

        $this->_addButton('edit_templates', array(
            'label'     => Mage::helper('M2ePro')->__('Edit Templates'),
            'onclick'   => '',
            'class'     => 'drop_down edit_template_drop_down'
        ));

        $url = $this->getUrl('*/adminhtml_ebay_listing/edit',array(
            'id'=>$listingData['id'],
            'back'=>Mage::helper('M2ePro')->makeBackUrlParam('view',array(
                'id'=>$listingData['id']
            ))
        ));
        $this->_addButton('edit_settings', array(
            'label'     => Mage::helper('M2ePro')->__('Edit Settings'),
            'onclick'   => 'window.open(\'' .$url.'\',\'_blank\')',
            'class'     => ''
        ));

        $url = $this->getUrl('*/adminhtml_ebay_listing/product',array(
            'id'=>$listingData['id'],
            'back'=>Mage::helper('M2ePro')->makeBackUrlParam('view',array(
                'id'=>$listingData['id']
            ))
        ));
        $this->_addButton('add_products', array(
            'label'     => Mage::helper('M2ePro')->__('Add Products'),
            'onclick'   => 'setLocation(\'' .$url.'\')',
            'class'     => 'add'
        ));

        /*if (!is_null($newListing) && $newListing == 'yes') {
           $this->_addButton('create_ebay_listing', array(
                'label'     => Mage::helper('M2ePro')->__('List All Items'),
                'onclick'   => 'EbayActionsHandlersObj.runListAllProducts()',
                'class'     => 'save'
           ));
        }*/
        //------------------------------
    }

    protected  function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>'.
               '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>'.
               '<div id="listing_view_content_container">'.
               parent::_toHtml().
               '</div>';
    }

    public function getGridHtml()
    {
        $temp = Mage::helper('M2ePro')->getSessionValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        $logViewUrl = $this->getUrl('*/adminhtml_log/listing',array(
            'id'=>$listingData['id'],
            'back'=>Mage::helper('M2ePro')->makeBackUrlParam('*/adminhtml_ebay_listing/view',array(
                'id'=>$listingData['id']
            ))
        ));
        $checkLockListing = $this->getUrl('*/adminhtml_listing/checkLockListing',array(
            'component'=>Ess_M2ePro_Helper_Component_Ebay::NICK
        ));
        $lockListingNow = $this->getUrl('*/adminhtml_listing/lockListingNow',array(
            'component'=>Ess_M2ePro_Helper_Component_Ebay::NICK
        ));
        $unlockListingNow = $this->getUrl('*/adminhtml_listing/unlockListingNow',array(
            'component'=>Ess_M2ePro_Helper_Component_Ebay::NICK
        ));
        $getErrorsSummary = $this->getUrl('*/adminhtml_listing/getErrorsSummary');

        $runListProducts = $this->getUrl('*/adminhtml_ebay_listing/runListProducts');
        $runReviseProducts = $this->getUrl('*/adminhtml_ebay_listing/runReviseProducts');
        $runRelistProducts = $this->getUrl('*/adminhtml_ebay_listing/runRelistProducts');
        $runStopProducts = $this->getUrl('*/adminhtml_ebay_listing/runStopProducts');
        $runStopAndRemoveProducts = $this->getUrl('*/adminhtml_ebay_listing/runStopAndRemoveProducts');

        $tempDropDownHtml = Mage::helper('M2ePro')->escapeJs(
            $this->getEditTemplateDropDownHtml()
        );

        $taskCompletedMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Task completed. Please wait ...')
        );

        $tempString = Mage::helper('M2ePro')->__('"%title%" task has successfully completed.');
        $taskCompletedSuccessMessage = Mage::helper('M2ePro')->escapeJs($tempString);

        $tempString  = Mage::helper('M2ePro')->__('"%title%" task has completed with warnings. ');
        $tempString .= Mage::helper('M2ePro')->__('<a href="%url%">View log</a> for details.');
        $taskCompletedWarningMessage = Mage::helper('M2ePro')->escapeJs($tempString);

        $tempString  = Mage::helper('M2ePro')->__('"%title%" task has completed with errors. ');
        $tempString .= Mage::helper('M2ePro')->__('<a href="%url%">View log</a> for details.');
        $taskCompletedErrorMessage = Mage::helper('M2ePro')->escapeJs($tempString);

        $sendingDataToEbayMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Sending %execute% product(s) data on eBay.')
        );
        $viewAllProductLogMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('View All Product Log.')
        );
        $listingLockedMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('The listing was locked by another process. Please try again later.')
        );
        $listingEmptyMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Listing is empty.')
        );
        $listingAllItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Listing All Items On eBay')
        );
        $listingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Listing Selected Items On eBay')
        );
        $revisingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Revising Selected Items On eBay')
        );
        $relistingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Relisting Selected Items On eBay')
        );
        $stoppingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Stopping Selected Items On eBay')
        );
        $stoppingAndRemovingSelectedItemsMessage = Mage::helper('M2ePro')->escapeJs(
            Mage::helper('M2ePro')->__('Stopping And Removing Selected Items On eBay')
        );

        $selectItemsMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Please select items.'));
        $selectActionMessage = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Please select action.'));

        $successWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Success'));
        $noticeWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Notice'));
        $warningWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Warning'));
        $errorWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Error'));
        $closeWord = Mage::helper('M2ePro')->escapeJs(Mage::helper('M2ePro')->__('Close'));

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

    M2ePro.text.task_completed_message = '{$taskCompletedMessage}';
    M2ePro.text.task_completed_success_message = '{$taskCompletedSuccessMessage}';
    M2ePro.text.task_completed_warning_message = '{$taskCompletedWarningMessage}';
    M2ePro.text.task_completed_error_message = '{$taskCompletedErrorMessage}';

    M2ePro.text.sending_data_message = '{$sendingDataToEbayMessage}';
    M2ePro.text.view_all_product_log_message = '{$viewAllProductLogMessage}';

    M2ePro.text.listing_locked_message = '{$listingLockedMessage}';
    M2ePro.text.listing_empty_message = '{$listingEmptyMessage}';

    M2ePro.text.listing_all_items_message = '{$listingAllItemsMessage}';
    M2ePro.text.listing_selected_items_message = '{$listingSelectedItemsMessage}';
    M2ePro.text.revising_selected_items_message = '{$revisingSelectedItemsMessage}';
    M2ePro.text.relisting_selected_items_message = '{$relistingSelectedItemsMessage}';
    M2ePro.text.stopping_selected_items_message = '{$stoppingSelectedItemsMessage}';
    M2ePro.text.stopping_and_removing_selected_items_message = '{$stoppingAndRemovingSelectedItemsMessage}';

    M2ePro.text.select_items_message = '{$selectItemsMessage}';
    M2ePro.text.select_action_message = '{$selectActionMessage}';

    M2ePro.text.success_word = '{$successWord}';
    M2ePro.text.notice_word = '{$noticeWord}';
    M2ePro.text.warning_word = '{$warningWord}';
    M2ePro.text.error_word = '{$errorWord}';
    M2ePro.text.close_word = '{$closeWord}';

    Event.observe(window, 'load', function() {
        ListingActionHandlerObj = new ListingActionHandler(M2ePro,{$listingData['id']});
        ListingItemGridHandlerObj = new ListingItemGridHandler(M2ePro,'ebayListingViewGrid{$listingData['id']}',
                                                               1,2,ListingActionHandlerObj);
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

        $helpBlock = $this->getLayout()->createBlock('M2ePro/adminhtml_ebay_listing_view_help');

        return $javascriptsMain.
               $helpBlock->toHtml().
               parent::getGridHtml();
    }

    public function getEditTemplateDropDownHtml()
    {
        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        $sellingFormatTemplate = Mage::helper('M2ePro')->__('Selling Format Template');
        $descriptionTemplate = Mage::helper('M2ePro')->__('Description Template');
        $generalTemplate = Mage::helper('M2ePro')->__('General Template');
        $synchronizationTemplate = Mage::helper('M2ePro')->__('Synchronization Template');

        $sellingFormatTemplateUrl = $this->getUrl('*/adminhtml_ebay_template_sellingFormat/edit',array(
            'id'=>$listingData['template_selling_format_id']
        ));
        $descriptionTemplateUrl = $this->getUrl('*/adminhtml_ebay_template_description/edit',array(
            'id'=>$listingData['template_description_id']
        ));
        $generalTemplateUrl = $this->getUrl('*/adminhtml_ebay_template_general/edit',array(
            'id'=>$listingData['template_general_id']
        ));
        $synchronizationTemplateUrl = $this->getUrl('*/adminhtml_ebay_template_synchronization/edit',array(
            'id'=>$listingData['template_synchronization_id']
        ));

        return <<<HTML
<ul style="display: none;">
    <li href="{$sellingFormatTemplateUrl}" target="_blank">{$sellingFormatTemplate}</li>
    <li href="{$descriptionTemplateUrl}" target="_blank">{$descriptionTemplate}</li>
    <li href="{$generalTemplateUrl}" target="_blank">{$generalTemplate}</li>
    <li href="{$synchronizationTemplateUrl}" target="_blank">{$synchronizationTemplate}</li>
</ul>
HTML;
    }
}
