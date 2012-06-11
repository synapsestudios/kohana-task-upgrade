<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Create a new upgrade file
 *
 *
 * @author Matt Button <matthew@sigswitch.com>
 */
class Minion_Task_Upgrade_New extends Minion_Task
{
	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_config = array(
		'location',
		'version'
	);

	/**
	 * Execute the task
	 *
	 * @param array Configuration
	 */
	public function execute(array $config)
	{
		if ( ! self::valid_version($config['version']))
		{
			return 'Please provide version. E.g. --version=1.0.0'.PHP_EOL;
		}

		if ( ! self::version_exists($config['version']))
		{
			return 'The version you specified already exists.'.PHP_EOL;
		}

		if (empty($config['location']))
		{
			$config['location'] = APPPATH;
		}

		try
		{
			$file = $this->_generate($config);
			Minion_CLI::write('Upgrade generated: '.$file);
		}
		catch(ErrorException $e)
		{
			Minion_CLI::write($e->getMessage());
		}

	}

	protected function _generate($config)
	{
		$location = rtrim(realpath($config['location']), '/').'/upgrades/';

		$file = $location.$config['version'].EXT;

		$class = 'Upgrade_'.str_replace('.', '_', $config['version']);

		$database_version = Model::factory('Task_Upgrade')
			->database_version();

		$data = View::factory('task/upgrade/new')
			->set('class', $class)
			->set('version', $config['version'])
			->set('database_version', $database_version)
			->render();

		if ( ! is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0775, TRUE);
		}

		file_put_contents($file, $data);

		return $file;
	}

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
	 		->rule('version', 'not_empty')
	 		->rule('version', 'Task_Upgrade_New::valid_version')
	 		->rule('version', 'Task_Upgrade_New::version_exists');
	}


	public static function valid_version($version)
	{
		// Can only consist of numeric values and dots
		if (preg_match('/[^0-9\.]/', $version))
			return FALSE;

		return TRUE;
	}

	public static function version_exists($version)
	{
		// Make sure the version doesn't already exist
		if (Kohana::find_file('upgrades', $version))
			return FALSE;

		return TRUE;
	}

}