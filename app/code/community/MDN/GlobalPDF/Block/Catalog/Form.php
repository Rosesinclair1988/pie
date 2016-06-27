<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2009 Maison du Logiciel (http://www.maisondulogiciel.com)
 * @author : Olivier ZIMMERMANN
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MDN_GlobalPDF_Block_Catalog_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * Return hierarchical drop down menu with all categories
	 */
	public function getCategoryCombo()
	{
		$html = '<select name="category_id" id="category_id">';
		
		$parentId = 0;
		$level = 0;
		$html .= $this->getCategoriesRecursive($parentId, $level);
		
		$html .= '</select>';
		return $html;
	}

	/**
	 * Recursive method to call categories
	 */
	protected function getCategoriesRecursive($parentId, $level)
	{
		$html = '';
		$categories = mage::getModel('catalog/category')
								->getCollection()
								->addAttributeToSelect('*')
								->addFieldToFilter('parent_id', $parentId);
		foreach($categories as $cat)
		{
			$indent = '';
			for ($i=0;$i<=$level;$i++)
				$indent .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			$indent .= '|&nbsp;';
			$html .= '<option value="'.$cat->getId().'">'.$indent.$cat->getName().'</option>';
			$html .= $this->getCategoriesRecursive($cat->getId(), $level + 1);
		}
		return $html ;
	}
}
