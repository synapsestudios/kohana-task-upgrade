<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Deploys a new app version
 */
class Task_Upgrade_Run extends Minion_Task
{

	protected $_options = array(
		'database'    => NULL,
		'drop-tables' => FALSE,
	);
	/**
	 * Run the application migrations and upgrades
	 *
	 * If no upgrade file is found all the migrations will run and notify the user the user that no upgrades were found.
	 *
	 * @param array Configuration to use
	 */
	protected function _execute(array $config)
	{
		Minion_CLI::write('-- App Upgrade --');

		$db = Database::instance($config['database']);

		if ($config['drop-tables'] === NULL)
		{
			$this->_clean_install($db);
			Minion_CLI::write('Dropped Tables');
		}

		$database_version = Model::factory('Task_Upgrade')
			->database_version();

		if ( ! $database_version)
		{
			$this->_install($db);

			// Get updated db version after install runs
			$database_version = Model::factory('Task_Upgrade')
				->database_version();
		}

		$this->_upgrade($db, $database_version);
	}

	protected function _install(Database $db)
	{
		Minion_CLI::write('Application not installed.');

		if ($install_file = Kohana::find_file('upgrades', 'Install'))
		{
			include $install_file;

			$install = new Upgrade_Install;

			Minion_CLI::write('Installing App...');

			// Create the initial database structure and insert any data included in the install file.
			Minion_CLI::write(' Creating initial database schema.');
			$this->_init_database_structure($db);

			Minion_CLI::write(' Inserting install data.');
			$this->_init_database_data($db);

			Minion_CLI::write(' Running install script.');
			$install->execute($db);

			Minion_CLI::write(' Installation completed.', TRUE);
		}
		else
		{
			Minion_CLI::write('No install file found. Nothing to do.');
		}
	}

	protected function _init_database_structure($db)
	{
		$install_schema_file = APPPATH.'/upgrades/db_structure.sql';

		if ( ! is_file($install_schema_file))
			return;

		$structure_sql = file_get_contents($install_schema_file);

		// Split the sql file on new lines and insert into the database one line at a time.
		foreach (preg_split('/;\s*\n/', $structure_sql) as $command)
		{
			try
			{
				$query = $db->query(NULL, $command);

				if (is_object($query))
				{
					$query->execute();
				}
			}
			catch (Database_Exception $e)
			{
				if ($e->getCode() !== 1065) // empty query
					throw $e;
			}
		}
	}

	protected function _init_database_data($db)
	{
		$install_data_file = APPPATH.'/upgrades/db_data.sql';

		if ( ! is_file($install_data_file))
			return;

		$data_sql = file_get_contents($install_data_file);

		// Split the sql file on new lines and insert into the database one line at a time.
		foreach (preg_split('/;\s*\n/', $data_sql) as $command)
		{
			try
			{
				$query = $db->query(NULL, $command);

				if (is_object($query))
				{
					$query->execute();
				}
			}
			catch (Database_Exception $e)
			{
				if ($e->getCode() !== 1065) // empty query
					throw $e;
			}
		}
	}

	protected function _upgrade(Database $db, $database_version)
	{
		if (version_compare($database_version, Kohana::APP_VERSION, '>'))
			throw new Minion_Exception('Database version ('.$database_version.') is newer than codebase ('.Kohana::APP_VERSION.'). Upgrade halted.');

		// Always run migrations before running the update.
		Minion_Task::factory(array('task' => 'migrations:run'))->execute();

		// No upgrade needed.
		if ($database_version == Kohana::APP_VERSION)
		{
			Minion_CLI::write('Your database is up-to-date. Nothing to do.');
			return;
		}

		if ($upgrade_file = Kohana::find_file('upgrades', Kohana::APP_VERSION))
		{
			include $upgrade_file;

			$update_class = 'Upgrade_'.str_replace('.', '_', Kohana::APP_VERSION);

			$upgrade = new $update_class;

			if ($upgrade->expected_version() !== $database_version)
				throw new Minion_Exception('The expected database version ('.$upgrade->expected_version().') is different from the actual database version ('.$database_version.'). Upgrade halted.');

			Minion_CLI::write_replace('Upgrading to version '.Kohana::APP_VERSION.'...');
			$upgrade->execute($db);
			Minion_CLI::write_replace('Upgrading to version '.Kohana::APP_VERSION.'... completed!', TRUE);
		}
		else
		{
			Minion_CLI::write('No upgrade file found. Nothing to do.');
		}
	}

	protected function _clean_install(Database $db)
	{
		$tables = $db->list_tables();

		$db->query(NULL, 'SET foreign_key_checks = 0');

		foreach ($tables as $table)
		{
			$db->query(NULL, 'DROP Table '.$db->quote_table($table));
		}

		$db->query(NULL, 'SET foreign_key_checks = 1');
	}
}
