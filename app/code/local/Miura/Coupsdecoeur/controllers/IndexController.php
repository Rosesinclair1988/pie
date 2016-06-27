<?php
class Miura_Coupsdecoeur_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/coupsdecoeur?id=15 
    	 *  or
    	 * http://site.com/coupsdecoeur/id/15 	
    	 */
    	/* 
		$coupsdecoeur_id = $this->getRequest()->getParam('id');

  		if($coupsdecoeur_id != null && $coupsdecoeur_id != '')	{
			$coupsdecoeur = Mage::getModel('coupsdecoeur/coupsdecoeur')->load($coupsdecoeur_id)->getData();
		} else {
			$coupsdecoeur = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($coupsdecoeur == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$coupsdecoeurTable = $resource->getTableName('coupsdecoeur');
			
			$select = $read->select()
			   ->from($coupsdecoeurTable,array('coupsdecoeur_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$coupsdecoeur = $read->fetchRow($select);
		}
		Mage::register('coupsdecoeur', $coupsdecoeur);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}
