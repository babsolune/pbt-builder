<?php
/*##################################################
 *		      ModcatfullDeleteCategoryController.class.php
 *                            -------------------
 *   begin                : Month XX, 2017
 *   copyright            : (C) 2017 Firstname LASTNAME
 *   email                : nickname@phpboost.com
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
 * @author Firstname LASTNAME <nickname@phpboost.com>
 */

class ModcatfullDeleteCategoryController extends AbstractDeleteCategoryController
{
	protected function get_id_category()
	{
		return AppContext::get_request()->get_getint('id', 0);
	}

	protected function get_categories_manager()
	{
		return ModcatfullService::get_categories_manager();
	}

	protected function get_categories_management_url()
	{
		return ModcatfullUrlBuilder::manage_categories();
	}

	protected function get_delete_category_url(Category $category)
	{
		return ModcatfullUrlBuilder::delete_category($category->get_id());
	}

	protected function get_module_home_page_url()
	{
		return ModcatfullUrlBuilder::home();
	}

	protected function get_module_home_page_title()
	{
		return LangLoader::get_message('modcatfull.module.title', 'common', 'modcatfull');
	}

	protected function check_authorizations()
	{
		if (!ModcatfullAuthorizationsService::check_authorizations()->manage_categories())
		{
			$error_controller = PHPBoostErrors::user_not_authorized();
			DispatchManager::redirect($error_controller);
		}
	}
}
?>