<?php
class Miura_Astucespro_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/astucespro?id=15 
    	 *  or
    	 * http://site.com/astucespro/id/15 	
    	 */
    	/* 
		$astucespro_id = $this->getRequest()->getParam('id');

  		if($astucespro_id != null && $astucespro_id != '')	{
			$astucespro = Mage::getModel('astucespro/astucespro')->load($astucespro_id)->getData();
		} else {
			$astucespro = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($astucespro == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$astucesproTable = $resource->getTableName('astucespro');
			
			$select = $read->select()
			   ->from($astucesproTable,array('astucespro_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$astucespro = $read->fetchRow($select);
		}
		Mage::register('astucespro', $astucespro);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}
