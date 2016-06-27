<?php
class Eternal_Venedor_Block_Social_Twittertweets extends Mage_Core_Block_Template
{
    protected $_cacheKeyArray = NULL;
    protected $_tweets;
    
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
                'SOCIAL_TWITTERTWEETS',
                Mage::app()->getStore()->getId(),
                (int)Mage::app()->getStore()->isCurrentlySecure(),
                Mage::getDesign()->getPackageName(),
                Mage::getDesign()->getTheme('template')
            );
        }
        return $this->_cacheKeyArray;
    }

    public function getTweets()
    {
        if (is_null($this->_tweets))
        {
            $venedor = Mage::helper('venedor');
            $this->_tweets = $venedor->getTwitterTweets();
        }
        return $this->_tweets;
    }
    
    public function processString($s){  
        return preg_replace('@(?<!href="|">)(https?:\/\/[\w\-\.!~?&=+\*\'(),\/]+)((?!\<\/\a\>).)*@i','<a target="_blank" href="$1">$1</a>',$s);
    }
}
