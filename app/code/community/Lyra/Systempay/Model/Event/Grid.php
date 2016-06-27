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

class Lyra_Systempay_Model_Event_Grid {
	
	public function showColumn($observer) {
		$block = $observer->getEvent()->getBlock();
		
		if($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid){
			$block->addColumnAfter('method', array(
					'header' => Mage::helper('systempay')->__('Payment Method'),
					'index' => 'method',
					'type' => 'text',
					'width' => '50px',
			), 'status');
		
			$block->setCollection($this->getNewCollection());
			$block->sortColumnsByOrder();
		}
	}
		
	public function  getNewCollection() {
		$collection = Mage::getResourceModel('sales/order_grid_collection');
		$collection->getSelect()
			->join(
					array('payment' => $collection->getTable('sales/order_payment')),
					'payment.parent_id = main_table.entity_id',
					array('method')
			);
		
		return $collection;
	}
}