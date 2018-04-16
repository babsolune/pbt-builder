<?php
/*##################################################
 *                       ModmixItemFormController.class.php
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

class ModmixItemFormController extends ModuleController
{
	/**
	 * @var HTMLForm
	 */
	private $form;
	/**
	 * @var FormButtonSubmit
	 */
	private $submit_button;

	private $tpl;

	private $lang;
	private $common_lang;

	private $itemmix;
	private $is_new_itemmix;

	public function execute(HTTPRequestCustom $request)
	{
		$this->init();
		$this->check_authorizations();
		$this->build_form($request);

		$tpl = new StringTemplate('# INCLUDE FORM #');
		$tpl->add_lang($this->lang);

		if ($this->submit_button->has_been_submited() && $this->form->validate())
		{
			$this->save();
			$this->redirect();
		}

		$tpl->put('FORM', $this->form->display());

		return $this->generate_response($tpl);
	}

	private function init()
	{
		$this->lang = LangLoader::get('common', 'modmix');
		$this->common_lang = LangLoader::get('common');
	}

	private function build_form(HTTPRequestCustom $request)
	{
		$form = new HTMLForm(__CLASS__);

		$fieldset = new FormFieldsetHTML('modmix', $this->lang['modmix.module.title']);
		$form->add_fieldset($fieldset);

		$fieldset->add_field(new FormFieldTextEditor('title', $this->common_lang['form.title'], $this->get_itemmix()->get_title(),
			array('required' => true)
		));

		if (ModmixAuthorizationsService::check_authorizations($this->get_itemmix()->get_id_category())->moderation())
		{
			$fieldset->add_field(new FormFieldCheckbox('personalize_rewrited_title', $this->common_lang['form.rewrited_name.personalize'], $this->get_itemmix()->rewrited_title_is_personalized(),
				array('events' => array('click' =>'
					if (HTMLForms.getField("personalize_rewrited_title").getValue()) {
						HTMLForms.getField("rewrited_title").enable();
					} else {
						HTMLForms.getField("rewrited_title").disable();
					}'
				))
			));

			$fieldset->add_field(new FormFieldTextEditor('rewrited_title', $this->common_lang['form.rewrited_name'], $this->get_itemmix()->get_rewrited_title(),
				array('description' => $this->common_lang['form.rewrited_name.description'],
				      'hidden' => !$this->get_itemmix()->rewrited_title_is_personalized()),
				array(new FormFieldConstraintRegex('`^[a-z0-9\-]+$`iu'))
			));
		}

		if (ModmixService::get_categories_manager()->get_categories_cache()->has_categories())
		{
			$search_category_children_options = new SearchCategoryChildrensOptions();
			$search_category_children_options->add_authorizations_bits(Category::CONTRIBUTION_AUTHORIZATIONS);
			$search_category_children_options->add_authorizations_bits(Category::WRITE_AUTHORIZATIONS);
			$fieldset->add_field(ModmixService::get_categories_manager()->get_select_categories_form_field('id_category', $this->common_lang['form.category'], $this->get_itemmix()->get_id_category(), $search_category_children_options));
		}

		$fieldset->add_field(new FormFieldCheckbox('enable_description', $this->lang['modmix.form.enabled.description'], $this->get_itemmix()->get_description_enabled(),
			array('description' => StringVars::replace_vars($this->lang['modmix.form.enabled.description.description'],
			array('number' => ModmixConfig::load()->get_characters_number_to_cut())),
				'events' => array('click' => '
					if (HTMLForms.getField("enable_description").getValue()) {
						HTMLForms.getField("description").enable();
					} else {
						HTMLForms.getField("description").disable();
					}'
		))));

		$fieldset->add_field(new FormFieldRichTextEditor('description', StringVars::replace_vars($this->lang['modmix.form.description'],
			array('number' =>ModmixConfig::load()->get_characters_number_to_cut())), $this->get_itemmix()->get_description(),
			array('rows' => 3, 'hidden' => !$this->get_itemmix()->get_description_enabled())
		));

		$fieldset->add_field(new FormFieldRichTextEditor('contents', $this->common_lang['form.contents'], $this->get_itemmix()->get_contents(),
			array('rows' => 15, 'required' => true)
		));

		if ($this->get_itemmix()->get_displayed_author_name() == true)
		{
			$fieldset->add_field(new FormFieldCheckbox('enabled_author_name_customization', $this->lang['modmix.form.enabled.author.name.customisation'], $this->get_itemmix()->is_enabled_author_name_customization(),
				array('events' => array('click' => '
				if (HTMLForms.getField("enabled_author_name_customization").getValue()) {
					HTMLForms.getField("custom_author_name").enable();
				} else {
					HTMLForms.getField("custom_author_name").disable();
				}'))
			));

			$fieldset->add_field(new FormFieldTextEditor('custom_author_name', $this->lang['modmix.form.custom.author.name'], $this->get_itemmix()->get_custom_author_name(), array(
				'hidden' => !$this->get_itemmix()->is_enabled_author_name_customization(),
			)));
		}

		$other_fieldset = new FormFieldsetHTML('other', $this->common_lang['form.other']);
		$form->add_fieldset($other_fieldset);

		$other_fieldset->add_field(new FormFieldCheckbox('displayed_author_name', LangLoader::get_message('config.author_displayed', 'admin-common'), $this->get_itemmix()->get_displayed_author_name()));

		$other_fieldset->add_field(new FormFieldUploadPictureFile('thumbnail', $this->common_lang['form.picture'], $this->get_itemmix()->get_thumbnail()->relative()));

		$other_fieldset->add_field(ModmixService::get_keywords_manager()->get_form_field($this->get_itemmix()->get_id(), 'keywords', $this->common_lang['form.keywords'],
			array('description' => $this->common_lang['form.keywords.description'])
		));

		$other_fieldset->add_field(new ModmixFormFieldSelectSources('sources', $this->common_lang['form.sources'], $this->get_itemmix()->get_sources()));

		$other_fieldset->add_field(new ModmixFormFieldCarousel('carousel', $this->lang['modmix.form.carousel'], $this->get_itemmix()->get_carousel()));

		if (ModmixAuthorizationsService::check_authorizations($this->get_itemmix()->get_id_category())->moderation())
		{
			$publication_fieldset = new FormFieldsetHTML('publication', $this->common_lang['form.approbation']);
			$form->add_fieldset($publication_fieldset);

			$publication_fieldset->add_field(new FormFieldDateTime('creation_date', $this->common_lang['form.date.creation'], $this->get_itemmix()->get_creation_date(),
				array('required' => true)
			));

			if (!$this->get_itemmix()->is_published())
			{
				$publication_fieldset->add_field(new FormFieldCheckbox('update_creation_date', $this->common_lang['form.update.date.creation'], false, array('hidden' => $this->get_itemmix()->get_status() != Itemmix::NOT_PUBLISHED)
				));
			}

			$publication_fieldset->add_field(new FormFieldSimpleSelectChoice('publication_state', $this->common_lang['form.approbation'], $this->get_itemmix()->get_publication_state(),
				array(
					new FormFieldSelectChoiceOption($this->common_lang['form.approbation.not'], Itemmix::NOT_PUBLISHED),
					new FormFieldSelectChoiceOption($this->common_lang['form.approbation.now'], Itemmix::PUBLISHED_NOW),
					new FormFieldSelectChoiceOption($this->common_lang['status.approved.date'], Itemmix::PUBLICATION_DATE),
				),
				array('events' => array('change' => '
				if (HTMLForms.getField("publication_state").getValue() == 2) {
					jQuery("#' . __CLASS__ . '_publication_start_date_field").show();
					HTMLForms.getField("end_date_enable").enable();
				} else {
					jQuery("#' . __CLASS__ . '_publication_start_date_field").hide();
					HTMLForms.getField("end_date_enable").disable();
				}'))
			));

			$publication_fieldset->add_field(new FormFieldDateTime('publication_start_date', $this->common_lang['form.date.start'],
				($this->get_itemmix()->get_publication_start_date() === null ? new Date() : $this->get_itemmix()->get_publication_start_date()),
				array('hidden' => ($this->get_itemmix()->get_publication_state() != Itemmix::PUBLICATION_DATE))
			));

			$publication_fieldset->add_field(new FormFieldCheckbox('end_date_enable', $this->common_lang['form.date.end.enable'], $this->get_itemmix()->enabled_end_date(),
				array('hidden' => ($this->get_itemmix()->get_publication_state() != Itemmix::PUBLICATION_DATE),
					'events' => array('click' => '
						if (HTMLForms.getField("end_date_enable").getValue()) {
							HTMLForms.getField("publication_end_date").enable();
						} else {
							HTMLForms.getField("publication_end_date").disable();
						}'
				))
			));

			$publication_fieldset->add_field(new FormFieldDateTime('publication_end_date', $this->common_lang['form.date.end'],
				($this->get_itemmix()->get_publication_end_date() === null ? new date() : $this->get_itemmix()->get_publication_end_date()),
				array('hidden' => !$this->get_itemmix()->enabled_end_date())
			));
		}

		$this->build_contribution_fieldset($form);

		$fieldset->add_field(new FormFieldHidden('referrer', $request->get_url_referrer()));

		$this->submit_button = new FormButtonDefaultSubmit();
		$form->add_button($this->submit_button);
		$form->add_button(new FormButtonReset());

		$this->form = $form;
	}

	private function build_contribution_fieldset($form)
	{
		if ($this->get_itemmix()->get_id() === null && $this->is_contributor_member())
		{
			$fieldset = new FormFieldsetHTML('contribution', LangLoader::get_message('contribution', 'user-common'));
			$fieldset->set_description(MessageHelper::display(LangLoader::get_message('contribution.explain', 'user-common'), MessageHelper::WARNING)->render());
			$form->add_fieldset($fieldset);

			$fieldset->add_field(new FormFieldRichTextEditor('contribution_description', LangLoader::get_message('contribution.description', 'user-common'), '',
				array('description' => LangLoader::get_message('contribution.description.explain', 'user-common'))));
		}
	}

	private function is_contributor_member()
	{
		return (!ModmixAuthorizationsService::check_authorizations()->write() && ModmixAuthorizationsService::check_authorizations()->contribution());
	}

	private function get_itemmix()
	{
		if ($this->itemmix === null)
		{
			$id = AppContext::get_request()->get_getint('id', 0);
			if (!empty($id))
			{
				try
				{
					$this->itemmix = ModmixService::get_itemmix('WHERE modmix.id=:id', array('id' => $id));
				}
				catch(RowNotFoundException $e)
				{
					$error_controller = PHPBoostErrors::unexisting_page();
					DispatchManager::redirect($error_controller);
				}
			}
			else
			{
				$this->is_new_itemmix = true;
				$this->itemmix = new Itemmix();
				$this->itemmix->init_default_properties(AppContext::get_request()->get_getint('id_category', Category::ROOT_CATEGORY));
			}
		}
		return $this->itemmix;
	}

	private function check_authorizations()
	{
		$itemmix = $this->get_itemmix();

		if ($itemmix->get_id() === null)
		{
			if (!$itemmix->is_authorized_to_add())
			{
				$error_controller = PHPBoostErrors::user_not_authorized();
				DispatchManager::redirect($error_controller);
			}
		}
		else
		{
			if (!$itemmix->is_authorized_to_edit())
			{
				$error_controller = PHPBoostErrors::user_not_authorized();
				DispatchManager::redirect($error_controller);
			}
		}
		if (AppContext::get_current_user()->is_readonly())
		{
			$controller = PHPBoostErrors::user_in_read_only();
			DispatchManager::redirect($controller);
		}
	}

	private function save()
	{
		$itemmix = $this->get_itemmix();

		$itemmix->set_title($this->form->get_value('title'));

		if (ModmixService::get_categories_manager()->get_categories_cache()->has_categories())
			$itemmix->set_id_category($this->form->get_value('id_category')->get_raw_value());

		$itemmix->set_description(($this->form->get_value('enable_description') ? $this->form->get_value('description') : ''));
		$itemmix->set_contents($this->form->get_value('contents'));

		$displayed_author_name = $this->form->get_value('displayed_author_name') ? $this->form->get_value('displayed_author_name') : Itemmix::NOTDISPLAYED_AUTHOR_NAME;
		$itemmix->set_displayed_author_name($displayed_author_name);
		$itemmix->set_thumbnail(new Url($this->form->get_value('thumbnail')));

		if ($this->get_itemmix()->get_displayed_author_name() == true)
			$itemmix->set_custom_author_name(($this->form->get_value('custom_author_name') && $this->form->get_value('custom_author_name') !== $itemmix->get_author_user()->get_display_name() ? $this->form->get_value('custom_author_name') : ''));

		$itemmix->set_sources($this->form->get_value('sources'));
		$itemmix->set_carousel($this->form->get_value('carousel'));

		if (!ModmixAuthorizationsService::check_authorizations($itemmix->get_id_category())->moderation())
		{
			if ($itemmix->get_id() === null)
				$itemmix->set_creation_date(new Date());

			$itemmix->set_rewrited_title(Url::encode_rewrite($itemmix->get_title()));
			$itemmix->clean_publication_start_and_end_date();

			if (ModmixAuthorizationsService::check_authorizations($itemmix->get_id_category())->contribution() && !ModmixAuthorizationsService::check_authorizations($itemmix->get_id_category())->write())
				$itemmix->set_publication_state(Itemmix::NOT_PUBLISHED);
		}
		else
		{
			if ($this->form->get_value('update_creation_date'))
			{
				$itemmix->set_creation_date(new Date());
			}
			else
			{
				$itemmix->set_creation_date($this->form->get_value('creation_date'));
			}

			$rewrited_title = $this->form->get_value('rewrited_title', '');
			$rewrited_title = $this->form->get_value('personalize_rewrited_title') && !empty($rewrited_title) ? $rewrited_title : Url::encode_rewrite($itemmix->get_title());
			$itemmix->set_rewrited_title($rewrited_title);

			$itemmix->set_publication_state($this->form->get_value('publication_state')->get_raw_value());
			if ($itemmix->get_publication_state() == Itemmix::PUBLICATION_DATE)
			{
				$config = ModmixConfig::load();
				$deferred_operations = $config->get_deferred_operations();

				$old_start_date = $itemmix->get_publication_start_date();
				$start_date = $this->form->get_value('publication_start_date');
				$itemmix->set_publication_start_date($start_date);

				if ($old_start_date !== null && $old_start_date->get_timestamp() != $start_date->get_timestamp() && in_array($old_start_date->get_timestamp(), $deferred_operations))
				{
					$key = array_search($old_start_date->get_timestamp(), $deferred_operations);
					unset($deferred_operations[$key]);
				}

				if (!in_array($start_date->get_timestamp(), $deferred_operations))
					$deferred_operations[] = $start_date->get_timestamp();

				if ($this->form->get_value('end_date_enable'))
				{
					$old_end_date = $itemmix->get_publication_end_date();
					$end_date = $this->form->get_value('publication_end_date');
					$itemmix->set_publication_end_date($end_date);

					if ($old_end_date !== null && $old_end_date->get_timestamp() != $end_date->get_timestamp() && in_array($old_end_date->get_timestamp(), $deferred_operations))
					{
						$key = array_search($old_end_date->get_timestamp(), $deferred_operations);
						unset($deferred_operations[$key]);
					}

					if (!in_array($end_date->get_timestamp(), $deferred_operations))
						$deferred_operations[] = $end_date->get_timestamp();
				}
				else
				{
					$itemmix->clean_publication_end_date();
				}

				$config->set_deferred_operations($deferred_operations);
				ModmixConfig::save();
			}
			else
			{
				$itemmix->clean_publication_start_and_end_date();
			}
		}

		if ($itemmix->get_id() === null)
		{
			$itemmix->set_author_user(AppContext::get_current_user());
			$id_itemmix = ModmixService::add($itemmix);
		}
		else
		{
			$now = new Date();
			$itemmix->set_updated_date($now);
			$id_itemmix = $itemmix->get_id();
			ModmixService::update($itemmix);
		}

		$this->contribution_actions($itemmix, $id_itemmix);

		ModmixService::get_keywords_manager()->put_relations($id_itemmix, $this->form->get_value('keywords'));

		Feed::clear_cache('modmix');
		ModmixCategoriesCache::invalidate();
	}

	private function contribution_actions(Itemmix $itemmix, $id_itemmix)
	{
		if ($itemmix->get_id() === null)
		{
			if ($this->is_contributor_member())
			{
				$contribution = new Contribution();
				$contribution->set_id_in_module($id_itemmix);
				$contribution->set_description(stripslashes($this->form->get_value('contribution_description')));
				$contribution->set_entitled($itemmix->get_title());
				$contribution->set_fixing_url(ModmixUrlBuilder::edit_item($id_itemmix)->relative());
				$contribution->set_poster_id(AppContext::get_current_user()->get_id());
				$contribution->set_module('modmix');
				$contribution->set_auth(
					Authorizations::capture_and_shift_bit_auth(
						ModmixService::get_categories_manager()->get_heritated_authorizations($itemmix->get_id_category(), Category::MODERATION_AUTHORIZATIONS, Authorizations::AUTH_CHILD_PRIORITY),
						Category::MODERATION_AUTHORIZATIONS, Contribution::CONTRIBUTION_AUTH_BIT
					)
				);
				ContributionService::save_contribution($contribution);
			}
		}
		else
		{
			$corresponding_contributions = ContributionService::find_by_criteria('modmix', $id_itemmix);
			if (count($corresponding_contributions) > 0)
			{
				foreach ($corresponding_contributions as $contribution)
				{
					$contribution->set_status(Event::EVENT_STATUS_PROCESSED);
					ContributionService::save_contribution($contribution);
				}
			}
		}
		$itemmix->set_id($id_itemmix);
	}

	private function redirect()
	{
		$itemmix = $this->get_itemmix();
		$category = $itemmix->get_category();

		if ($this->is_new_itemmix && $this->is_contributor_member() && !$itemmix->is_published())
		{
			DispatchManager::redirect(new UserContributionSuccessController());
		}
		elseif ($itemmix->is_published())
		{
			if ($this->is_new_itemmix)
				AppContext::get_response()->redirect(ModmixUrlBuilder::display_item($category->get_id(), $category->get_rewrited_name(), $itemmix->get_id(), $itemmix->get_rewrited_title(), AppContext::get_request()->get_getint('page', 1)), StringVars::replace_vars($this->lang['modmix.message.success.add'], array('title' => $itemmix->get_title())));
			else
				AppContext::get_response()->redirect(($this->form->get_value('referrer') ? $this->form->get_value('referrer') : ModmixUrlBuilder::display_item($category->get_id(), $category->get_rewrited_name(), $itemmix->get_id(), $itemmix->get_rewrited_title(), AppContext::get_request()->get_getint('page', 1))), StringVars::replace_vars($this->lang['modmix.message.success.edit'], array('title' => $itemmix->get_title())));
		}
		else
		{
			if ($this->is_new_itemmix)
				AppContext::get_response()->redirect(ModmixUrlBuilder::display_pending_items(), StringVars::replace_vars($this->lang['modmix.message.success.add'], array('title' => $itemmix->get_title())));
			else
				AppContext::get_response()->redirect(($this->form->get_value('referrer') ? $this->form->get_value('referrer') : ModmixUrlBuilder::display_pending_items()), StringVars::replace_vars($this->lang['modmix.message.success.edit'], array('title' => $itemmix->get_title())));
		}
	}

	private function generate_response(View $tpl)
	{
		$itemmix = $this->get_itemmix();

		$response = new SiteDisplayResponse($tpl);
		$graphical_environment = $response->get_graphical_environment();

		$breadcrumb = $graphical_environment->get_breadcrumb();
		$breadcrumb->add($this->lang['modmix.module.title'], ModmixUrlBuilder::home());

		if ($itemmix->get_id() === null)
		{
			$breadcrumb->add($this->lang['modmix.add'], ModmixUrlBuilder::add_item($itemmix->get_id_category()));
			$graphical_environment->set_page_title($this->lang['modmix.add'], $this->lang['modmix.module.title']);
			$graphical_environment->get_seo_meta_data()->set_description($this->lang['modmix.add']);
			$graphical_environment->get_seo_meta_data()->set_canonical_url(ModmixUrlBuilder::add_item($itemmix->get_id_category()));
		}
		else
		{
			$categories = array_reverse(ModmixService::get_categories_manager()->get_parents($itemmix->get_id_category(), true));
			foreach ($categories as $id => $category)
			{
				if ($category->get_id() != Category::ROOT_CATEGORY)
					$breadcrumb->add($category->get_name(), ModmixUrlBuilder::display_category($category->get_id(), $category->get_rewrited_name()));
			}
			$breadcrumb->add($itemmix->get_title(), ModmixUrlBuilder::display_item($category->get_id(), $category->get_rewrited_name(), $itemmix->get_id(), $itemmix->get_rewrited_title()));

			$breadcrumb->add($this->lang['modmix.edit'], ModmixUrlBuilder::edit_item($itemmix->get_id()));
			$graphical_environment->set_page_title($this->lang['modmix.edit'], $this->lang['modmix.module.title']);
			$graphical_environment->get_seo_meta_data()->set_description($this->lang['modmix.edit']);
			$graphical_environment->get_seo_meta_data()->set_canonical_url(ModmixUrlBuilder::edit_item($itemmix->get_id()));
		}

		return $response;
	}
}
?>