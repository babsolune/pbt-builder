<?php
/*##################################################
 *                       ModlistUrlBuilder.class.php
 *                            -------------------
 *   begin                : Month XX, 2017
 *   copyright            : (C) 2013 Firstname LASTNAME
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

class ModlistUrlBuilder
{

	private static $dispatcher = '/modlist';

    // Administration

	/**
	 * @return Url
	 */
	public static function configuration()
	{
		return DispatchManager::get_url(self::$dispatcher, '/admin/config/');
	}

    // Categories

	/**
	 * @return Url
	 */
	public static function manage_categories()
	{
		return DispatchManager::get_url(self::$dispatcher, '/categories/');
	}

	/**
	 * @return Url
	 */
	public static function category_syndication($id)
	{
		return SyndicationUrlBuilder::rss('modlist', $id);
	}

	/**
	 * @return Url
	 */
	public static function add_category($id_parent = null)
	{
		$id_parent = !empty($id_parent) ? $id_parent . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/categories/add/' . $id_parent);
	}

	/**
	 * @return Url
	 */
	public static function edit_category($id)
	{
		return DispatchManager::get_url(self::$dispatcher, '/categories/'. $id .'/edit/');
	}

	/**
	 * @return Url
	 */
	public static function delete_category($id)
	{
		return DispatchManager::get_url(self::$dispatcher, '/categories/'. $id .'/delete/');
	}

	/**
	 * @return Url
	 */
	public static function display_category($id, $rewrited_name, $sort_field = '', $sort_mode = '', $page = 1, $subcategories_page = 1)
	{
		$config = ModlistConfig::load();
		$category = $id > 0 ? $id . '-' . $rewrited_name .'/' : '';
		$page = $page !== 1 || $subcategories_page !== 1 ? $page . '/': '';
		$subcategories_page = $subcategories_page !== 1 ? $subcategories_page . '/': '';
		$sort_field = $sort_field !== $config->get_items_default_sort_field() ? $sort_field . '/' : '';
		$sort_mode = $sort_mode !== $config->get_items_default_sort_mode() ? $sort_mode . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/' . $category . $sort_field . $sort_mode . $page . $subcategories_page);
	}

    // Items

	/**
	 * @return Url
	 */
	public static function manage_items()
	{
		return DispatchManager::get_url(self::$dispatcher, '/manage/');
	}

	/**
	 * @return Url
	 */
	public static function print_item($id_itemlist, $rewrited_title)
	{
		return DispatchManager::get_url(self::$dispatcher, '/print/' . $id_itemlist . '-' .$rewrited_title . '/');
	}

	/**
	 * @return Url
	 */
	public static function add_item($id_category = null)
	{
		$id_category = !empty($id_category) ? $id_category . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/add/' . $id_category);
	}

	/**
	 * @return Url
	 */
	public static function edit_item($id, $page = 1)
	{
		$page = $page !== 1 ? $page . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/' . $id . '/edit/' . $page);
	}

	/**
	 * @return Url
	 */
	public static function delete_item($id)
	{
		return DispatchManager::get_url(self::$dispatcher, '/' . $id . '/delete/?' . 'token=' . AppContext::get_session()->get_token());
	}

	/**
	 * @return Url
	 */
	public static function display_item($id_category, $rewrited_name_category, $id_itemlist, $rewrited_title, $page = 1)
	{
		$page = $page !== 1 ? $page . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/' . $id_category . '-' . $rewrited_name_category . '/' . $id_itemlist . '-' .$rewrited_title . '/' . $page);
	}

	/**
	 * @return Url
	 */
	public static function display_items_comments($id_category, $rewrited_name_category, $id_itemlist, $rewrited_title)
	{
		return DispatchManager::get_url(self::$dispatcher, '/' . $id_category . '-' . $rewrited_name_category . '/' . $id_itemlist . '-' . $rewrited_title . '/#comments-list');
	}

	/**
	 * @return Url
	 */
 	public static function display_pending_items($sort_field = '', $sort_mode = '', $page = 1)
	{
		$config = ModlistConfig::load();
		$page = $page !== 1 ? $page . '/': '';
		$sort_field = $sort_field !== $config->get_items_default_sort_field() ? $sort_field . '/' : '';
		$sort_mode = $sort_mode !== $config->get_items_default_sort_mode() ? $sort_mode . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/pending/' . $sort_field . $sort_mode . $page);
	}

	/**
	 * @return Url
	 */
	public static function display_tag($rewrited_name, $sort_field = '', $sort_mode = '', $page = 1)
	{
		$config = ModlistConfig::load();
		$page = $page !== 1 ? $page . '/' : '';
		$sort_field = $sort_field !== $config->get_items_default_sort_field() ? $sort_field . '/' : '';
		$sort_mode = $sort_mode !== $config->get_items_default_sort_mode() ? $sort_mode . '/' : '';
		return DispatchManager::get_url(self::$dispatcher, '/tag/'. $rewrited_name . '/' . $sort_field . $sort_mode . $page);
	}

	/**
	 * @return Url
	 */
	public static function home()
	{
		return DispatchManager::get_url(self::$dispatcher, '/');
	}
}
?>