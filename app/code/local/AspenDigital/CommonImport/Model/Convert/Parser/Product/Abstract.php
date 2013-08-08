<?php

abstract class AspenDigital_CommonImport_Model_Convert_Parser_Product_Abstract extends Mage_Dataflow_Model_Convert_Parser_Abstract
{
	protected $_categories_with_parents;

	protected $_show_info;
	protected $_dry_run;
	

	// Return the index for the manufacturer name in a record array
	abstract protected function _getManufacturerRecordKey();
	abstract protected function _saveRow($data);
	abstract protected function _skipRecord($data);
	
	public function lockIndexer()
	{
		Mage::getSingleton('index/indexer')->lockIndexer();
	}

	public function unlockIndexer()
	{
		Mage::getSingleton('index/indexer')->unlockIndexer();
	}

	// Add any manufacturer names that will be needed later (due to caching, this doesn't work while processing each record)
	protected function _addManufacturers()
	{
		$attribute = Mage::getModel('catalog/product')->getResource()->getAttribute('manufacturer');
		$setup = Mage::getModel('eav/entity_setup', 'core_setup');

		/*
		 * Get current values.  This would normally just require $attribute->getSource()->getAllOptions(),
		 * but we don't want the source to cache the values, since we're adding options.  So, we use this code adapted
		 * from Mage_Eav_Model_Entity_Attribute_Source_Table getAllOptions.  This doesn't seem like the sort of thing
		 * that would be likely to change, so it's a satisfactory solution to the issue.
		 */
		$options = array();
		$collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($attribute->getId())
                ->setStoreFilter($attribute->getStoreId())
                ->load();
		foreach ($collection->toOptionArray() as $opt)
			$options[ strtolower($opt['label']) ] = $opt['label'];

		$record_key = $this->_getManufacturerRecordKey();
		for ($count = count($this->_products)-1; $count >= 0; $count--)
		{
			$record =& $this->_products[$count];

			$manufacturer_name = $record[$record_key];
			$index = strtolower(trim($manufacturer_name));
			if ($this->_skipRecord($record) || empty($index))
				continue;

			// If the name exists in some form, make sure we match the actual option value
			//   (we don't want different case usage to result in a mismatch)
			if (isset($options[$index]))
			{
				$record[$record_key] = $options[$index];
				continue;
			}

			// Add new option to manufacturer attribute
			$option['attribute_id'] = $attribute->getId();
			$option['value'][0][0] = $manufacturer_name;
			$setup->addAttributeOption($option);

			$options[$index] = $manufacturer_name;
		}
	}

	// Display immediately if option set; otherwise save as normal
	public function addException($msg, $level=null)
	{
		return ($this->_showInfo()) ? $this->addInfoException($msg, $level) : parent::addException($msg, $level);
	}

	/* Display immediately if option set; otherwise do not do anything (for less important step-by-step messages
	 * that aren't important if not done in real-time). Saving the exceptions consumes significant amount of memory,
	 * so using it for general-purpose messages is not effective
	 */
	public function addInfoException($msg, $level=null)
	{
		if (!$this->_showInfo())
			return;

		$level = (is_null($level)) ? Mage_Dataflow_Model_Convert_Exception::NOTICE : $level;



		// From Mage_Adminhtml_Block_System_Convert_Profile_Run (kind of a hack to put it here, but it does work)
		switch ($level) {
				case Varien_Convert_Exception::FATAL:
					$img = 'error_msg_icon.gif';
					$liStyle = 'background-color:#FBB; ';
					break;
				case Varien_Convert_Exception::ERROR:
					$img = 'error_msg_icon.gif';
					$liStyle = 'background-color:#FDD; ';
					break;
				case Varien_Convert_Exception::WARNING:
					$img = 'fam_bullet_error.gif';
					$liStyle = 'background-color:#FFD; ';
					break;
				case Varien_Convert_Exception::NOTICE:
					$img = 'fam_bullet_success.gif';
					$liStyle = 'background-color:#DDF; ';
					break;
			}

		echo '<li style="'.$liStyle.'">';
		echo '<img src="'.Mage::getDesign()->getSkinUrl('images/'.$img).'" class="v-middle"/>';
		echo $msg;
		echo "</li>";
	}

	// Recursively build up list of category IDs with parents
	protected function _categoryChain($node, $parent_ids=array())
	{
		$parent_ids[] = $node['category_id'];
		$this->_categories_with_parents[$node['category_id']] = join(',', $parent_ids);
		foreach ($node['children'] as $child)
			$this->_categoryChain($child, $parent_ids);
	}

	// Return IDs for all categories in chain -- that is, all parent categories as well -- in a comma-delimited string
	protected function _categoryWithParents($category_id)
	{
		if (!$this->_categories_with_parents)
		{
			$this->_categories_with_parents = array();
			$category_api = Mage::getSingleton('catalog/category_api_v2');

			$tree = $category_api->tree();
			foreach ($tree['children'] as $node) // Skip root category
				$this->_categoryChain($node);
		}

		return $this->_categories_with_parents[$category_id];
	}

	protected function _isDryRun()
	{
		if (is_null($this->_dry_run))
		{
			$dry_run = $this->getVar('dry_run');
			if (is_null($dry_run))
				$dry_run = Mage::registry('import_product_dry_run');

			$this->_dry_run = $dry_run;
		}

		return $this->_dry_run;
	}

	protected function _showInfo()
	{
		if (is_null($this->_show_info))
		{
			$show = $this->getVar('show_info');
			if (is_null($show))
				$show = Mage::registry('import_product_show_info');

			$this->_show_info = $show;
		}

		return $this->_show_info;
	}
}

?>
