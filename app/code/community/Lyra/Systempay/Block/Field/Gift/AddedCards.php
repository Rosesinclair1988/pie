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
 * Custom renderer for the Systempay add gift cards field
 */
class Lyra_Systempay_Block_Field_Gift_AddedCards extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	public function __construct() {
		$this->addColumn('code', array(
				'label' => Mage::helper('systempay')->__('Card Code'),
				'style' => 'width: 100px'
		));
		$this->addColumn('name', array(
				'label' => Mage::helper('systempay')->__('Card Label'),
				'style' => 'width: 200px'
		));
		
		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('systempay')->__('Add');
		parent::__construct();
	}

}