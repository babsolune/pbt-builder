<?php
/*##################################################
 *                   TsmClubsAuthService.class.php
 *                            -------------------
 *   begin                : February 13, 2018
 *   copyright            : (C) 2018 Sebastien LARTIGUE
 *   email                : babsolune@phpboost.com
 *
 *
 ###################################################
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

/**
 * @author Sebastien LARTIGUE <babsolune@phpboost.com>
 */

class TsmClubsAuthService
{
	const READ_CLUB_AUTH = 1;
	const WRITE_CLUB_AUTH = 2;
	const CONTRIBUTION_CLUB_AUTH = 4;
	const MODERATION_CLUB_AUTH = 8;

	public static function check_club_auth()
	{
		$instance = new self();
		return $instance;
	}

	public function read_club()
	{
		return $this->is_authorized(self::READ_CLUB_AUTH);
	}

	public function write_club()
	{
		return $this->is_authorized(self::WRITE_CLUB_AUTH);
	}

	public function contribution_club()
	{
		return $this->is_authorized(self::CONTRIBUTION_CLUB_AUTH);
	}

	public function moderation_club()
	{
		return $this->is_authorized(self::MODERATION_CLUB_AUTH);
	}

	private function is_authorized($bit)
	{
		$auth = TsmConfig::load()->get_club_auth();
		return AppContext::get_current_user()->check_auth($auth, $bit);
	}

}
?>