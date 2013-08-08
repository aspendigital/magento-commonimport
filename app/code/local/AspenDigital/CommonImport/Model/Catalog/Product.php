<?php

/* Extend catalog/product so that we can capture attempted cache cleaning in bulk
 * import/update situations and clean the cache all in one go at the end. This does
 * of course mean that we open ourselves to the small possibility of a fatal error in the
 * middle of a bulk operation getting the cache out of sync.  However, the performance
 * boost is drastic.
 */
class AspenDigital_CommonImport_Model_Catalog_Product extends Mage_Catalog_Model_Product
{
	protected static $_capture_tags = false;
	protected static $_captured_cache_tags = array();


	public function startCacheCapture()
	{
		self::$_capture_tags = true;
	}

	public function endCacheCapture()
	{
		self::$_capture_tags = false;

		// Run the deferred cache cleaning
		if (empty(self::$_captured_cache_tags))
			return;

		$tags = array_unique(self::$_captured_cache_tags);
		if (!empty($tags))
			Mage::app()->cleanCache($tags);

		self::$_captured_cache_tags = array();
	}


	/* From Mage_Catalog_Model_Product - clear cache for this product's record
	 * This is called in _beforeSave, but in _afterSave cleanModelCache is called,
	 * which includes this cache tag.  So, when we're in bulk import mode, we can
	 * ignore this call
	 */
	public function cleanCache()
	{
		return (self::$_capture_tags) ? $this : parent::cleanCache();
	}

	/* From Mage_Core_Model_Abstract - clear cache for this model */
	public function cleanModelCache()
	{
		if (!self::$_capture_tags)
			return parent::cleanModelCache();

		$tags = $this->getCacheTags();
		self::$_captured_cache_tags = array_merge(self::$_captured_cache_tags, $tags);

		return $this;
	}
}

?>
