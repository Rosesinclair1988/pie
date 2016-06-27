<?php

class Eternal_Venedor_Model_Export_Blocks extends Mage_Core_Model_Abstract
{
    private $_exportPath;
    
    public function __construct()
    {
        parent::__construct();
        $this->_exportPath = Mage::getBaseDir() . '/app/code/local/Eternal/Venedor/data/export/';
    }
    
    public function exportBlockItems($modelString, $modelIdentifierString, $itemContainerNodeString)
    {
        try
        {
            // Create xml file
            $io = new Varien_Io_File();
            $xmlPath = $this->_exportPath . $itemContainerNodeString . '.xml';
            
            $io->setAllowCreateFolders(true);
            $io->open(array('path' => $this->_exportPath));
            
            if ($io->fileExists($xmlPath) && !$io->isWriteable($xmlPath))
            {
                Mage::throwException(Mage::helper('venedor')->__('File "%s" cannot be saved. Please, make sure the directory is writeable by web server.', $xmlPath));
            }

            $io->streamOpen($xmlPath, 'w+');
            $io->streamWrite('<root>'. "\n");
            $io->streamWrite("\t".'<'.$itemContainerNodeString.'>');
            
            // Get cms collection
            $collection = Mage::getModel($modelString)->getCollection();
            $i = 0;
            foreach ($collection as $item)
            {
                $title      = $item->getTitle();
                $identifier = $item->getIdentifier();
                $content    = $item->getContent();
                $is_active  = $item->getIsActive();
            
                $io->streamWrite("\n\t\t".'<'.$modelIdentifierString.'>'."\n\t\t\t".'<title>'.$title.'</title>'."\n\t\t\t".'<identifier>'.$identifier.'</identifier>'."\n\t\t\t".'<content><![CDATA['.$content.']]></content>'."\n\t\t\t".'<is_active>'.$is_active.'</is_active>'."\n\t\t\t".'<stores><item>0</item></stores>'."\n\t\t".'</'.$modelIdentifierString.'>');
                
                $i++;
            }
            
            $io->streamWrite("\n\t".'</'.$itemContainerNodeString.'>');
            
            $io->streamWrite("\n".'</root>');
            $io->streamClose();
            
            //Final info
            if ($i)
            {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('venedor')->__('Number of exported items: %s', $i)
                );
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addNotice(
                    Mage::helper('venedor')->__('No items were exported')
                );
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::logException($e);
        }
    }    
}