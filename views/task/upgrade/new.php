<?php echo Kohana::FILE_SECURITY.PHP_EOL; ?>
/**
 * 
 * Upgrade application to version <?php echo $version.PHP_EOL; ?>
 *
 */

class <?php echo $class; ?> extends Upgrade_Base {
	
	/**
	 * The database version this upgrade applies to.
	 *
	 * @type string
	 */
	protected $_expected_version = '<?php echo $database_version ?>';

	/**
	 * Run all database upgrades for this app version
	 *
	 */
	protected function _execute()
	{
		// $this->_bugfix_42();
	}

	/**
	* protected function _bugfix_42()
	* {
	* 	// Fix phone number formatting
	* }
	**/

}
