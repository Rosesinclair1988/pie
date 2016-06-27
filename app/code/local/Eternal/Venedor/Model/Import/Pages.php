<?php

class Eternal_Venedor_Model_Import_Pages extends Mage_Core_Model_Abstract
{
    private $_importPath;
    
    public function __construct()
    {
        parent::__construct();
        $this->_importPath = Mage::getBaseDir() . '/app/code/local/Eternal/Venedor/data/import/';
    }
    
    public function importPageItems($modelString, $itemContainerNodeString, $overwrite = false)
    {
        try
        {
            $xmlPath = $this->_importPath . $itemContainerNodeString . '.xml';
            if (!is_readable($xmlPath))
            {
                throw new Exception(
                    Mage::helper('venedor')->__("Can't read data file: %s", $xmlPath)
                );
            }
            $xmlObj = new Varien_Simplexml_Config($xmlPath);
            
            $conflictingOldItems = array();
            $i = 0;
            foreach ($xmlObj->getNode($itemContainerNodeString)->children() as $item)
            {
                //Check if block already exists
                $oldBlocks = Mage::getModel($modelString)->getCollection()
                    ->addFieldToFilter('identifier', $item->identifier)
                    ->load();
                
                //If items can be overwritten
                if ($overwrite)
                {
                    if (count($oldBlocks) > 0)
                    {
                        $conflictingOldItems[] = $item->identifier;
                        foreach ($oldBlocks as $old)
                            $old->delete();
                    }
                }
                else
                {
                    if (count($oldBlocks) > 0)
                    {
                        $conflictingOldItems[] = $item->identifier;
                        continue;
                    }
                }
                
                Mage::getModel($modelString)
                    ->setTitle($item->title)
                    ->setContent($item->content)
                    ->setIdentifier($item->identifier)
                    ->setIsActive($item->is_active)
                    ->setStores(array(0))
                    ->save();
                $i++;
            }            
            
            //Final info
            if ($i)
            {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('venedor')->__('Number of imported items: %s', $i)
                );
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addNotice(
                    Mage::helper('venedor')->__('No items were imported')
                );
            }
            
            if ($overwrite)
            {
                if ($conflictingOldItems)
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('venedor')
                        ->__('Items (%s) with the following identifiers were overwritten:<br />%s', count($conflictingOldItems), implode(', ', $conflictingOldItems))
                    );
            }
            else
            {
                if ($conflictingOldItems)
                    Mage::getSingleton('adminhtml/session')->addNotice(
                        Mage::helper('venedor')
                        ->__('Unable to import items (%s) with the following identifiers (they already exist in the database):<br />%s', count($conflictingOldItems), implode(', ', $conflictingOldItems))
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
