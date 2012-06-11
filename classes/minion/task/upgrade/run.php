<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Deploys a new app version
 */
class Minion_Task_Upgrade_Run extends Minion_Task
{

	protected $_config = array(
		'database',
		'drop-tables'
	);
	/**
	 * Run the application migrations and upgrades
	 * 
	 * If no upgrade file is found all the migrations will run and notify the user the user that no upgrades were found.
	 *
	 * @param array Configuration to use
	 */
	public function execute(array $config)
	{
		Minion_CLI::write('-- App Upgrade --');

		$database = Arr::get($config, 'database');

		$db = Database::instance($database);

		if (array_key_exists('drop-tables', $config))
		{
			$this->_clean_install($db);
		}

		$database_version = Model::factory('Task_Upgrade')
			->database_version();

		if ( ! $database_version)
		{
			$this->_install($db);
		}
		else
		{
			$this->_upgrade($db, $database_version);
		}
	}

	protected function _install(Database $db)
	{
		Minion_CLI::write('Application not installed.');

		if ($install_file = Kohana::find_file('upgrades', 'install'))
		{
			include $install_file;

			$install = new Upgrade_Install;

			Minion_CLI::write('Installing App...');

			$install->execute($db);

			Minion_CLI::write('Installing App... completed!');

			// Make sure the migrations are up-to-date
			Minion_Task::factory('migrations:run')->execute(array());
		}
		else
		{
			Minion_CLI::write('No install file found. Nothing to do.');
		}
	}

	protected function _upgrade(Database $db, $database_version)
	{
		if (version_compare($database_version, Kohana::APP_VERSION, '>'))
			throw new Minion_Exception('Database version newer than codebase. Upgrade halted.');

		// If an upgrade isn't needed just run the migrations
		if ($database_version == Kohana::APP_VERSION)
		{
			Minion_Task::factory('migrations:run')->execute(array());

			Minion_CLI::write('Your database is up-to-date. Nothing to do.');
			return;
		}

		if ($upgrade_file = Kohana::find_file('upgrades', Kohana::APP_VERSION))
		{
			include $upgrade_file;

			$update_class = 'Upgrade_'.str_replace('.', '_', Kohana::APP_VERSION);

			$upgrade = new $update_class;

			if ($upgrade->expected_version() !== $database_version)
				throw new Minion_Exception('The expected database version if different from the actual database version. Upgrade halted.');

			// Make sure the migrations are up-to-date before running the upgrade
			Minion_Task::factory('migrations:run')->execute(array());

			Minion_CLI::write('Upgrading to version '.Kohana::APP_VERSION.'...');
			$upgrade->execute($db);
			Minion_CLI::write('Upgrading to version '.Kohana::APP_VERSION.'... completed!');
		}
		else
		{
			// Need to run the migrations even if no upgrade was found
			Minion_Task::factory('migrations:run')->execute(array());

			Minion_CLI::write('No install file found. Nothing to do.');
		}
	}

	protected function _clean_install(Database $db)
	{
		$tables = $db->list_tables();

		$db->query(NULL, 'SET foreign_key_checks = 0');

		foreach ($tables as $table)
		{
			$db->query(NULL, 'DROP Table '.$db->quote_identifier($table));
		}

		$db->query(NULL, 'SET foreign_key_checks = 1');
	}
}
