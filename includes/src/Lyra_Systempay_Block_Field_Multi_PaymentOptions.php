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
 * Custom renderer for the Systempay multi payment options field
 */
class Lyra_Systempay_Block_Field_Multi_PaymentOptions extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	public function __construct() {
		$this->addColumn('label', array(
				'label' => Mage::helper('systempay')->__('Label'),
				'style' => 'width: 150px',
		));
		$this->addColumn('minimum', array(
				'label' => Mage::helper('systempay')->__('Mini amount'),
				'style' => 'width: 80px',
		));
		$this->addColumn('maximum', array(
				'label' => Mage::helper('systempay')->__('Maxi amount'),
				'style' => 'width: 80px',
		));
		$this->addColumn('contract', array(
				'label' => Mage::helper('systempay')->__('Contract'),
				'style' => 'width: 65px',
		));
		$this->addColumn('count', array(
				'label' => Mage::helper('systempay')->__('Count'),
				'style' => 'width: 65px',
		));
		$this->addColumn('period', array(
				'label' => Mage::helper('systempay')->__('Period'),
				'style' => 'width: 65px',
		));
		$this->addColumn('first', array(
				'label' => Mage::helper('systempay')->__('1st payment'),
				'style' => 'width: 70px',
		));
		
		$this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('systempay')->__('Add');
		parent::__construct();
	}

}