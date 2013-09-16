<?php defined('SYSPATH') or die('No direct script access.');

class Task_Upgrade_Generate extends Minion_Task
{
	protected $_options = [];

	protected $_db_settings;

	protected $_output_path;

	public function __construct()
	{
		$this->_db_settings = Kohana::$config->load('database')->default['connection'];

		$this->_output_path = APPPATH . 'upgrades';
	}

	protected function _execute(array $config)
	{
		$this->_dump_structure();

		Minion_Cli::write('Exported DB structure');

		$this->_dump_data();

		Minion_Cli::write('Exported DB data');
	}

	protected function _dump_structure()
	{
		$output_file = $this->_output_path . '/db_structure.sql';

		$cmd = sprintf(
			'mysqldump %s -u %s -p%s --no-data | sed "s/AUTO_INCREMENT=[0-9]*//" > %s',
			escapeshellarg($this->_db_settings['database']),
			escapeshellarg($this->_db_settings['username']),
			escapeshellarg($this->_db_settings['password']),
			escapeshellarg($output_file)
		);

		return shell_exec($cmd);
	}

	protected function _dump_data()
	{
		$output_file = $this->_output_path . '/db_data.sql';

		$tables = array_map('escapeshellarg', Kohana::$config->load('install')->data_tables);

		$cmd = sprintf(
			'mysqldump %s %s -u %s -p%s --no-create-info > %s',
			escapeshellarg($this->_db_settings['database']),
			implode(' ', $tables),
			escapeshellarg($this->_db_settings['username']),
			escapeshellarg($this->_db_settings['password']),
			escapeshellarg($output_file)
		);

		return shell_exec($cmd);
	}

}
