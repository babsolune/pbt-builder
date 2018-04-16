<?php
/*##################################################
 *                               ClubsCommentsTopic.class.php
 *                            -------------------
 *   begin                : June 23, 2017
 *   copyright            : (C) 2017 Sebastien LARTIGUE
 *   email                : babsolune@phpboost.com
 *
 *
 ###################################################
 *
 * This program is a free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

 /**
 * @author Sebastien LARTIGUE <babsolune@phpboost.com>
 */

class ClubsCommentsTopic extends CommentsTopic
{
	private $club;
	
	public function __construct(Club $club = null)
	{
		parent::__construct('clubs');
		$this->club = $club;
	}
	
	public function get_authorizations()
	{
		$authorizations = new CommentsAuthorizations();
		$authorizations->set_authorized_access_module(ClubsAuthorizationsService::check_authorizations($this->get_club()->get_id_category())->read());
		return $authorizations;
	}
	
	public function is_display()
	{
		return $this->get_club()->is_visible();
	}
	
	private function get_club()
	{
		if ($this->club === null)
		{
			$this->club = ClubsService::get_club('WHERE clubs.id=:id', array('id' => $this->get_id_in_module()));
		}
		return $this->club;
	}
}
?>