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
 * Custom renderer for the Systempay init button
 */
class Lyra_Systempay_Block_Field_InitButton extends Mage_Adminhtml_Block_System_Config_Form_Field {
	/**
	 * Set template to itself
	 */
	protected function _prepareLayout() {
		parent::_prepareLayout();
		
		if (!$this->getTemplate()) {
			$this->setTemplate('systempay/field/init_button.phtml');
		}
		
		return $this;
	}

	/**
	 * Unset some non-related element parameters
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	public function render(Varien_Data_Form_Element_Abstract $element) {
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	/**
	 * Get the button and scripts contents
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		$originalData = $element->getOriginalData();
		$this->addData(array(
				'button_label' => Mage::helper('systempay')->__($originalData['button_label']),
				'button_url'   => $this->getUrl($originalData['button_url'], array('_secure' => true)),
				'html_id' => $element->getHtmlId()
		));
		
		return $this->_toHtml();
	}
}