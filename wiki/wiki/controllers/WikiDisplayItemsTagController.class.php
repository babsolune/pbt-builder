<?php
/**
 * @copyright 	&copy; 2005-2019 PHPBoost
 * @license 	https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Sebastien LARTIGUE <babsolune@phpboost.com>
 * @version   	PHPBoost 5.2 - last update: 2018 05 25
 * @since   	PHPBoost 5.1 - 2018 05 25
*/

class WikiDisplayItemsTagController extends ModuleController
{
	private $lang;
	private $view;
	private $keyword;

	private $config;
	private $content_management_config;

	public function execute(HTTPRequestCustom $request)
	{
		$this->check_authorizations();

		$this->init();

		$this->build_view($request);

		return $this->generate_response();
	}

	private function init()
	{
		$this->lang = LangLoader::get('common', 'wiki');
		$this->view = new FileTemplate('wiki/WikiDisplayCategoryController.tpl');
		$this->view->add_lang($this->lang);
		$this->config = WikiConfig::load();
		$this->content_management_config = ContentManagementConfig::load();
	}

	private function get_keyword()
	{
		if ($this->keyword === null)
		{
			$rewrited_name = AppContext::get_request()->get_getstring('tag', '');
			if (!empty($rewrited_name))
			{
				try {
					$this->keyword = WikiService::get_keywords_manager()->get_keyword('WHERE rewrited_name=:rewrited_name', array('rewrited_name' => $rewrited_name));
				} catch (RowNotFoundException $e) {
					$error_controller = PHPBoostErrors::unexisting_page();
   					DispatchManager::redirect($error_controller);
				}
			}
			else
			{
				$error_controller = PHPBoostErrors::unexisting_page();
   				DispatchManager::redirect($error_controller);
			}
		}
		return $this->keyword;
	}

	private function build_view($request)
	{
		$now = new Date();

		$authorized_categories = WikiService::get_authorized_categories(Category::ROOT_CATEGORY);

		$condition = 'WHERE relation.id_keyword = :id_keyword
		AND id_category IN :authorized_categories
		AND (published = 1 OR (published = 2 AND publishing_start_date < :timestamp_now AND (publishing_end_date > :timestamp_now OR publishing_end_date = 0)))';
		$parameters = array(
			'id_keyword' => $this->get_keyword()->get_id(),
			'authorized_categories' => $authorized_categories,
			'timestamp_now' => $now->get_timestamp()
		);

		$page = AppContext::get_request()->get_getint('page', 1);
		$pagination = $this->get_pagination($condition, $parameters, $page);

		$result = PersistenceContext::get_querier()->select('SELECT wiki.*, member.*
		FROM ' . WikiSetup::$wiki_table . ' wiki
		LEFT JOIN ' . DB_TABLE_KEYWORDS_RELATIONS . ' relation ON relation.module_id = \'wiki\' AND relation.id_in_module = wiki.id
		LEFT JOIN ' . DB_TABLE_MEMBER . ' member ON member.user_id = wiki.author_user_id
		' . $condition . '
		ORDER BY order_id
		LIMIT :number_items_per_page OFFSET :display_from', array_merge($parameters, array(
			'number_items_per_page' => $pagination->get_number_items_per_page(),
			'display_from' => $pagination->get_display_from()
		)));

		$number_columns_display_per_line = $this->config->get_number_cols_display_per_line();

		$this->view->put_all(array(
			'C_DOCUMENTS' => $result->get_rows_count() > 0,
			'C_MORE_THAN_ONE_DOCUMENT' => $result->get_rows_count() > 1,
			'C_PAGINATION' => $pagination->has_several_pages(),
			'PAGINATION' => $pagination->display(),
			'C_NO_DOCUMENT_AVAILABLE' => $result->get_rows_count() == 0,
			'C_MOSAIC' => $this->config->get_display_type() == WikiConfig::DISPLAY_MOSAIC,
			'C_DOCUMENTS_CAT' => false,
			'C_COMMENTS_ENABLED' => $this->comments_config->module_comments_is_enabled('wiki'),
			'C_NOTATION_ENABLED' => $this->content_management_config->module_notation_is_enabled('wiki'),
			'C_DOCUMENTS_FILTERS' => true,
			'CATEGORY_NAME' => $this->get_keyword()->get_name(),
			'C_SEVERAL_COLUMNS' => $number_columns_display_per_line > 1,
			'NUMBER_COLUMNS' => $number_columns_display_per_line
		));

		while ($row = $result->fetch())
		{
			$document = new Document();
			$document->set_properties($row);

			$this->build_keywords_view($document);

			$this->view->assign_block_vars('wiki', $document->get_array_tpl_vars());
		}
		$result->dispose();
	}

	private function build_keywords_view(Document $document)
	{
		$keywords = $document->get_keywords();
		$nbr_keywords = count($keywords);
		$this->view->put('C_KEYWORDS', $nbr_keywords > 0);

		$i = 1;
		foreach ($keywords as $keyword)
		{
			$this->view->assign_block_vars('keywords', array(
				'C_SEPARATOR' => $i < $nbr_keywords,
				'NAME' => $keyword->get_name(),
				'URL' => WikiUrlBuilder::display_tag($keyword->get_rewrited_name())->rel(),
			));
			$i++;
		}
	}

	private function get_pagination($condition, $parameters, $page)
	{
		$result = PersistenceContext::get_querier()->select_single_row_query('SELECT COUNT(*) AS nbr_wiki
		FROM '. WikiSetup::$wiki_table .' wiki
		LEFT JOIN '. DB_TABLE_KEYWORDS_RELATIONS .' relation ON relation.module_id = \'wiki\' AND relation.id_in_module = wiki.id
		' . $condition, $parameters);

		$pagination = new ModulePagination($page, $result['nbr_wiki'], WikiConfig::load()->get_number_items_per_page());
		$pagination->set_url(WikiUrlBuilder::display_tag($this->get_keyword()->get_rewrited_name(), '%d'));

		if ($pagination->current_page_is_empty() && $page > 1)
		{
			$error_controller = PHPBoostErrors::unexisting_page();
			DispatchManager::redirect($error_controller);
		}

		return $pagination;
	}

	private function check_authorizations()
	{
		if (!(WikiAuthorizationsService::check_authorizations()->read()))
		{
			$error_controller = PHPBoostErrors::user_not_authorized();
			DispatchManager::redirect($error_controller);
		}
	}

	private function generate_response()
	{
		$response = new SiteDisplayResponse($this->view);

		$graphical_environment = $response->get_graphical_environment();
		$graphical_environment->set_page_title($this->get_keyword()->get_name(), $this->lang['wiki']);
		$graphical_environment->get_seo_meta_data()->set_description(StringVars::replace_vars($this->lang['wiki.seo.description.tag'], array('subject' => $this->get_keyword()->get_name())));
		$graphical_environment->get_seo_meta_data()->set_canonical_url(WikiUrlBuilder::display_tag($this->get_keyword()->get_rewrited_name(), AppContext::get_request()->get_getint('page', 1)));

		$breadcrumb = $graphical_environment->get_breadcrumb();
		$breadcrumb->add($this->lang['wiki'], WikiUrlBuilder::home());
		$breadcrumb->add($this->get_keyword()->get_name(), WikiUrlBuilder::display_tag($this->get_keyword()->get_rewrited_name(), AppContext::get_request()->get_getint('page', 1)));

		return $response;
	}
}
?>
