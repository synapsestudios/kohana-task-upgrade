<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Base Upgrade class. All upgrade files should extend this class
 *
 */
abstract class Upgrade_Base {

	/**
	 * Database verison required to run this upgrade
	 * @var string
	 */
	protected $_expected_version = NULL;

	/**
	 * Version of app that the installer script will install. If different from codebase version upgrades will run.
	 * @var boolean
	 */
	protected $_install_version = FALSE;

	protected $_db = NULL;

	public function expected_version()
	{
		if ( ! $this->_expected_version)
			throw new Kohana_Exception('Upgrade file must have an expected version set.');

		return $this->_expected_version;
	}

	public function execute(Database $db)
	{
		$this->_db = $db;

		$this->_execute();

		Model::factory('Task_Upgrade')
			->upgraded($this->_install_version ? $this->_install_version : Kohana::APP_VERSION);
	}

	/**
	 * Runs the upgrade
	 *
	 * @param Database The database connection to perform actions on
	 */
	abstract protected function _execute();
}
