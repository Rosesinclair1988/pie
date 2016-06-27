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

/**
 * Systempay Admin Configuraion Controller
 */
class Lyra_Systempay_Adminhtml_Systempay_ConfigController extends Mage_Adminhtml_Controller_Action {

	public function resetAction() {
		$resource = Mage::getSingleton('core/resource');
		
		// retrieve write connection 
		$writeConnection = $resource->getConnection('core_write');
		
		// get sales_flat_order table name & execute update query
		$table = $resource->getTableName('sales/order');
		$query = "UPDATE `{$table}` SET status = 'pending_payment' WHERE status = 'pending_vads' OR status = 'pending_vadsmulti' OR status = 'pending_pwbpv1'" ;
		$writeConnection->query($query);
		
		// get sales_flat_order_payment table name & execute update query
		$table = $resource->getTableName('sales/order_payment');
		$query = "UPDATE `{$table}` SET method = 'systempay' WHERE method = 'vads'";
		$writeConnection->query($query);
		
		$query = "UPDATE `{$table}` SET method = 'systempay_multi' WHERE method = 'vadsmulti'";
		$writeConnection->query($query);
		
		$query = "UPDATE `{$table}` SET method = 'systempay' WHERE method = 'pwbpv1'";
		$writeConnection->query($query);
		
		// get sales_flat_quote_payment table name & execute update query
		$table = $resource->getTableName('sales/quote_payment');
		$query = "UPDATE `{$table}` SET method = 'systempay' WHERE method = 'vads'";
		$writeConnection->query($query);
		
		$query = "UPDATE `{$table}` SET method = 'systempay_multi' WHERE method = 'vadsmulti'";
		$writeConnection->query($query);
		
		$query = "UPDATE `{$table}` SET method = 'systempay' WHERE method = 'pwbpv1'";
		$writeConnection->query($query);
		
		// get config data model table name & execute query
		$table = $resource->getTableName('core/config_data');
		$query = "DELETE FROM `{$table}` WHERE `path` LIKE 'payment/systempay%' OR `path` LIKE 'payment/vads%'";
		$writeConnection->query($query);
		
		// clear cache
		Mage::getConfig()->removeCache();
		
		$session = Mage::getSingleton('adminhtml/session');
		$session->addSuccess(Mage::helper('systempay')->__('The configuration of the Systempay module has been successfully reset.'));
		
		// redirect to payment config editor 
		$this->_redirect('adminhtml/system_config/edit', array('_secure' => true, 'section' => 'payment'));
	}	
}