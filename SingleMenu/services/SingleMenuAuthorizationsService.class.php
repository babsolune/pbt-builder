<?php

/**
 * @package 	SingleMenu
 * @subpackage 	Services
 * @category 	Modules
 * @copyright 	&copy; 2005-2019 PHPBoost
 * @license 	https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Sebastien LARTIGUE <babsolune@phpboost.com>
 * @version   	PHPBoost 5.2 - last update: 2016 04 21
 * @since   	PHPBoost 5.0 - 2016 04 21
 */

class SingleMenuAuthorizationsService
{
	const READ_AUTHORIZATIONS = 1;

	public static function check_authorizations()
	{
		$instance = new self();
		return $instance;
	}

	public function read()
	{
		return $this->get_authorizations(self::READ_AUTHORIZATIONS);
	}

	private function get_authorizations($bit)
	{
		return AppContext::get_current_user()->check_auth(SingleMenuConfig::load()->get_authorizations(), $bit);
	}
}
?>
