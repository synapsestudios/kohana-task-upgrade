<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for managing migrations
 */
class Model_Task_Upgrade extends Model_Database
{

	/**
	 * The table that's used to store the migrations
	 * @var string
	 */
	protected $_table = 'app_versions';

	public function __construct($db = NULL)
	{
		parent::__construct($db);

		// Create the table if needed
		$this->ensure_table_exists();
	}

	/**
	 * Returns a list of upgrades that have been installed
	 *
	 * @return array
	 */
	public function installed_upgrades()
	{
		return DB::select('version', 'timestamp')
				->from($this->_table)
				->order_by(DB::expr('INET_ATON(SUBSTRING_INDEX(CONCAT(version, ".0.0.0"), ".", 4))'), 'desc')
				->execute($this->_db)
				->as_array('version', 'timestamp');
	}

	protected function ensure_table_exists()
	{
		$query = $this->_db->query(Database::SELECT, "SHOW TABLES like '".$this->_table."'");

		if ( ! count($query))
		{
			$sql = file_get_contents(Kohana::find_file('', 'upgrade_schema', 'sql'));

			$this->_db->query(NULL, $sql);
		}
	}

	/**
	 * Finds the current (last) database version
	 * 
	 * @return string Current database version
	 */
	public function database_version()
	{
		return DB::select('version')
				->from($this->_table)
				->order_by('timestamp', 'desc')
				->limit(1)
				->execute($this->_db)
				->get('version');
	}

	/**
	 * Marks and upgrade as installed
	 * @param  string $version Upgrade version that was installed
	 * @return void
	 */
	public function upgraded($version)
	{
		DB::insert($this->_table, array('version', 'timestamp'))
			->values(array($version, time()))
			->execute($this->_db);
	}

}