<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement Systempay
#						Version : 1.2 (révision 46959)
#									########################
#					Développé pour Magento
#						Version : 1.5.1.0
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						13/05/2013
#						Contact : supportvad@lyra-network.com
#
#####################################################################################################

class Lyra_Systempay_Block_Info extends Mage_Payment_Block_Info {
	
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('systempay/info.phtml');
    }
    
    public function getTrsInfosHtml() {
	    $collection = Mage::getResourceModel('sales/order_payment_transaction_collection');
	    $collection->addOrderIdFilter($this->getInfo()->getData('entity_id'));
	    $collection->load();
	    
	    $html = '';
	    
	    foreach ($collection as $item) {
	    	$html .= '<hr />';
	    	
	    	$html .=  $this->__('Sequence Number') . ' : ' . substr($item->getTxnId(), strpos($item->getTxnId(), '-') + 1);
	    	$html .= '<br />';
	    	
	    	$info = $item->getAdditionalInformation('raw_details_info');
	    	foreach ($info as $key => $value) {
	    		$html .= $this->__($key) . ' : ' . $value;
	    		$html .= '<br />';
	    	}
	    }
	    
	    return $html;
    }
}