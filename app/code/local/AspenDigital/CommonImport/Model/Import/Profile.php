<?php

// Wrapper to provide dataflow/profile functions using convert class (so we can take advantage of system import block)
class AspenDigital_CommonImport_Model_Import_Profile
{
	protected $_profile;

	// This is a generic wrapper we can set up to report back whatever we need
	protected $_name;
	protected $_entity_type = 'product';
	protected $_direction = 'import';

	function setProfile($profile)
	{
		$this->_profile = $profile;
		return $this;
	}

	function getId()
	{
		return true;
	}

	function setName($name)
	{
		$this->_name = $name;
		return $this;
	}

	function getName()
	{
		return $this->_name;
	}

	function run()
	{
		return $this->_profile->run();
	}

	function getExceptions()
	{
		return $this->_profile->getExceptions();
	}

	function setEntityType($value)
	{
		$this->_entity_type = $value;
		return $this;
	}

	function getEntityType()
	{
		return $this->_entity_type;
	}

	function setDirection($value)
	{
		$this->_direction = $value;
		return $this;
	}

	function getDirection()
	{
		return $this->_direction;
	}
}

?>
