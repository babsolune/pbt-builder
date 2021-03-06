<?php
/**
 * @copyright 	&copy; 2005-2019 PHPBoost
 * @license 	https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL-3.0
 * @author      Sebastien LARTIGUE <babsolune@phpboost.com>
 * @version   	PHPBoost 5.2 - last update: 2018 05 25
 * @since   	PHPBoost 5.1 - 2018 05 25
*/

class WikiDisplayItemHistoryController extends ModuleController
{
	private $lang;
	private $tpl;
	private $document;

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
		$this->tpl = new FileTemplate('wiki/WikiDisplayItemHistoryController.tpl');
		$this->tpl->add_lang($this->lang);
	}

	private function build_view(HTTPRequestCustom $request)
	{
		$config = WikiConfig::load();
		$content_management_config = ContentManagementConfig::load();
	}

	private function check_authorizations()
	{
		$document = $this->get_document();

		$current_user = AppContext::get_current_user();
		// $not_authorized = !WikiAuthorizationsService::check_authorizations($document->get_id_category())->moderation() && !WikiAuthorizationsService::check_authorizations($document->get_id_category())->write() && (!WikiAuthorizationsService::check_authorizations($document->get_id_category())->contribution() || $document->get_author_user()->get_id() != $current_user->get_id());
		//
		// switch ($document->get_publishing_state())
		// {
		// 	case Document::PUBLISHED_NOW:
		// 		if (!WikiAuthorizationsService::check_authorizations($document->get_id_category())->read())
		// 		{
		// 			$error_controller = PHPBoostErrors::user_not_authorized();
		//    			DispatchManager::redirect($error_controller);
		// 		}
		// 	break;
		// 	case Document::NOT_PUBLISHED:
		// 		if ($not_authorized || ($current_user->get_id() == User::VISITOR_LEVEL))
		// 		{
		// 			$error_controller = PHPBoostErrors::user_not_authorized();
		//    			DispatchManager::redirect($error_controller);
		// 		}
		// 	break;
		// 	case Document::PUBLISHED_DATE:
		// 		if (!$document->is_published() && ($not_authorized || ($current_user->get_id() == User::VISITOR_LEVEL)))
		// 		{
		// 			$error_controller = PHPBoostErrors::user_not_authorized();
		//    			DispatchManager::redirect($error_controller);
		// 		}
		// 	break;
		// 	default:
		// 		$error_controller = PHPBoostErrors::unexisting_page();
		// 		DispatchManager::redirect($error_controller);
		// 	break;
		// }
	}

	// private function get_document()
	// {
	// 	if ($this->document === null)
	// 	{
	// 		$id = AppContext::get_request()->get_getint('id', 0);
	// 		if (!empty($id)) {
	// 			try {
	// 				$this->document = WikiService::get_document('WHERE wiki.id=:id', array('id' => $id));
	// 			} catch (RowNotFoundException $e)
	// 			{
	// 				$error_controller = PHPBoostErrors::unexisting_page();
   	// 				DispatchManager::redirect($error_controller);
	// 			}
	// 		} else
	// 			$this->document = new Document();
	// 	}
	// 	return $this->document;
	// }

	private function get_document()
	{
		if ($this->document === null)
		{
			$id = AppContext::get_request()->get_getint('id', 0);
			if (!empty($id))
			{
				try
				{
					$this->document = WikiService::get_document('WHERE wiki.id=:id', array('id' => $id));
				}
				catch(RowNotFoundException $e)
				{
					$error_controller = PHPBoostErrors::unexisting_page();
					DispatchManager::redirect($error_controller);
				}
			}
			else
			{
				$this->is_new_document = true;
				$this->document = new Document();
				$this->document->init_default_properties(AppContext::get_request()->get_getint('id_category', Category::ROOT_CATEGORY));
			}
		}
		return $this->document;
	}

	private function generate_response()
	{
		$document = $this->get_document();
		$response = new SiteDisplayResponse($this->tpl);

		$graphical_environment = $response->get_graphical_environment();
		$graphical_environment->set_page_title($this->document->get_title(), $this->lang['module.title']);

		$breadcrumb = $graphical_environment->get_breadcrumb();
		$breadcrumb->add($this->lang['module.title'], WikiUrlBuilder::home());
		$categories = array_reverse(WikiService::get_categories_manager()->get_parents($document->get_id_category(), true));
		foreach ($categories as $id => $category)
		{
			if ($category->get_id() != Category::ROOT_CATEGORY)
				$breadcrumb->add($category->get_name(), WikiUrlBuilder::display_category($category->get_id(), $category->get_rewrited_name()));
		}
		$breadcrumb->add($document->get_title(), WikiUrlBuilder::display_item($category->get_id(), $category->get_rewrited_name(), $document->get_id(), $document->get_rewrited_title()));

		$breadcrumb->add($this->lang['wiki.historic'], WikiUrlBuilder::change_history($document->get_id()));
		$graphical_environment->set_page_title($this->lang['wiki.historic'], $this->lang['module.title']);
		$graphical_environment->get_seo_meta_data()->set_description($this->lang['wiki.historic']);
		$graphical_environment->get_seo_meta_data()->set_canonical_url(WikiUrlBuilder::change_history($document->get_id()));

		return $response;
	}
}
?>
