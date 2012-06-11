<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Displays the current status of upgrades
 *
 */
class Minion_Task_Upgrade_Status extends Minion_Task {

	/**
	 * Execute the task
	 *
	 * @param array Config for the task
	 */
	public function execute(array $config)
	{
		$view = new View('task/upgrade/status');

		$view->upgrades = Model::factory('Task_Upgrade')
			->installed_upgrades();

		echo $view;
	}
}
