<?php
class Eternal_Venedor_Block_Social_Facebookfans extends Mage_Core_Block_Template
{
    protected $_cacheKeyArray = NULL;
    protected $_fbFans;
    
    /**
     * Initialize block's cache
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addData(array(
            'cache_lifetime'    => 3600,
            'cache_tags'        => array(Mage_Cms_Model_Block::CACHE_TAG)
        ));
    }
    
    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        if (NULL === $this->_cacheKeyArray)
        {
            $this->_cacheKeyArray = array(
                'SOCIAL_FACEBOOKFANS',
                Mage::app()->getStore()->getId(),
                (int)Mage::app()->getStore()->isCurrentlySecure(),
                Mage::getDesign()->getPackageName(),
                Mage::getDesign()->getTheme('template')
            );
        }
        return $this->_cacheKeyArray;
    }

    public function getFans()
    {
        if (is_null($this->_fbFans))
        {
            $venedor = Mage::helper('venedor');
            $this->_fbFans = $venedor->getFBFans();
        }
        return $this->_fbFans;
    }
}
