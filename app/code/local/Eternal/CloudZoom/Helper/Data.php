<?php

class Eternal_CloudZoom_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfig($optionString)
    {
        return Mage::getStoreConfig('eternal_cloudzoom/' . $optionString);
    }
    
    /**
     * Check if module is enabled.
     * @return bool
     */
    public function isCloudZoomEnabled()
    {
        return (bool) $this->getConfig('general/enable');
    }
    
    /**
     * Check if lightbox is enabled
     * @return bool
     */
    public function useLightbox()
    {
        return (bool) $this->getConfig('lightbox/enable');
    }
    
    /**
     * Check if cloud zoom position equals 'inside'
     * @return bool
     */
    public function isPositionInside()
    {
        return ($this->getConfig('general/position') == 'inside');
    }
    
    /**
     * Get string with CloudZoom options
     * @return string
     */
    public function getCloudZoomOptions()
    {        
        //Get Cloud Zoom options
        $showTitle      = intval($this->getConfig('general/show_title'));
        $titleOpacity   = intval($this->getConfig('general/title_opacity')) / 100;
        $position       = $this->getConfig('general/position');
        $zoomWidth      = intval($this->getConfig('general/zoom_width'));
        $zoomHeight     = intval($this->getConfig('general/zoom_height'));
        $lensOpacity    = intval($this->getConfig('general/lens_opacity')) / 100;
        $tint           = intval($this->getConfig('general/tint'));
        $tintOpacity    = intval($this->getConfig('general/tint_opacity')) / 100;
        $softFocus      = intval($this->getConfig('general/soft_focus'));
        $smoothMove     = intval($this->getConfig('general/smooth_move'));
        
        //Create CloudZoom option array
        $config = array(
            "position:'{$position}'",
            "lensOpacity:{$lensOpacity}",
            "smoothMove:{$smoothMove}",
            "showTitle:{$showTitle}",
            "titleOpacity:{$titleOpacity}"
        );
        
        if ($zoomWidth) {
            $config[] = "zoomWidth:{$zoomWidth}";
        }
        if ($zoomHeight) {
            $config[] = "zoomHeight:{$zoomHeight}";
        }
    
        //Right and bottom: move 10px (+ 2 * 1px border). Left and top: move -10px (- 2 * 1px border).
        if ($position == 'inside') {
            $config[] = 'adjustX:0,adjustY:0';
        } elseif ($position == 'right') {
            $config[] = 'adjustX:4,adjustY:0';
        } elseif ($position == 'bottom') {
            $config[] = 'adjustX:0,adjustY:4';
        } elseif ($position == 'left') {
            $config[] = 'adjustX:-4,adjustY:0';
        } elseif ($position == 'top') {
            $config[] = 'adjustX:0,adjustY:-4';
        }

        if ($tint) {
            $config[] = "tint:'{$tint}',tintOpacity:{$tintOpacity}";
        }
        if ($softFocus) {
            $config[] = "softFocus:{$softFocus}";
        }
        
        return implode($config, ',');
    }
}