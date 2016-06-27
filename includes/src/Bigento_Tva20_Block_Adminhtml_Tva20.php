<?php

class Bigento_Tva20_Block_Adminhtml_Tva20 extends Mage_Core_Block_Template
{

    public function getMessage()
    {
        $html = '';
        $rates = mage::helper('bgtva20')->getAllTVA20();
        $happynewyeardate = mage::helper('bgtva20')->getHappyNewYearDate(true);

        $html .= $this->__('<strong>%s</strong> taux seront modifié le <strong>%s</strong>', count($rates), $happynewyeardate) . '&nbsp;&nbsp;';
        $html .= '<a href="' . Mage::helper("adminhtml")->getUrl('bgtva20/index/save') . '">' . $this->helper('tax')->__('Appliquer les nouveaux taux MAINTENANT') . '</a> - ';
        $html .= '<a href="' . Mage::helper("adminhtml")->getUrl('bgtva20/index/restore') . '">' . $this->helper('tax')->__('Rétablir  les anciens taux') . '</a><br /><br />';
        $html .= '<ul>';

        foreach ($rates as $rate) {
            $html .= '<li>' . $this->__('Pour la règle <strong>%s</strong> du code pays <strong>%s</strong> le taux <strong>%s</strong> sera remplacé par <strong>%s</strong><br />', $rate->getCode(), $rate->gettax_country_id(), $rate->getrate(), $rate->getnew_rate()) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}
