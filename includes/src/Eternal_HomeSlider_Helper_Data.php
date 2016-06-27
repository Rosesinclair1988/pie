<?php

class Eternal_HomeSlider_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getConfig($option)
    {
        return Mage::getStoreConfig('eternal_homeslider/' . $option);
    }
    
    public function getJsPluginFile() {
        if ($this->getConfig('general/type') == 'block') {
            switch ($this->getConfig('general/block_plugin')) {
                case 'fraction': // Fraction Slider
                    return 'eternal/jquery/jquery.fractionslider.min.js';
                case 'sequence': // Sequence Modern Slide In
                    return 'eternal/jquery/jquery.sequence-min.js';
                default: // Revolution Slider
                    return 'eternal/jquery/jquery.themepunch.revolution.min.js';
            }
        }
        // Box Slider
        return 'eternal/jquery/jquery.bxslider.min.js';
    }
    
    public function getCssPluginFile() {
        if ($this->getConfig('general/type') == 'block') {
            switch ($this->getConfig('general/block_plugin')) {
                case 'fraction': // Fraction Slider
                    return 'css/jquery/fractionslider.css';
                case 'sequence': // Sequence Modern Slide In
                    return 'css/jquery/sequencejs.css';
                default: // Revolution Slider
                    return 'css/jquery/revolution.css';
            }
        }
        // Box Slider
        return 'css/jquery/bxslider.css';
    }
    
    public function getResponsiveCssPluginFile() {
        if (!$this->getConfig('general/active_responsive'))
            return 'css/empty.css';
            
        if ($this->getConfig('general/type') == 'block') {
            switch ($this->getConfig('general/block_plugin')) {
                case 'fraction': // Fraction Slider
                    return 'css/jquery/fractionslider-responsive.css';
                case 'sequence': // Sequence Modern Slide In
                    return 'css/jquery/sequencejs-responsive.css';
                default: // Revolution Slider
                    return 'css/jquery/revolution-responsive.css';
            }
        }
        // Box Slider
        return 'css/jquery/bxslider-responsive.css';
    }
}
