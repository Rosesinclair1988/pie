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

abstract class Lyra_Systempay_Block_Abstract extends Mage_Payment_Block_Form {
	
	protected function _construct() {
		parent::_construct();
	
		$model = Mage::getModel($this->_getModelName());
		$logoFileName = $model->getLogo();
	
		if($logoFileName && $this->_checkAndGetSkinUrl($logoFileName)) {
			$logo = Mage::getConfig()->getBlockClassName('core/template');
			$logo = new $logo;
			$logo->setTemplate('systempay/logo.phtml');
			$logo->setLogoSrc($this->_checkAndGetSkinUrl($logoFileName));
			$logo->setMethodTitle($model->getConfigData('title'));
			 
			// Add logo to the method title
			$this->setMethodTitle('')->setMethodLabelAfterHtml($logo->toHtml());
		}
	}
	
	protected function _checkAndGetSkinUrl($file) {
		$fileName = Mage::getDesign()->getFilename('images' . DS . 'systempay' . DS . $file, array('_type' => 'skin'));
		 
		if (file_exists($fileName)) {
			return $this->getSkinUrl('images/systempay/' . $file);
		}
		 
		return false;
	}
	
	protected abstract function _getModelName();
}