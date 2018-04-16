<?php
/*##################################################
 *		    ModlistDisplayPendingItemsController.class.php
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

class ModlistDisplayPendingItemsController extends ModuleController
{
	private $lang;
	private $view;
	private $form;

	public function execute(HTTPRequestCustom $request)
	{
		$this->check_authorizations();

		$this->init();

		$this->build_view($request);

		return $this->generate_response();
	}

	private function init()
	{
		$this->lang = LangLoader::get('common', 'modlist');
		$this->view = new FileTemplate('modlist/ModlistDisplayCategoryController.tpl');
		$this->view->add_lang($this->lang);
	}

	private function build_sorting_form($field, $mode)
	{
		$common_lang = LangLoader::get('common');

		$form = new HTMLForm(__CLASS__, '', false);
		$form->set_css_class('options');

		$fieldset = new FormFieldsetHorizontal('filters', array('description' => $common_lang['sort_by']));
		$form->add_fieldset($fieldset);

		$fieldset->add_field(new FormFieldSimpleSelectChoice('sort_fields', '', $field, array(
			new FormFieldSelectChoiceOption($common_lang['form.date.creation'], Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_DATE]),
			new FormFieldSelectChoiceOption($common_lang['form.title'], Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_ALPHABETIC]),
			new FormFieldSelectChoiceOption($common_lang['author'], Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_AUTHOR])
			), array('events' => array('change' => 'document.location = "'. ModlistUrlBuilder::display_pending_items()->rel() . '" + HTMLForms.getField("sort_fields").getValue() + "/" + HTMLForms.getField("sort_mode").getValue();'))
		));

		$fieldset->add_field(new FormFieldSimpleSelectChoice('sort_mode', '', $mode,
			array(
				new FormFieldSelectChoiceOption($common_lang['sort.asc'], 'asc'),
				new FormFieldSelectChoiceOption($common_lang['sort.desc'], 'desc')
			),
			array('events' => array('change' => 'document.location = "' . ModlistUrlBuilder::display_pending_items()->rel() . '" + HTMLForms.getField("sort_fields").getValue() + "/" + HTMLForms.getField("sort_mode").getValue();'))
		));

		$this->form = $form;
	}

	private function build_view($request)
	{
		$now = new Date();
		$authorized_categories = ModlistService::get_authorized_categories(Category::ROOT_CATEGORY);
		$config = ModlistConfig::load();
		$comments_config = new ModlistComments();
		$notation_config = new ModlistNotation();

		$mode = $request->get_getstring('sort', $config->get_items_default_sort_mode());
		$field = $request->get_getstring('field', Itemlist::SORT_FIELDS_URL_VALUES[$config->get_items_default_sort_field()]);

		$sort_mode = TextHelper::strtoupper($mode);
		$sort_mode = (in_array($sort_mode, array(Itemlist::ASC, Itemlist::DESC)) ? $sort_mode : $config->get_items_default_sort_mode());

		if (in_array($field, array(Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_ALPHABETIC], Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_AUTHOR], Itemlist::SORT_FIELDS_URL_VALUES[Itemlist::SORT_DATE])))
			$sort_field = array_search($field, Itemlist::SORT_FIELDS_URL_VALUES);
		else
			$sort_field = Itemlist::SORT_DATE;

		$condition = 'WHERE id_category IN :authorized_categories
		' . (!ModlistAuthorizationsService::check_authorizations()->moderation() ? ' AND author_user_id = :user_id' : '') . '
		AND (published = 0 OR (published = 2 AND (publication_start_date > :timestamp_now OR (publication_end_date != 0 AND publication_end_date < :timestamp_now))))';
		$parameters = array(
			'authorized_categories' => $authorized_categories,
			'user_id' => AppContext::get_current_user()->get_id(),
			'timestamp_now' => $now->get_timestamp()
		);

		$page = AppContext::get_request()->get_getint('page', 1);
		$pagination = $this->get_pagination($condition, $parameters, $field, TextHelper::strtolower($sort_mode), $page);

		$result = PersistenceContext::get_querier()->select('SELECT modlist.*, member.*, com.number_comments, notes.number_notes, notes.average_notes, note.note
		FROM '. ModlistSetup::$modlist_table .' modlist
		LEFT JOIN '. DB_TABLE_MEMBER .' member ON member.user_id = modlist.author_user_id
		LEFT JOIN ' . DB_TABLE_COMMENTS_TOPIC . ' com ON com.id_in_module = modlist.id AND com.module_id = "modlist"
		LEFT JOIN ' . DB_TABLE_AVERAGE_NOTES . ' notes ON notes.id_in_module = modlist.id AND notes.module_name = "modlist"
		LEFT JOIN ' . DB_TABLE_NOTE . ' note ON note.id_in_module = modlist.id AND note.module_name = "modlist" AND note.user_id = :user_id
		' . $condition . '
		ORDER BY ' . $sort_field . ' ' . $sort_mode . '
		LIMIT :items_number_per_page OFFSET :display_from', array_merge($parameters, array(
			'items_number_per_page' => $pagination->get_number_items_per_page(),
			'display_from' => $pagination->get_display_from()
		)));

		$nbr_items_pending = $result->get_rows_count();

		$this->build_sorting_form($field, TextHelper::strtolower($sort_mode));

		$this->view->put_all(array(
			'C_ITEMS' => $result->get_rows_count() > 0,
			'C_MORE_THAN_ONE_ITEM' => $result->get_rows_count() > 1,
			'C_PENDING' => true,
			'C_MOSAIC' => $config->get_display_type() == ModlistConfig::MOSAIC_DISPLAY,
			'C_NO_ITEM_AVAILABLE' => $nbr_items_pending == 0
		));

		if ($nbr_items_pending > 0)
		{
			$columns_number_displayed_per_line = $config->get_cols_number_displayed_per_line();

			$this->view->put_all(array(
				'C_ITEMS_FILTERS' => true,
				'C_COMMENTS_ENABLED' => $comments_config->are_comments_enabled(),
				'C_NOTATION_ENABLED' => $notation_config->is_notation_enabled(),
				'C_PAGINATION' => $pagination->has_several_pages(),
				'PAGINATION' => $pagination->display(),
				'C_SEVERAL_COLUMNS' => $columns_number_displayed_per_line > 1,
				'NUMBER_COLUMNS' => $columns_number_displayed_per_line
			));

			while($row = $result->fetch())
			{
				$itemlist = new Itemlist();
				$itemlist->set_properties($row);

				$this->build_keywords_view($itemlist);

				$this->view->assign_block_vars('items', $itemlist->get_array_tpl_vars());
				$this->build_sources_view($itemlist);
			}
		}
		$result->dispose();

		$this->view->put('FORM', $this->form->display());
	}

	private function build_sources_view(Itemlist $itemlist)
	{
		$sources = $itemlist->get_sources();
		$nbr_sources = count($sources);
		if ($nbr_sources)
		{
			$this->view->put('items.C_SOURCES', $nbr_sources > 0);

			$i = 1;
			foreach ($sources as $name => $url)
			{
				$this->view->assign_block_vars('items.sources', array(
					'C_SEPARATOR' => $i < $nbr_sources,
					'NAME' => $name,
					'URL' => $url,
				));
				$i++;
			}
		}
	}

	private function build_keywords_view(Itemlist $itemlist)
	{
		$keywords = $itemlist->get_keywords();
		$nbr_keywords = count($keywords);
		$this->view->put('C_KEYWORDS', $nbr_keywords > 0);

		$i = 1;
		foreach ($keywords as $keyword)
		{
			$this->view->assign_block_vars('keywords', array(
				'C_SEPARATOR' => $i < $nbr_keywords,
				'NAME' => $keyword->get_name(),
				'URL' => ModlistUrlBuilder::display_tag($keyword->get_rewrited_name())->rel(),
			));
			$i++;
		}
	}

	private function check_authorizations()
	{
		if (!(ModlistAuthorizationsService::check_authorizations()->write() || ModlistAuthorizationsService::check_authorizations()->contribution() || ModlistAuthorizationsService::check_authorizations()->moderation()))
		{
			$error_controller = PHPBoostErrors::user_not_authorized();
			DispatchManager::redirect($error_controller);
		}
	}

	private function get_pagination($condition, $parameters, $field, $mode, $page)
	{
		$items_number = PersistenceContext::get_querier()->count(ModlistSetup::$modlist_table, $condition, $parameters);

		$pagination = new ModulePagination($page, $items_number, (int)ModlistConfig::load()->get_items_number_per_page());
		$pagination->set_url(ModlistUrlBuilder::display_pending_items($field, $mode, '/%d'));

		if ($pagination->current_page_is_empty() && $page > 1)
		{
			$error_controller = PHPBoostErrors::unexisting_page();
			DispatchManager::redirect($error_controller);
		}

		return $pagination;
	}

	private function generate_response()
	{
		$response = new SiteDisplayResponse($this->view);

		$graphical_environment = $response->get_graphical_environment();
		$graphical_environment->set_page_title($this->lang['modlist.pending.items'], $this->lang['modlist.module.title']);
		$graphical_environment->get_seo_meta_data()->set_description($this->lang['modlist.seo.description.pending']);
		$graphical_environment->get_seo_meta_data()->set_canonical_url(ModlistUrlBuilder::display_pending_items(AppContext::get_request()->get_getstring('field', 'date'), AppContext::get_request()->get_getstring('sort', 'desc'), AppContext::get_request()->get_getint('page', 1)));

		$breadcrumb = $graphical_environment->get_breadcrumb();
		$breadcrumb->add($this->lang['modlist.module.title'], ModlistUrlBuilder::home());
		$breadcrumb->add($this->lang['modlist.pending.items'], ModlistUrlBuilder::display_pending_items(AppContext::get_request()->get_getstring('field', 'date'), AppContext::get_request()->get_getstring('sort', 'desc'), AppContext::get_request()->get_getint('page', 1)));

		return $response;
	}
}
?>