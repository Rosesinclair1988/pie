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

class Lyra_Systempay_Model_Source_CtxModes {
    public function toOptionArray() {
        $options =  array();

        foreach (Mage::helper('systempay')->getConfigArray('ctx_modes') as $code => $name) {
            $options[] = array
			(
               'value' => $code,
               'label' => $name
            );
        }

        return $options;
    }
}