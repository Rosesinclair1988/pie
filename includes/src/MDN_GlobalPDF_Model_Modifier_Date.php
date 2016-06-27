<?php

class MDN_GlobalPDF_Model_Modifier_Date extends MDN_GlobalPDF_Model_Modifier_Abstract {

    public function apply($modifierParam, $value) {
        $timestamp = strtotime($value);
        $dateFormatIso = '';
        switch ($modifierParam) {
            case 'short':
                $dateFormatIso = mage::getStoreConfig('globalpdf/date/short');
                break;
            case 'medium':
                $dateFormatIso = mage::getStoreConfig('globalpdf/date/medium');
                break;
            case 'long':
                $dateFormatIso = mage::getStoreConfig('globalpdf/date/long');
                break;
            default:
                $dateFormatIso = mage::getStoreConfig('globalpdf/date/short');
                break;
        }

        $currentDate = date($dateFormatIso, $timestamp);

        // get the current store language : 'en' or 'fr' ...
        $langStore =  Mage::app()->getLocale()->getlocaleCode();
        $langStore = substr($langStore, 0, 2);

        $day = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
        $month = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $jours = array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
        $mois = array("Janvier", "Fevrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Decembre");

        if ($langStore == 'fr') {
            $currentDate = str_replace($day, $jours, $currentDate); // formattage jours
            $currentDate = str_replace($month, $mois, $currentDate); // formattage mois
        }



        return $currentDate;
    }

}
