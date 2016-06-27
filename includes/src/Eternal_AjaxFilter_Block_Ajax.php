<?php 

class Eternal_AjaxFilter_Block_Ajax extends Mage_Core_Block_Template 
{
    public function __construct(){
        $_helper = Mage::helper('eternal_ajaxfilter');
        $this->config = $_helper->getConfig('general');
        $this->url = Mage::getStoreConfig('web/unsecure/base_url');
        
        $this->ajaxSlider = $this->config['slider'];
        $this->ajaxLayered = $this->config['layered'];
        $this->ajaxToolbar = $this->config['toolbar'];
        $this->overlayColor = $this->config['overlay_color'];
        $this->overlayOpacity = $this->config['overlay_opacity'];
        $this->loadingText = $this->config['loading_text'];
        $this->loadingTextColor = $this->config['loading_text_color'];
        $this->loadingImage = $this->config['loading_image'];
        if($this->loadingImage == '' || $this->loadingImage == null){
            $this->loadingImage = '';//$this->url.'media/eternal/ajaxfilter/default/ajax-loader.gif';
        }else{
            $this->loadingImage = $this->url.'media/eternal/ajaxfilter/'.$this->loadingImage;
        }    
    }
}